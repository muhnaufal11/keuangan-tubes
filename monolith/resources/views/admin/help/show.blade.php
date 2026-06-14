@extends('layouts.app')

@section('header_title')
    Session #{{ $session->id }}
@endsection

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <a href="{{ route('admin.help.index') }}" class="btn btn-secondary btn-sm me-2"><i class="bi bi-arrow-left"></i> Kembali</a>
            <strong>Session #{{ $session->id }} — {{ $session->user->name ?? $session->user->username }}</strong>
        </div>
        <div>
            @if($session->status !== 'closed')
                <form method="POST" action="{{ route('admin.help.close', $session->id) }}" style="display:inline-block">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Tutup session ini? Pengguna tidak dapat lagi mengirim pesan pada session yang ditutup.');">
                        <i class="bi bi-x-circle"></i> Close Session
                    </button>
                </form>
            @else
                <span class="badge bg-secondary">Closed</span>
            @endif
        </div>
    </div>

    <div style="height:400px; overflow:auto; border:1px solid #ddd; padding:10px;" id="chat-box">
        @foreach($session->messages as $m)
            <div style="margin-bottom:10px;">
                <strong>{{ $m->user->name ?? $m->user->username }}:</strong>
                {!! nl2br(e($m->message)) !!}
                <br><small class="text-muted">{{ $m->created_at }}</small>
            </div>
        @endforeach
    </div>

    <form method="POST" action="{{ route('admin.help.send') }}" class="mt-2" @if($session->status === 'closed') style="display:none;" @endif>
        @csrf
        <input type="hidden" name="help_session_id" value="{{ $session->id }}">
        <div class="input-group">
            <input type="text" id="message-input" name="message" class="form-control" placeholder="Balas pesan..." @if($session->status === 'closed') disabled @endif>
            <button class="btn btn-primary" id="send-btn" @if($session->status === 'closed') disabled @endif>Kirim</button>
        </div>
    </form>

    @if($session->status === 'closed')
        <p class="text-muted mt-2">Session ini telah ditutup. Tidak dapat mengirim pesan lagi.</p>
    @endif
</div>

@if($session->status !== 'closed')
<script>
window.addEventListener('beforeunload', function (e) {
    e.preventDefault();
    e.returnValue = 'Anda memiliki sesi chat yang masih aktif. Apakah Anda yakin ingin keluar?';
});
</script>
@endif

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sessionId = '{{ $session->id }}';
    const chatBox = document.getElementById('chat-box');
    const status = '{{ $session->status }}';

    async function fetchMessages() {
        const res = await fetch('/help/messages/' + sessionId);
        const data = await res.json();
        chatBox.innerHTML = '';
        data.forEach(m => {
            const el = document.createElement('div');
            el.style.marginBottom = '10px';
            el.innerHTML = '<strong>' + (m.user.name || m.user.username) + ':</strong> ' + (m.message.replace(/\n/g, '<br>')) + '<br><small class="text-muted">' + new Date(m.created_at).toLocaleString() + '</small>';
            chatBox.appendChild(el);
        });
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    // initial load + polling only if not closed
    if (status !== 'closed') {
        fetchMessages();
        const poll = setInterval(fetchMessages, 3000);
    }

    // handle send via AJAX only if not closed
    if (status !== 'closed') {
        const sendBtn = document.getElementById('send-btn');
        const msgInput = document.getElementById('message-input');
        sendBtn.addEventListener('click', async function(e) {
            e.preventDefault();
            const msg = msgInput.value.trim();
            if (!msg) return;
            try {
                const res = await fetch('{{ route('admin.help.send') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ help_session_id: sessionId, message: msg })
                });

                if (!res.ok) {
                    const errData = await res.json().catch(() => ({}));
                    throw new Error(errData.error || errData.message || 'Server returned ' + res.status);
                }

                msgInput.value = '';
                fetchMessages();
            } catch (err) {
                console.error(err);
                alert('Gagal mengirim pesan: ' + err.message);
            }
        });
    }
});
</script>

@endsection
