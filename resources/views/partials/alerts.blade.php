@foreach (['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'status' => 'info'] as $key => $type)
    @if (session($key))
        <div class="alert alert-{{ $type }} alert-dismissible fade show" role="alert">
            {{ session($key) }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
@endforeach
