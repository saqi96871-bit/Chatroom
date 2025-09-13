<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Support\Facades\Storage;

class MessageSent implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public $sender_id;
    public $receiver_id;
    public $messageModel;

    public function __construct(Message $message)
    {
        $this->sender_id   = $message->sender_id;
        $this->receiver_id = $message->receiver_id;
        $this->messageModel = $message;
    }

    public function broadcastOn()
    {
        return [
            new PrivateChannel('Chatroom.' . $this->sender_id),
            new PrivateChannel('Chatroom.' . $this->receiver_id),
        ];
    }

    public function broadcastWith()
    {
        return [
            'id'         => $this->messageModel->id,
            'message'    => $this->messageModel->message,
            'image'      => $this->messageModel->image ? Storage::url($this->messageModel->image) : null,
            'audio'      => $this->messageModel->audio ? Storage::url($this->messageModel->audio) : null,
            'created_at' => $this->messageModel->created_at->format('Y-m-d H:i:s'),
            'sender'     => [
                'id'   => $this->messageModel->sender->id,
                'name' => $this->messageModel->sender->name
            ],
            'receiver_id' => $this->messageModel->receiver_id,
            'sender_id'   => $this->messageModel->sender_id,
        ];
    }
}
