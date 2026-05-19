<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    // GET /api/messages/conversations
    public function getConversations(Request $request)
    {
        $userId = $request->user()->id;

        // Fetch latest message per conversation
        $latestMessages = Message::where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->unique(function ($item) use ($userId) {
                return $item->sender_id === $userId ? $item->receiver_id : $item->sender_id;
            });

        $conversations = [];
        foreach ($latestMessages as $msg) {
            $otherUserId = $msg->sender_id === $userId ? $msg->receiver_id : $msg->sender_id;
            $otherUser = User::find($otherUserId);
            
            $unreadCount = Message::where('sender_id', $otherUserId)
                ->where('receiver_id', $userId)
                ->where('is_read', false)
                ->count();

            $conversations[] = [
                'id' => 'conv_' . $otherUserId,
                'userId' => $otherUserId,
                'name' => $otherUser->nom,
                'lastMessage' => $msg->text ?? ($msg->file_url ? 'Pièce jointe' : ''),
                'lastMessageAt' => $msg->created_at,
                'unreadCount' => $unreadCount,
                'online' => false, // Simplification for now
            ];
        }

        return response()->json(array_values($conversations));
    }

    // POST /api/messages/conversations (Create new conversation)
    public function createConversation(Request $request)
    {
        $data = $request->validate([
            'receiverId' => ['required', 'exists:users,id'],
        ]);
        return response()->json(['id' => 'conv_' . $data['receiverId']]);
    }

    // GET /api/messages/conversations/{id}
    public function getMessages(Request $request, $id)
    {
        $userId = $request->user()->id;
        $otherUserId = str_replace('conv_', '', $id);

        $messages = Message::where(function ($q) use ($userId, $otherUserId) {
                $q->where('sender_id', $userId)->where('receiver_id', $otherUserId);
            })
            ->orWhere(function ($q) use ($userId, $otherUserId) {
                $q->where('sender_id', $otherUserId)->where('receiver_id', $userId);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        // Mark as read
        Message::where('sender_id', $otherUserId)
            ->where('receiver_id', $userId)
            ->update(['is_read' => true]);

        $formatted = $messages->map(function ($m) {
            return [
                'id' => $m->id,
                'senderId' => $m->sender_id,
                'receiverId' => $m->receiver_id,
                'text' => $m->text,
                'createdAt' => $m->created_at,
                'isRead' => $m->is_read,
                'fileUrl' => $m->file_url,
                'fileName' => $m->file_name,
            ];
        });

        return response()->json($formatted);
    }

    // POST /api/messages
    public function store(Request $request)
    {
        $data = $request->validate([
            'receiverId' => ['required', 'exists:users,id'],
            'text' => ['nullable', 'string'],
            'fileUrl' => ['nullable', 'string'],
            'fileName' => ['nullable', 'string'],
        ]);

        $message = Message::create([
            'sender_id' => $request->user()->id,
            'receiver_id' => $data['receiverId'],
            'text' => $data['text'],
            'file_url' => $data['fileUrl'],
            'file_name' => $data['fileName'],
            'is_read' => false,
        ]);

        return response()->json(['success' => true, 'message' => $message]);
    }
}
