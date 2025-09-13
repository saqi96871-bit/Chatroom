@extends('layout.app')

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        .sidebar-users {
            background: #f8f9fa;
            border-right: 1px solid #ddd;
            padding: 10px;
            height: 80vh;
            overflow-y: auto;
        }

        .user-item-wrapper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 6px;
            border-radius: 6px;
            margin-bottom: 6px;
            background: #fff;
            transition: background 0.2s;
        }

        .user-item-wrapper:hover {
            background: #f1f1f1;
        }

        .user-item {
            display: flex;
            align-items: center;
            gap: 8px;
            border: none;
            background: transparent;
            width: 100%;
            text-align: left;
            padding: 4px;
        }

        .user-item img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
        }

        .user-item.selected {
            background: #d1ecf1;
            border-radius: 6px;
        }

        .block-btn {
            padding: 4px 6px;
            font-size: 14px;
            line-height: 1;
        }

        #chat-box {
            height: 65vh;
            overflow-y: auto;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 12px;
        }

        #message-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        #message-list li {
            margin-bottom: 10px;
            max-width: 75%;
            word-wrap: break-word;
        }

        #message-list li.text-end {
            margin-left: auto;
            background: #e2f7e1;
            padding: 8px;
            border-radius: 8px;
        }

        #message-list li.text-start {
            margin-right: auto;
            background: #f1f1f1;
            padding: 8px;
            border-radius: 8px;
        }

        .msg-time {
            font-size: 0.75rem;
            color: #888;
            margin-top: 2px;
        }
    </style>

    <div class="container mt-4">
        <div class="row">
            @auth
                @php
                    $blockedUsers = Auth::user()->blockedUsers()->pluck('blocked_user_id')->toArray();
                    $users = App\Models\User::where('id', '!=', Auth::id())
                        ->get()
                        ->map(function ($user) use ($blockedUsers) {
                            $user->is_blocked = in_array($user->id, $blockedUsers);
                            return $user;
                        });

                @endphp

                <!-- Sidebar -->
                <div class="col-md-3 sidebar-users">
                    <h5 class="mb-3">💬 Users</h5>
                    <div id="user-list">
                        @forelse($users as $user)
                            <div class="user-item-wrapper">
                                <button class="user-item flex-grow-1" data-user-id="{{ $user->id }}"
                                    data-user-name="{{ $user->name }}">
                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=random"
                                        alt="{{ $user->name }}">
                                    <span>{{ $user->name }}</span>
                                    <span class="user-status"></span>
                                </button>
                                @if ($user->is_blocked)
                                    <button class="btn btn-sm btn-success unlock-btn" data-user-id="{{ $user->id }}"
                                        data-blocked="true" title="Unblock this user">
                                        ✅
                                    </button>
                                @else
                                    <button class="btn btn-sm btn-outline-danger block-btn" data-user-id="{{ $user->id }}"
                                        data-blocked="false" title="Block this user">
                                        🚫
                                    </button>
                                @endif
                            </div>
                        @empty
                            <div class="text-muted text-center">No other users found</div>
                        @endforelse
                    </div>
                </div>
                <!-- Chat Area -->
                <div class="col-md-9">
                    <h3 class="text-center mb-4">
                        Chat Room ({{ Auth::user()->name }})
                    </h3>

                    <div id="chat-box" data-messages-url="{{ route('chat.messages') }}"
                        data-send-url="{{ route('chat.send') }}">
                        <ul id="message-list">
                            <li class="text-muted text-center">Select a user to start chatting.</li>
                        </ul>
                    </div>

                    <form id="chat-form" class="d-flex mt-3" style="display: none;" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" id="sender_id" value="{{ Auth::id() }}">
                        <input type="hidden" id="receiver_id">

                        <input type="text" id="message" class="form-control me-2" placeholder="Type a message...">
                        <input type="file" id="image" class="form-control me-2" accept="image/*">
                        <input type="file" id="audio" class="form-control me-2" accept="audio/*">

                        <button type="button" id="startRecording" class="btn btn-secondary">🎙</button>
                        <button type="button" id="stopRecording" class="btn btn-danger" style="display:none;">⏹</button>

                        <button type="submit" class="btn btn-primary">Send</button>
                    </form>
                </div>
            @else
                <div class="col-md-8 offset-md-2">
                    <div class="alert alert-danger text-center">
                        Please <a href="{{ route('login') }}">log in</a> to access the chat room.
                    </div>
                </div>
            @endauth
        </div>
    </div>

    @auth
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <script src="https://js.pusher.com/7.2/pusher.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]')
                    .content;

                const chatBox = document.getElementById('chat-box');
                const messageList = document.getElementById('message-list');
                const form = document.getElementById('chat-form');
                const messageInput = document.getElementById('message');
                const imageInput = document.getElementById('image');
                const audioInput = document.getElementById('audio');
                const senderId = parseInt(document.getElementById('sender_id').value);
                const receiverInput = document.getElementById('receiver_id');
                const messagesUrl = chatBox.dataset.messagesUrl;
                const sendUrl = chatBox.dataset.sendUrl;
                const startBtn = document.getElementById('startRecording');
                const stopBtn = document.getElementById('stopRecording');
                const blockBtn = document.querySelector('.block-btn');
                const userList = document.getElementById('user-list');
                const unblockBtn = document.querySelector('.unlock-btn');


                let mediaRecorder, audioChunks = [];

                const scrollToBottom = () => chatBox.scrollTop = chatBox.scrollHeight;

                const renderMessage = (msg) => {
                    const isSender = parseInt(msg.sender.id) === senderId;
                    const li = document.createElement('li');
                    li.className = isSender ? 'text-end' : 'text-start';
                    li.innerHTML = `
            ${msg.message ? `<div>${msg.message}</div>` : ''}
              ${msg.image ? `<img src="${msg.image}" style="max-width:150px; border-radius:8px; margin-top:5px;">` : ''}
            ${msg.audio ? `<audio controls style="margin-top:5px; max-width:200px;"><source src="${msg.audio}" type="audio/mpeg"></audio>` : ''}
            <div class="msg-time">${msg.created_at}</div>
        `;
                    messageList.appendChild(li);
                };

                const loadMessages = (receiverId) => {
                    messageList.innerHTML = `<li class="text-muted text-center">Loading messages...</li>`;
                    axios.get(`${messagesUrl}?sender_id=${senderId}&receiver_id=${receiverId}`)
                        .then(({
                            data
                        }) => {
                            messageList.innerHTML = '';

                            if (data.blocked) {
                                messageList.innerHTML =
                                    '<li class="text-danger text-center">You cannot send messages to this user.</li>';
                                form.style.display = 'none';
                                return;
                            }

                            if (data.length === 0) {
                                messageList.innerHTML =
                                    '<li class="text-muted text-center">No messages yet.</li>';
                            } else {
                                let hasUnblocked = false;
                                data.forEach(msg => {
                                    if (!msg.is_blocked) {
                                        renderMessage(msg);
                                        hasUnblocked = true;
                                    }
                                });

                                if (!hasUnblocked) {
                                    messageList.innerHTML =
                                        '<li class="text-danger text-center">Messages are blocked between you and this user.</li>';
                                    form.style.display = 'none';
                                    return;
                                }
                            }

                            form.style.display = 'flex';
                            scrollToBottom();
                        })
                        .catch(() => {
                            messageList.innerHTML =
                                '<li class="text-danger text-center">Failed to load messages.</li>';
                        });
                }
                // Delegated event listener for block/unblock buttons
                document.getElementById('user-list').addEventListener('click', (e) => {
                    const btn = e.target.closest('.block-btn, .unlock-btn');
                    if (!btn) return; // not a block/unblock button

                    const userId = btn.dataset.userId;
                    const isBlocked = btn.dataset.blocked === 'true';

                    if (!isBlocked) {
                        // Block user
                        axios.post('{{ route('chat.block') }}', {
                                blocked_user_id: userId
                            })
                            .then(() => {
                                btn.textContent = '✅';
                                btn.title = 'Unblock this user';
                                btn.classList.remove('btn-outline-danger', 'block-btn');
                                btn.classList.add('btn-success', 'unlock-btn');
                                btn.dataset.blocked = 'true';

                                if (receiverInput.value == userId) {
                                    form.style.display = 'none';
                                    messageList.innerHTML =
                                        '<li class="text-danger text-center">🚫 You have blocked this user.</li>';
                                }

                                Swal.fire({
                                    icon: 'warning',
                                    title: 'User Blocked',
                                    text: 'You will no longer receive or send messages with this user.',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            });
                    } else {
                        // Unblock user
                        axios.post('{{ route('chat.unblock') }}', {
                                blocked_user_id: userId
                            })
                            .then(() => {
                                btn.textContent = '🚫';
                                btn.title = 'Block this user';
                                btn.classList.remove('btn-success', 'unlock-btn');
                                btn.classList.add('btn-outline-danger', 'block-btn');
                                btn.dataset.blocked = 'false';

                                if (receiverInput.value == userId) {
                                    loadMessages(userId);
                                    form.style.display = 'flex';
                                }

                                Swal.fire({
                                    icon: 'success',
                                    title: 'User Unblocked',
                                    text: 'You can now chat with this user again.',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            });
                    }
                });


                document.querySelectorAll('.user-item').forEach(btn => {
                    btn.addEventListener('click', () => {
                        document.querySelectorAll('.user-item').forEach(b => b.classList.remove(
                            'selected'));
                        btn.classList.add('selected');
                        receiverInput.value = btn.dataset.userId;
                        form.style.display = 'flex';
                        loadMessages(receiverInput.value);
                    });
                });

                form.addEventListener('submit', e => {
                    e.preventDefault();

                    // If form is hidden, do not send
                    if (form.style.display === 'none') {
                        alert('You cannot send messages to this user.');
                        return;
                    }

                    const msg = messageInput.value.trim();
                    const imageFile = imageInput.files[0];
                    const audioFile = audioInput.files[0];
                    if (!msg && !imageFile && !audioFile) return;

                    let formData = new FormData();
                    formData.append('sender_id', senderId);
                    formData.append('receiver_id', receiverInput.value);
                    formData.append('message', msg);
                    if (imageFile) formData.append('image', imageFile);
                    if (audioFile) formData.append('audio', audioFile);

                    axios.post(sendUrl, formData)
                        .then(() => {
                            form.reset(); // clears text and file inputs safely
                        })
                        .catch(error => {
                            if (error.response && error.response.status === 403) {
                                alert('You are blocked from sending messages to this user.');
                                form.style.display = 'none';
                            } else {
                                alert('Message send failed.');
                            }
                        });
                });

                startBtn.addEventListener('click', async () => {
                    try {
                        const stream = await navigator.mediaDevices.getUserMedia({
                            audio: true
                        });
                        mediaRecorder = new MediaRecorder(stream);
                        audioChunks = [];

                        mediaRecorder.ondataavailable = e => {
                            if (e.data.size > 0) {
                                audioChunks.push(e.data);
                            }
                        };

                        mediaRecorder.start();
                        startBtn.style.display = 'none';
                        stopBtn.style.display = 'inline-block';

                        stopBtn.addEventListener('click', () => {
                            mediaRecorder.stop();
                            stopBtn.style.display = 'none';
                            startBtn.style.display = 'inline-block';

                            mediaRecorder.onstop = () => {
                                const audioBlob = new Blob(audioChunks, {
                                    type: 'audio/webm'
                                });
                                const audioFile = new File([audioBlob], "recording.webm", {
                                    type: "audio/webm"
                                });

                                // Put recorded audio into input (or directly into form data)
                                let dt = new DataTransfer();
                                dt.items.add(audioFile);
                                audioInput.files = dt.files;
                            };
                        }, {
                            once: true
                        }); // ensure listener attaches only once

                    } catch (err) {
                        alert('Microphone access denied or not available.');
                        console.error(err);
                    }
                });


                stopBtn.addEventListener('click', () => {
                    mediaRecorder.stop();
                    mediaRecorder.onstop = () => {
                        const audioBlob = new Blob(audioChunks, {
                            type: 'audio/webm'
                        });
                        const audioFile = new File([audioBlob], 'recording.webm', {
                            type: 'audio/webm'
                        });
                        const dt = new DataTransfer();
                        dt.items.add(audioFile);
                        audioInput.files = dt.files;
                    };
                    stopBtn.style.display = 'none';
                    startBtn.style.display = 'inline-block';
                });

                const pusher = new Pusher("5a5b4e73ef6ff623fb19", {
                    cluster: "ap1",
                    forceTLS: true,
                    authEndpoint: "/broadcasting/auth",
                    auth: {
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    }
                });

                pusher.subscribe(`private-Chatroom.${senderId}`)
                    .bind('App\\Events\\MessageSent', data => {
                        const fromSender = parseInt(data.sender_id);
                        const toReceiver = parseInt(data.receiver_id);
                        const activeReceiverId = parseInt(receiverInput.value);
                        const isRelevant =
                            (fromSender === senderId && toReceiver === activeReceiverId) ||
                            (fromSender === activeReceiverId && toReceiver === senderId);

                        if (isRelevant && !data.is_blocked) {
                            renderMessage(data);
                            scrollToBottom();
                        }
                    });
            });
        </script>
    @endauth
@endsection
