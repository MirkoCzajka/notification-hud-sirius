<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    // GET /api/register
    public function register(Request $request)
    {
        $data = $request->validate([
            'username' => ['required','string','max:100','unique:users,username'],
            'password' => ['required', Password::min(8)],
            'role'     => ['nullable','in:admin,user'],
        ]);

        $roleType = $data['role'] ?? 'user';
        $roleId = UserRole::where('type', $roleType)->value('id');

        if (!$roleId) {
            return response()->json(['message' => "Rol '$roleType' doesn't exist."], 422);
        }

        $user = User::create([
            'username' => $data['username'],
            'password' => $data['password'],
            'role_id'  => $roleId,
        ]);

        return response()->json([
            'message' => 'The user has been registered',
            'user'    => $user->only(['id','username','role_id']),
        ], 201);
    }

    // GET /api/login
    public function login(Request $request)
    {
        $cred = $request->validate([
            'username' => ['required','string'],
            'password' => ['required','string'],
        ]);

        if (! $token = Auth::guard('api')->attempt($cred)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        return $this->respondWithToken($token);
    }

    // GET /api/me
    public function me()
    {
        return response()->json(Auth::guard('api')->user());
    }

    // POST /api/logout
    public function logout()
    {
        Auth::guard('api')->logout();
        return response()->json(['message' => 'Logout ok']);
    }

    // POST /api/refresh
    public function refresh()
    {
        return $this->respondWithToken(Auth::guard('api')->refresh());
    }

    private function respondWithToken(string $token)
    {
        $factory = Auth::guard('api')->factory();

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => $factory->getTTL() * 60,
        ]);
    }
}
