<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\MessageStatus;
use App\Models\Service;
use App\Models\User;
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
    /**
     * @OA\Post(
     *   path="/api/login",
     *   tags={"Auth"},
     *   summary="Login y obtiene JWT",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"username","password"},
     *       @OA\Property(property="username", type="string", example="admin"),
     *       @OA\Property(property="password", type="string", example="secret")
     *     )
     *   ),
     *   @OA\Response(response=200, description="OK",
     *     @OA\JsonContent(
     *       @OA\Property(property="access_token", type="string"),
     *       @OA\Property(property="token_type",   type="string", example="bearer"),
     *       @OA\Property(property="expires_in",   type="integer", example=3600)
     *     )
     *   ),
     *   @OA\Response(response=401, description="Invalid credentials")
     * )
     */
    // GET /api/messages
    public function index(Request $request)
    {
        $user = Auth::guard('api')->user();

        $filters = $request->validate([
            'status'     => ['nullable','array','min:1'],
            'status.*'   => ['string', Rule::exists('message_statuses','key')],
            'services'   => ['nullable','array','min:1'],
            'services.*' => ['string', Rule::exists('services','name')],
            'from'       => ['nullable','date'],
            'to'         => ['nullable','date'],
            'per_page'   => ['nullable','integer','min:1','max:100'],
        ]);

        $query = Message::query()->with(['service:id,name', 'user:id,username', 'statusRef:id,key,name']);

        $isAdmin = ($user->role?->type ?? null) === 'admin';
        if (!$isAdmin) {
            $query->where('user_id', $user->id);
        }

        if (!empty($filters['status'])) {
            $query->whereIn('message_status_id', MessageStatus::whereIn('key', $filters['status'])->pluck('id'));
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
            'content'         => ['required','string'],
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
                $message->message_status_id = MessageStatus::idByKey('pending');
                $message->content           = $data['content'];
                $message->user_id           = $user->id;
                $message->service_id        = $service->id;
                $message->date_sent         = null;
                $message->provider_response = null;
                $message->save();
    
                switch ($serviceName) {
                    case 'slack':
                        $clientService = app()->makeWith(SlackService::class, [
                                            'endpoint' => $service->endpoint,
                                        ]); break;
                    case 'telegram':
                        $clientService = app()->makeWith(TelegramService::class, [
                                            'endpoint' => $service->endpoint,
                                        ]); break;
                    case 'discord':
                        $clientService = app()->makeWith(DiscordService::class, [
                                            'endpoint' => $service->endpoint,
                                        ]); break;
                    default:
                        return response()->json([
                            'message' => "Service not configured: {$serviceName}."
                        ], 404);
                }
                        
                $respose = $clientService->postMessage($signed);
                $ok = ($respose['ok'] ?? false) === true;

                $message->message_status_id = MessageStatus::idByKey($ok ? 'success' : 'failed');
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
        $status = $anyOk ? 201 : 400;

        return response()->json([
            'message' => $anyOk ? 'Message(s) sent' : 'No message was sent',
            'results' => $results,
        ], $status);
    }

    /**
     * @OA\Get(
     *   path="/api/admin/metrics/messages",
     *   tags={"Admin"},
     *   summary="Métricas de mensajes por usuario (solo admin)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="OK",
     *     @OA\JsonContent(
     *       @OA\Property(property="date", type="string", format="date"),
     *       @OA\Property(property="metrics", type="array",
     *         @OA\Items(
     *           @OA\Property(property="user_id",         type="integer"),
     *           @OA\Property(property="username",        type="string"),
     *           @OA\Property(property="role",            type="string"),
     *           @OA\Property(property="daily_msg_limit", type="integer"),
     *           @OA\Property(property="today_tries",     type="integer"),
     *           @OA\Property(property="today_sent",      type="integer"),
     *           @OA\Property(property="remaining_today", type="integer"),
     *           @OA\Property(property="total_tries",     type="integer"),
     *           @OA\Property(property="total_sent",      type="integer")
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(response=403, description="Forbidden")
     * )
     */
    // GET /api/admin/metrics/messages
    public function adminGetUsersMetrics()
    {
        $auth = Auth::guard('api')->user();
        if (!$auth) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        $auth = $auth->fresh(['role']);
        if (($auth->role->type ?? null) !== 'admin') {
            return response()->json(['message' => 'You do not have permissions.'], 403);
        }

        $today = Carbon::today();
        $sentId = MessageStatus::idByKey('success');

        // total enviados por usuario
        $successTotals = Message::select('user_id', DB::raw("COUNT(*) as total_sent"))
            ->where('message_status_id', $sentId)
            ->groupBy('user_id')
            ->pluck('total_sent', 'user_id');

        // total intentos por usuario
        $tryTotals = Message::select('user_id', DB::raw("COUNT(*) as total_try"))
            ->groupBy('user_id')
            ->pluck('total_try', 'user_id');

        // tries today
        $todayTries = Message::select('user_id', DB::raw("COUNT(*) as count_today"))
            ->whereDate('created_at', $today->toDateString())
            ->groupBy('user_id')
            ->pluck('count_today', 'user_id');

        // success sent today
        $todaySent = Message::select('user_id', DB::raw("COUNT(*) as count_today"))
            ->whereDate('created_at', $today->toDateString())
            ->where('message_status_id', $sentId)
            ->groupBy('user_id')
            ->pluck('count_today', 'user_id');

        // usuarios y rol para el límite
        $users = User::with(['role:id,type,daily_msg_limit'])
            ->select('id','username','role_id')
            ->get();

        $rows = $users->map(function ($u) use ($successTotals, $tryTotals, $todayTries, $todaySent) {
            $limit = (int) ($u->role?->daily_msg_limit ?? 0);
            $triesToday = (int) ($todayTries[$u->id] ?? 0);

            $remaining = ($limit === 0) ? 'unlimited' : max($limit - $triesToday, 0);

            return [
                'user_id'         => $u->id,
                'username'        => $u->username,
                'role'            => $u->role?->type,
                'daily_msg_limit' => $limit,
                'today_tries'     => (int) ($todayTries[$u->id] ?? 0),
                'today_sent'      => (int) ($todaySent[$u->id] ?? 0),
                'remaining_today' => $remaining,
                'total_tries'     => (int) ($tryTotals[$u->id] ?? 0),
                'total_sent'      => (int) ($successTotals[$u->id] ?? 0),
            ];
        })->values();

        return response()->json([
            'date'    => $today->toDateString(),
            'metrics' => $rows,
        ]);
    }
}
