<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

Route::get('/layout', function () {
    return view('layout.app');
});
Route::view('layout/app', 'dashboard.app')->name('dashboard.app');
Route::view('layout/button', 'commponents.buttons')->name('button');
Route::view('layout/cards', 'commponents.cards')->name('cards');
Route::view('register/view', 'auth.register')->name('register.view');
Route::view('login/view', 'auth.login')->name('login.view');
Route::post('register', [AuthController::class, 'register'])->name('register');
Route::post('login', [AuthController::class, 'login'])->name('login');
Route::get('logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/chat/view', [ChatController::class, 'index']);

Route::get('/chat/messages', [ChatController::class, 'fetchMessages'])->name('chat.messages');

Route::get('/messages', [ChatController::class, 'messages'])->name('chat.messages')->middleware('auth');
Route::post('/chat/send', [ChatController::class, 'send'])->name('chat.send');
Route::get('/chat/{receiverId}', [ChatController::class, 'chat'])->name('chat.view');
Route::get('/chat/{userId}', [ChatController::class, 'loadMessages'])->name('chat.loadMessages');
Route::post('/block', [ChatController::class, 'block'])->name('chat.block');
Route::post('/unblock', [ChatController::class, 'unblock'])->name('chat.unblock');
