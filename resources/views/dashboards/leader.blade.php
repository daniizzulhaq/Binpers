@extends('layouts.app')

@section('title', 'Dashboard Pimpinan')
@section('page-title', 'Dashboard Pimpinan')

@section('content')
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3">
            <a href="{{ route('assessor.assessments.index') }}" class="btn btn-primary w-100"><i class="bi bi-pencil-square me-1"></i>Menilai Anggota</a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('review.index') }}" class="btn btn-outline-primary w-100"><i class="bi bi-clipboard-check me-1"></i>Pemeriksaan</a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('notes.create') }}" class="btn btn-outline-primary w-100"><i class="bi bi-plus-circle me-1"></i>Tulis Catatan</a>
        </div>
    </div>

    @include('dashboards.partials.recent-notes')

    @include('dashboards.partials.analytics', ['action' => route('leader.dashboard'), 'isHrd' => false])

    @include('partials.my-recent-notes')
@endsection
