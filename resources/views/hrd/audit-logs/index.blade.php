@extends('layouts.app')

@section('title', 'Audit Log')
@section('page-title', 'Audit Log')

@section('content')
    @php($types = ['User', 'Personnel', 'PersonnelAssignment', 'OrganizationalUnit', 'Position', 'AssessmentAuthorization', 'AssessmentPeriod', 'AssessmentCriterion', 'Assessment', 'AssessmentDetail', 'Reward', 'PointTransaction'])

    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-3">
            <select name="event" class="form-select">
                <option value="">Semua kejadian</option>
                @foreach ($events as $e)<option value="{{ $e }}" @selected(request('event') === $e)>{{ $e }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select name="type" class="form-select">
                <option value="">Semua objek</option>
                @foreach ($types as $t)<option value="{{ $t }}" @selected(request('type') === $t)>{{ $t }}</option>@endforeach
            </select>
        </div>
        <div class="col-auto">
            <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
            <a href="{{ route('hrd.audit-logs.index') }}" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light"><tr><th>Waktu</th><th>Pengguna</th><th>Kejadian</th><th>Objek</th><th>Perubahan</th><th>IP</th></tr></thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td class="text-nowrap small">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                            <td>{{ $log->user?->name ?? 'Sistem' }}</td>
                            <td><span class="badge text-bg-light border">{{ $log->event }}</span></td>
                            <td class="small">{{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}</td>
                            <td style="min-width:260px">
                                @if ($log->old_values || $log->new_values)
                                    <details>
                                        <summary class="small text-primary" style="cursor:pointer">Lihat</summary>
                                        @if ($log->old_values)<div class="small text-muted mt-1">Sebelum</div><pre class="json-pre">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>@endif
                                        @if ($log->new_values)<div class="small text-muted mt-1">Sesudah</div><pre class="json-pre">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>@endif
                                    </details>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="small text-muted">{{ $log->ip_address }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty icon="journal-text">Belum ada catatan.</x-empty></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $logs->links() }}</div>
@endsection
