@extends('layouts.app')

@section('title', 'Dashboard HRD')
@section('page-title', 'Dashboard HRD')

@section('content')
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3"><a href="{{ route('hrd.personnel.create') }}" class="btn btn-primary w-100"><i class="bi bi-person-plus me-1"></i>Tambah Personel</a></div>
        <div class="col-6 col-md-3"><a href="{{ route('hrd.periods.index') }}" class="btn btn-outline-primary w-100"><i class="bi bi-calendar-range me-1"></i>Periode</a></div>
        <div class="col-6 col-md-3"><a href="{{ route('hrd.authorizations.create') }}" class="btn btn-outline-primary w-100"><i class="bi bi-person-check me-1"></i>Kewenangan</a></div>
        <div class="col-6 col-md-3"><a href="{{ route('hrd.rewards.index') }}" class="btn btn-outline-primary w-100"><i class="bi bi-award me-1"></i>Apresiasi</a></div>
    </div>
    @include('dashboards.partials.recent-notes')
    @include('dashboards.partials.analytics', ['action' => route('hrd.dashboard'), 'isHrd' => true])
@endsection
