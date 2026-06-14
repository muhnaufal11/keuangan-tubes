@extends('layouts.app')

@section('header_title', 'Get Help')

@section('content')
<div class="container mt-4">
    <h4>Bantuan / Live Chat</h4>

    @if(!$session)
        <p>Belum ada sesi chat aktif.</p>
        <form method="POST" action="{{ route('help.start') }}">
            @csrf
            <button class="btn btn-primary">Mulai Chat dengan Admin</button>
        </form>
    @elseif($session->status == 'closed')
        <p>Session chat ini telah ditutup. Anda tidak dapat mengirim pesan lagi.</p>
        <form method="POST" action="{{ route('help.start') }}">
            @csrf
            <button class="btn btn-primary">Mulai Session Baru</button>
        </form>
    @else
        <div id="chat-box" class="chat-container">
            <!-- messages akan diisi oleh JS -->
        </div>

        <form id="send-form" class="mt-2">
            @csrf
            <input type="hidden" id="help_session_id" value="{{ $session->id }}">
            <div class="input-group">
                <input type="text" id="message-input" class="form-control" placeholder="Ketik pesan...">
                <button class="btn btn-primary" id="send-btn">Kirim</button>
            </div>
        </form>
    @endif
</div>

@if($session && $session->status == 'open')
<script>
window.addEventListener('beforeunload', function (e) {
    e.preventDefault();
    e.returnValue = 'Anda memiliki sesi chat yang masih aktif. Apakah Anda yakin ingin keluar?';
});
</script>
@endif

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sessionId = document.getElementById('help_session_id') ? document.getElementById('help_session_id').value : null;
    const chatBox = document.getElementById('chat-box');
    
    // Instantiate ChatUtil
    // routes object constructed from blade
    const routes = {
        messages: '/help/messages/__ID__'
    };
    const chatUtil = new ChatUtil(routes);
    const currentUserId = {{ Auth::id() }};

    let lastMessageId = 0;

    async function fetchMessages() {
        if (!sessionId || !chatBox) return;
        const data = await chatUtil.fetchMessages(sessionId, lastMessageId);
        
        if (data.length > 0) {
            data.forEach(m => {
                if(m.id <= lastMessageId) return;
                const isSelf = (m.user_id === currentUserId);
                const el = chatUtil.createMessageElement(m, isSelf);
                chatBox.appendChild(el);
                if(m.id > lastMessageId) lastMessageId = m.id;
            });
            chatBox.scrollTop = chatBox.scrollHeight;
        }
    }

    if (sessionId) {
        fetchMessages();
        setInterval(fetchMessages, 3000);

        document.getElementById('send-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const msgInput = document.getElementById('message-input');
            const msg = msgInput.value.trim();
            if (!msg) return;
            
            try {
                await chatUtil.sendMessage('/help/messages', {
                    help_session_id: sessionId, 
                    message: msg
                });
                msgInput.value = '';
                fetchMessages();
            } catch (err) {
                console.error('Error sending message:', err);
                alert('Error sending message: ' + (err.message || err));
            }
        });
    }
});
</script>

@endsection
