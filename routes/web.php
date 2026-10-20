<?php

use App\Enums\RoleName;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EvidenceController;
use App\Http\Controllers\Hrd\AssessmentAuthorizationController;
use App\Http\Controllers\Hrd\AssessmentCriterionController;
use App\Http\Controllers\Hrd\AssessmentPeriodController;
use App\Http\Controllers\Hrd\AuditLogController;
use App\Http\Controllers\Hrd\OrganizationalUnitController;
use App\Http\Controllers\Hrd\PersonnelAssignmentController;
use App\Http\Controllers\Hrd\PersonnelController;
use App\Http\Controllers\Hrd\PositionController;
use App\Http\Controllers\Hrd\RewardController;
use App\Http\Controllers\Hrd\UserController;
use App\Http\Controllers\MemberAssessmentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MyNoteController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\Hrd\NotePointSettingController;
use App\Http\Controllers\MonthlyRecapController;
use App\Http\Controllers\NoteImageController;
use App\Http\Controllers\PerformanceNoteController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.attempt');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // ---------------- Profil (semua peran) ----------------
    Route::get('profil', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('profil/kata-sandi', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // ---------------- Notifikasi (semua peran) ----------------
    Route::prefix('notifikasi')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('baca-semua', [NotificationController::class, 'readAll'])->name('read-all');
        Route::get('{id}/buka', [NotificationController::class, 'open'])->name('open');
    });

    // ---------------- Bukti pendukung (otorisasi diperiksa di policy) ----------------
    Route::get('bukti/{detail}', [EvidenceController::class, 'show'])->whereNumber('detail')->name('evidence.show');

 // ---------------- Catatan Kinerja (sewaktu-waktu) ----------------
    Route::prefix('catatan')->name('notes.')->group(function () {
        Route::get('/', [PerformanceNoteController::class, 'index'])
            ->middleware('permission:assessments.create|reports.view')->name('index');

        // Catatan kinerja yang saya terima/buat sendiri
        Route::get('saya', [MyNoteController::class, 'index'])->name('mine');
        Route::get('{note}/gambar', [NoteImageController::class, 'show'])->whereNumber('note')->name('image');
        Route::middleware('role_or_permission:hrd|assessments.create')->group(function () {
            Route::get('buat', [PerformanceNoteController::class, 'create'])->name('create');
            Route::post('/', [PerformanceNoteController::class, 'store'])->name('store');
            Route::get('{note}/ubah', [PerformanceNoteController::class, 'edit'])
                ->whereNumber('note')->name('edit');
            Route::put('{note}', [PerformanceNoteController::class, 'update'])
                ->whereNumber('note')->name('update');
            Route::delete('{note}', [PerformanceNoteController::class, 'destroy'])
                ->whereNumber('note')->name('destroy');
        });
    });

    // ---------------- HRD ----------------
    Route::middleware('role:'.RoleName::Hrd->value)
        ->prefix('hrd')->name('hrd.')

        ->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'hrd'])->name('dashboard');
Route::middleware('permission:periods.manage')->group(function () {
                Route::get('poin-catatan', [NotePointSettingController::class, 'edit'])->name('note-points.edit');
                Route::put('poin-catatan', [NotePointSettingController::class, 'update'])->name('note-points.update');
            });
            Route::middleware('permission:users.manage')->group(function () {
                Route::get('users', [UserController::class, 'index'])->name('users.index');
                Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
                Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
            });

            Route::middleware('permission:organization.manage')->group(function () {
                Route::resource('units', OrganizationalUnitController::class)->except('show');
            });

            Route::middleware('permission:positions.manage')->group(function () {
                Route::resource('positions', PositionController::class)->except('show');
            });

            Route::middleware('permission:personnel.manage')->group(function () {
                Route::resource('personnel', PersonnelController::class)->parameters(['personnel' => 'personnel']);
                Route::post('personnel/{personnel}/assignments', [PersonnelAssignmentController::class, 'store'])->name('personnel.assignments.store');
                Route::delete('assignments/{assignment}', [PersonnelAssignmentController::class, 'destroy'])->name('assignments.destroy');
            });

            Route::middleware('permission:authorizations.manage')->group(function () {
                Route::post('authorizations/by-unit', [AssessmentAuthorizationController::class, 'storeByUnit'])->name('authorizations.by-unit');
                Route::resource('authorizations', AssessmentAuthorizationController::class)->except('show');
            });

            Route::middleware('permission:periods.manage')->group(function () {
                Route::resource('periods', AssessmentPeriodController::class);
                Route::post('periods/{period}/open', [AssessmentPeriodController::class, 'open'])->name('periods.open');
                Route::post('periods/{period}/close', [AssessmentPeriodController::class, 'close'])->name('periods.close');
            });

            Route::middleware('permission:criteria.manage')->group(function () {
                Route::post('periods/{period}/criteria', [AssessmentCriterionController::class, 'store'])->name('criteria.store');
                Route::post('periods/{period}/criteria/copy', [AssessmentCriterionController::class, 'copy'])->name('criteria.copy');
                Route::put('criteria/{criterion}', [AssessmentCriterionController::class, 'update'])->name('criteria.update');
                Route::delete('criteria/{criterion}', [AssessmentCriterionController::class, 'destroy'])->name('criteria.destroy');
            });

            Route::middleware('permission:rewards.manage')->group(function () {
                Route::resource('rewards', RewardController::class)->except('show');
                Route::get('rewards/{reward}/rekomendasi', [RewardController::class, 'recommendations'])->name('rewards.recommendations');
                Route::post('rewards/{reward}/berikan/{personnel}', [RewardController::class, 'grant'])->name('rewards.grant');
                Route::get('poin', [RewardController::class, 'ledger'])->name('points.index');
                Route::post('personnel/{personnel}/poin', [RewardController::class, 'adjust'])->name('points.adjust');
            });

            Route::get('audit-logs', [AuditLogController::class, 'index'])
                ->middleware('permission:audit-logs.view')->name('audit-logs.index');
        });

    // ---------------- Kapolres & Wakapolres ----------------
    Route::middleware('role:'.implode('|', RoleName::leaders()))
        ->prefix('pimpinan')->name('leader.')
        ->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'leader'])->name('dashboard');
        });

    // ---------------- Laporan & pemeriksaan (HRD, Kapolres, Wakapolres) ----------------
    Route::get('laporan', [ReportController::class, 'index'])
        ->middleware('permission:reports.view')->name('reports.index');
    Route::get('rekap-bulanan', [MonthlyRecapController::class, 'index'])
        ->middleware('permission:reports.view')->name('recap.index');
    Route::post('rekap-bulanan/{period}/kunci', [MonthlyRecapController::class, 'lock'])
        ->middleware('permission:periods.manage')->name('recap.lock');
    Route::middleware('permission:assessments.approve')
        ->prefix('pemeriksaan')->name('review.')
        ->group(function () {
            Route::get('/', [ReviewController::class, 'index'])->name('index');
            Route::get('{assessment}', [ReviewController::class, 'show'])->whereNumber('assessment')->name('show');
            Route::post('{assessment}/setujui', [ReviewController::class, 'approve'])->name('approve');
            Route::post('{assessment}/kembalikan', [ReviewController::class, 'returnForRevision'])->name('return');
            Route::post('{assessment}/finalisasi', [ReviewController::class, 'finalize'])
                ->middleware('permission:assessments.finalize')->name('finalize');
        });

    // ---------------- Penilai: Kapolres, Wakapolres, Kabag, Kasat, Kasi, Kanit ----------------
    Route::middleware('role:'.implode('|', RoleName::canAssess()))
        ->prefix('penilai')->name('assessor.')
        ->group(function () {
            Route::get('/beranda', [DashboardController::class, 'assessor'])->name('home');

            Route::get('penilaian', [AssessmentController::class, 'index'])->name('assessments.index');
            Route::get('penilaian/riwayat', [AssessmentController::class, 'history'])->name('assessments.history');
            Route::get('penilaian/{assessment}', [AssessmentController::class, 'show'])->whereNumber('assessment')->name('assessments.show');
            Route::post('penilaian/{assessment}/kirim', [AssessmentController::class, 'submit'])->whereNumber('assessment')->name('assessments.submit');

            Route::get('formulir/{period}/{assessee}', [AssessmentController::class, 'form'])->name('assessments.form');
            Route::post('formulir/{period}/{assessee}', [AssessmentController::class, 'save'])->name('assessments.save');

            // Hasil penilaian pribadi (penilai juga dinilai atasannya)
            Route::get('hasil-saya', [MemberAssessmentController::class, 'index'])->name('results.index');
            Route::get('hasil-saya/{assessment}', [MemberAssessmentController::class, 'show'])->whereNumber('assessment')->name('results.show');
        });

    // ---------------- Anggota ----------------
    Route::middleware('role:'.RoleName::Anggota->value)
        ->prefix('anggota')->name('member.')
        ->group(function () {
            Route::get('/beranda', [DashboardController::class, 'member'])->name('home');
            Route::get('hasil', [MemberAssessmentController::class, 'index'])->name('results.index');
            Route::get('hasil/{assessment}', [MemberAssessmentController::class, 'show'])->whereNumber('assessment')->name('results.show');
        });
});
