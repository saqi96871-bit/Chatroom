<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('Chatroom.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});


