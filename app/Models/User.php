<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Messages sent by the user.
     */
    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    /**
     * Messages received by the user.
     */
    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    /**
     * Users that this user has blocked.
     */
    public function blockedUsers()
    {
        return $this->hasMany(blocked_users::class, 'user_id');
    }

    /**
     * Check if this user is blocked by another user.
     */
    public function isBlockedBy($userId)
    {
        return blocked_users::where('user_id', $userId)
            ->where('blocked_user_id', $this->id)
            ->exists();
    }
}
