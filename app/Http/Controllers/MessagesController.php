<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use App\Services\SlackService;
use App\Services\TelegramService;
use App\Services\DiscordService;

class MessagesController extends Controller
{
    // GET /api/messages
    public function index(Request $request)
    {
        $user = Auth::guard('api')->user();

        $filters = $request->validate([
            'status'     => ['nullable','array','min:1'],
            'status.*'   => ['string', Rule::in(['success','pending','failed'])],
            'services'   => ['nullable','array','min:1'],
            'services.*' => ['string', Rule::exists('services','name')],
            'from'       => ['nullable','date'],
            'to'         => ['nullable','date'],
            'per_page'   => ['nullable','integer','min:1','max:100'],
        ]);

        $query = Message::query()->with(['service:id,name', 'user:id,username']);

        $isAdmin = ($user->role?->type ?? null) === 'admin';
        if (!$isAdmin) {
            $query->where('user_id', $user->id);
        }

        if (!empty($filters['status'])) {
            $query->whereIn('status', $filters['status']);
        }

        if (!empty($filters['services'])) {
            $serviceIds = Service::whereIn('name', $filters['services'])->pluck('id');
            $query->whereIn('service_id', $serviceIds);
        }

        if (!empty($filters['from']) || !empty($filters['to'])) {
            $from = !empty($filters['from'])
                ? Carbon::parse($filters['from'])->startOfDay()
                : Carbon::create(1990,1,1);
            $to = !empty($filters['to'])
                ? Carbon::parse($filters['to'])->endOfDay()
                : Carbon::now()->endOfDay();

            $query->whereBetween('created_at', [$from, $to]);
        }

        $messages = $query
            ->latest('id')
            ->paginate($request->integer('per_page', 15))
            ->appends($request->query());

        return response()->json($messages);
    }

    // POST /api/send_message
    public function store(Request $request)
    {
        $user = Auth::guard('api')->user();

        $data = $request->validate([
            'content'     => ['required','string'],
            'destinations'    => ['required','array','min:1'],
            'destinations.*'  => ['required','string', Rule::exists('services','name')],
        ]);

        $serviceRows = Service::whereIn('name', $data['destinations'])
            ->get()->keyBy(fn($s) => strtolower($s->name));
        if ($serviceRows->count() !== count($data['destinations'])) {
            return response()->json(['message' => 'At least one service entered was not found as an available service'], 422);
        }
        $signed = "From @{$user->username}: {$data['content']}";
        $results = [];

        foreach ($data['destinations'] as $serviceName) {

            $key = strtolower($serviceName);
            $service = $serviceRows->get($key);
            if (!$service) {
                $results[$key] = ['ok' => false, 'error' => "Service not configured: {$serviceName}"];
                continue;
            }

            try {
                DB::beginTransaction();
    
                $message = new Message();
                $message->status            = 'pending';
                $message->content           = $data['content'];
                $message->user_id           = $user->id;
                $message->service_id        = $service->id;
                $message->date_sent         = null;
                $message->provider_response = null;
                $message->save();
    
                switch ($serviceName) {
                    case 'slack':
                        $clientService = new SlackService($service->endpoint); break;
                    case 'telegram':
                        $clientService = new TelegramService($service->endpoint); break;
                    case 'discord':
                        $clientService = new DiscordService($service->endpoint); break;
                    default:
                        return response()->json([
                            'message' => "Service not configured: {$serviceName}."
                        ], 404);
                }
                        
                $respose = $clientService->postMessage($signed);
                $ok = ($respose['ok'] ?? false) === true;

                $message->status            = $ok ? 'success' : 'failed';
                $message->date_sent         = $ok ? now() : null;
                $message->provider_response = $respose ?? ['ok' => false, 'error' => 'no response'];
                $message->save();
    
                DB::commit();

                $results[$key] = [
                    'ok'        => $ok,
                    'service'   => $serviceName,
                    'service_id'=> $service->id,
                    'message_id'=> $message->id,
                    'response'  => $message->provider_response,
                ];
                
            } catch (\Throwable $e) {
                DB::rollBack();
                $results[$key] = [
                    'ok'      => false,
                    'service' => $serviceName,
                    'error'   => $e->getMessage(),
                ];
            }
        }

        $anyOk = collect($results)->contains(fn($r) => $r['ok'] === true);
        $status = $anyOk ? 201 : 207;

        return response()->json([
            'message' => $anyOk ? 'Message(s) sent' : 'No message was sent',
            'results' => $results,
        ], $status);
    }
}
