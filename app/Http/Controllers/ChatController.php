<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:500',
            'room' => 'nullable|string',
        ]);

        $user = Auth::user();
        $userName = $user ? $user->name : 'Guest_'.rand(100, 999);
        $userAvatar = $user ? $user->avatar : 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150&auto=format&fit=crop&q=80';
        $vipLevel = $user ? $user->vip_level : 1;

        $msg = ChatMessage::create([
            'user_name' => $userName,
            'user_avatar' => $userAvatar,
            'vip_level' => $vipLevel,
            'message' => $validated['message'],
            'room' => $validated['room'] ?? 'global',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => $msg,
        ]);
    }
}
