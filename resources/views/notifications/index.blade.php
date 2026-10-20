@extends(auth()->user()->layout())

@section('title', 'Notifikasi')
@section('page-title', 'Notifikasi')

@section('content')
    @php($icons = ['submit' => 'send', 'return' => 'arrow-counterclockwise', 'approve' => 'check2-circle', 'finalize' => 'patch-check'])

    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="text-muted small">{{ $unreadCount }} belum dibaca</span>
        @if ($unreadCount)
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button class="btn btn-sm btn-outline-primary">Tandai semua dibaca</button>
            </form>
        @endif
    </div>

    <div class="card border-0 shadow-sm">
        <div class="list-group list-group-flush">
            @forelse ($notifications as $n)
                @php($unread = $n->read_at === null)
                <a href="{{ route('notifications.open', $n->id) }}" class="list-group-item list-group-item-action d-flex gap-3 py-3 {{ $unread ? 'bg-primary-subtle bg-opacity-25' : '' }}">
                    <i class="bi bi-{{ $icons[$n->data['action'] ?? ''] ?? 'bell' }} fs-4 text-primary"></i>
                    <div class="flex-grow-1">
                        <div class="{{ $unread ? 'fw-bold' : 'fw-semibold' }}">{{ $n->data['title'] ?? 'Notifikasi' }}</div>
                        <div class="small">{{ $n->data['message'] ?? '' }}</div>
                        @if (! empty($n->data['notes']))
                            <div class="small text-danger mt-1"><i class="bi bi-chat-left-text me-1"></i>{{ $n->data['notes'] }}</div>
                        @endif
                        <div class="small text-muted mt-1">{{ $n->created_at->diffForHumans() }}</div>
                    </div>
                    @if ($unread)<span class="badge bg-danger align-self-start">Baru</span>@endif
                </a>
            @empty
                <x-empty icon="bell-slash">Belum ada notifikasi.</x-empty>
            @endforelse
        </div>
    </div>

    <div class="mt-3">{{ $notifications->links() }}</div>
@endsection
