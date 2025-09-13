<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class blocked_users extends Model
{
    // Explicit table name (optional, but makes intent clear)
    protected $table = 'blocked_users';

    protected $fillable = [
        'user_id',
        'blocked_user_id',
    ];

    /**
     * The user who is blocking others.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The user who is being blocked.
     */
    public function blocked()
    {
        return $this->belongsTo(User::class, 'blocked_user_id');
    }
}
