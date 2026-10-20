<?php

namespace App\Services;

use App\Enums\RoleName;
use App\Models\AssessmentAuthorization;
use App\Models\AssessmentRule;
use App\Models\OrganizationalUnit;
use App\Models\Personnel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AssessorScopeService
{
    /** @var array<int, int[]> cache turunan unit per request */
    private array $unitCache = [];

    /** Aturan aktif sesuai role penilai. */
    public function rulesFor(Personnel $assessor): Collection
    {
        $roles = $assessor->user?->getRoleNames() ?? collect();

        return $roles->isEmpty()
            ? collect()
            : AssessmentRule::active()->whereIn('assessor_role', $roles)->get();
    }

    /** Semua personel yang boleh dinilai: dari aturan + kewenangan manual HRD. */
    public function assesseeQuery(Personnel $assessor): Builder
    {
        return $this->build($assessor, false);
    }

    /** Personel yang WAJIB dinilai (aturan wajib saja). */
    public function mandatoryQuery(Personnel $assessor): Builder
    {
        return $this->build($assessor, true);
    }

    public function canAssess(Personnel $assessor, Personnel $assessee): bool
    {
        return $assessor->id !== $assessee->id
            && $this->assesseeQuery($assessor)->whereKey($assessee->id)->exists();
    }

    public function isMandatory(Personnel $assessor, Personnel $assessee): bool
    {
        return $this->mandatoryQuery($assessor)->whereKey($assessee->id)->exists();
    }

    // ---------------------------------------------------------------

    private function build(Personnel $assessor, bool $mandatoryOnly): Builder
    {
        $assessor->loadMissing(['user', 'currentAssignment']);

        $rules = $this->rulesFor($assessor)
            ->when($mandatoryOnly, fn ($c) => $c->where('is_mandatory', true));

        $manualIds = $mandatoryOnly
            ? collect()
            : AssessmentAuthorization::effective()->where('assessor_id', $assessor->id)->pluck('assessee_id');

        return Personnel::query()
            ->whereKeyNot($assessor->id)
            ->whereHas('user', fn ($u) => $u->where('is_active', true))
            ->whereDoesntHave('user.roles', fn ($r) => $r->where('name', RoleName::Hrd->value))
            ->where(function (Builder $w) use ($rules, $assessor, $manualIds) {
                $w->whereRaw('1 = 0');

                foreach ($rules as $rule) {
                    $w->orWhere(fn (Builder $q) => $this->applyRule($q, $rule, $assessor));
                }

                if ($manualIds->isNotEmpty()) {
                    $w->orWhereIn('personnel.id', $manualIds);
                }
            });
    }

    private function applyRule(Builder $q, AssessmentRule $rule, Personnel $assessor): void
    {
        $targets = $rule->target_roles ?? [];

        if (! in_array('*', $targets, true)) {
            $q->whereHas('user.roles', fn ($r) => $r->whereIn('name', $targets));
        }

        if ($rule->scope === AssessmentRule::SCOPE_UNIT) {
            $unitId = $assessor->currentAssignment?->organizational_unit_id;

            if (! $unitId) {
                $q->whereRaw('1 = 0');

                return;
            }

            $ids = $this->unitCache[$unitId] ??= OrganizationalUnit::descendantIdsOf($unitId);
            $q->whereHas('currentAssignment', fn ($a) => $a->whereIn('organizational_unit_id', $ids));
        }

        if (! empty($rule->excluded_position_ids)) {
            $q->whereDoesntHave('currentAssignment', fn ($a) => $a->whereIn('position_id', $rule->excluded_position_ids));
        }
    }
}
