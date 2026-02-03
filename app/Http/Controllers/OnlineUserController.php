<?php

namespace App\Http\Controllers;

use App\Models\OnlineUser;
use Illuminate\Http\Request;

class OnlineUserController extends Controller
{
    public function ping(Request $request)
    {
        $user = $request->user() ?? $request->user('sanctum');

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Update or create last_seen_at
        OnlineUser::updateOrCreate(
            ['user_id' => $user->id],
            ['last_seen_at' => now()]
        );

        return response()->json([
            'message' => 'Ping saved',
            'user_id' => $user->id,
            'last_seen_at' => now(),
        ]);
    }

    public function index()
    {
        $onlineThreshold = now()->subMinutes(5);

        $onlineUsers = OnlineUser::with('user')
            ->where('last_seen_at', '>=', $onlineThreshold)
            ->orderByDesc('last_seen_at')
            ->get();

        return response()->json($onlineUsers);
    }
}
