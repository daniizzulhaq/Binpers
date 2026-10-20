<?php

namespace App\Models;

use App\Enums\RoleName;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, Auditable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function personnel(): HasOne
    {
        return $this->hasOne(Personnel::class);
    }

    /** Nama route beranda sesuai role, atau null jika belum punya role. */
    public function homeRoute(): ?string
    {
        return match (true) {
            $this->hasRole(RoleName::Hrd->value) => 'hrd.dashboard',
            $this->hasAnyRole(RoleName::leaders()) => 'leader.dashboard',
            $this->hasAnyRole(RoleName::assessors()) => 'assessor.home',
            $this->hasRole(RoleName::Anggota->value) => 'member.home',
            default => null,
        };
    }

    public function roleLabel(): string
    {
        $name = $this->getRoleNames()->first();

        return $name
            ? (RoleName::tryFrom($name)?->label() ?? $name)
            : 'Tanpa role';
    }

    public function isAssessor(): bool
    {
        return $this->hasAnyRole(RoleName::assessors());
    }

    /** Boleh mengisi penilaian: Kapolres, Wakapolres, Kabag, Kasat, Kasi, Kanit. */
    public function canAssess(): bool
    {
        return $this->hasAnyRole(RoleName::canAssess());
    }

    /** HRD, Kapolres, dan Wakapolres memakai layout desktop; lainnya mobile-first. */
    public function usesDesktopLayout(): bool
    {
        return $this->hasAnyRole([RoleName::Hrd->value, ...RoleName::leaders()]);
    }

    public function layout(): string
    {
        return $this->usesDesktopLayout() ? 'layouts.app' : 'layouts.mobile';
    }
}
