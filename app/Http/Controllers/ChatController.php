<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Events\MessageSent;
use App\Models\blocked_users;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use PhpParser\Node\Stmt\Block;

class ChatController extends Controller
{
    public function index()
    {
        return view('chat.index');
    }

    public function Messages(Request $request)
    {
        $isBlocked = blocked_users::where('user_id', $request->receiver_id)
            ->where('blocked_user_id', $request->sender_id)
            ->exists();
        // $isBlocked = $isBlocked || blocked_users::where('user_id', $request->sender_id)
        //     ->where('blocked_user_id', $request->receiver_id)
        //     ->exists();

        $messages = Message::where(function ($q) use ($request) {
            $q->where('sender_id', $request->sender_id)
                ->where('receiver_id', $request->receiver_id);
        })
            ->orWhere(function ($q) use ($request) {
                $q->where('sender_id', $request->receiver_id)
                    ->where('receiver_id', $request->sender_id);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'blocked'  => $isBlocked, // <-- This flag tells frontend
            'messages' => $messages
        ]);
    }
    public function send(Request $request)
    {
        // Block check before sending
        if (
            blocked_users::where('user_id', $request->receiver_id)
            ->where('blocked_user_id', $request->sender_id)
            ->exists() ||
            blocked_users::where('user_id', $request->sender_id)
            ->where('blocked_user_id', $request->receiver_id)
            ->exists()
        ) {
            return response()->json(['error' => 'User is blocked.'], 403);
        }

        $data = [

            'sender_id' => $request->sender_id,
            'receiver_id' => $request->receiver_id,
            'message' => $request->message,
        ];

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('chat_images', 'public');
        }
        if ($request->hasFile('audio')) {
            $data['audio'] = $request->file('audio')->store('chat_audio', 'public');
        }
        $message = Message::create($data);

        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'success' => true,
            'message' => $message,
            'image'   => isset($data['image']) ? asset('storage/' . $data['image']) : null,
            'audio'   => isset($data['audio']) ? asset('storage/' . $data['audio']) : null,
        ]);
    }
    public function block(Request $request)
    {

        blocked_users::firstOrCreate([
            'user_id' => auth()->id(),
            'blocked_user_id' => $request->blocked_user_id
        ]);

        return response()->json(['status' => 'blocked']);
    }

    public function unblock(Request $request)
    {
        blocked_users::where('user_id', auth()->id())
            ->where('blocked_user_id', $request->blocked_user_id)
            ->delete();

        return response()->json(['status' => 'unblocked']);
    }


}
