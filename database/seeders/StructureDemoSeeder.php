<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Enums\UnitType;
use App\Models\OrganizationalUnit;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StructureDemoSeeder extends Seeder
{
    public function run(): void
    {
        $polres = OrganizationalUnit::where('code', 'POLRES')->firstOrFail();
        $bagops = OrganizationalUnit::where('code', 'BAGOPS')->firstOrFail();

        $sipropam = $this->unit('SIPROPAM', 'Seksi Propam', UnitType::Seksi, $polres);
        $polsek = $this->unit('POLSEK-KOTA', 'Polsek Kota', UnitType::Polsek, $polres);
        $reskrimPs = $this->unit('PS-RESKRIM', 'Unit Reskrim Polsek Kota', UnitType::Unit, $polsek);
        $provostPs = $this->unit('PS-PROVOST', 'Unit Provost Polsek Kota', UnitType::Unit, $polsek);
        $subbagDalops = $this->unit('SUBBAG-DALOPS', 'Subbag Pengendalian Operasional', UnitType::Subbag, $bagops);

        $people = [
            ['kasipropam', 'Kasi Propam Demo', RoleName::KasiPengawas, '86010010', 'AKP', $sipropam, 'Kepala Seksi Propam'],
            ['kapolsek', 'Kapolsek Demo', RoleName::Kapolsek, '82010011', 'Kompol', $polsek, 'Kapolsek'],
            ['wakapolsek', 'Wakapolsek Demo', RoleName::Wakapolsek, '84010012', 'AKP', $polsek, 'Wakapolsek'],
            ['kanitreskrim', 'Kanit Reskrim Polsek Demo', RoleName::Kanit, '89010013', 'Ipda', $reskrimPs, 'Kanit Reskrim'],
            ['kanitprovost', 'Kanit Provost Polsek Demo', RoleName::Kanit, '90010014', 'Aiptu', $provostPs, 'Kanit Provost'],
            ['anggotapolsek', 'Anggota Polsek Demo', RoleName::Anggota, '98010015', 'Bripda', $reskrimPs, 'Anggota'],
            // Kasubag memakai role kanit; penyetujunya Kabag Ops (unit induk Subbag).
            ['kasubag', 'Kasubag Dalops Demo', RoleName::Kanit, '87010016', 'Iptu', $subbagDalops, 'Kasubag Dalops'],
            ['anggotasubbag', 'Anggota Subbag Dalops Demo', RoleName::Anggota, '99010017', 'Brigpol', $subbagDalops, 'Anggota'],
        ];

        foreach ($people as [$username, $name, $role, $nrp, $rank, $unit, $positionName]) {
            $user = User::updateOrCreate(['username' => $username], [
                'name' => $name,
                'email' => $username.'@polres.test',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]);
            $user->syncRoles([$role->value]);

            $personnel = Personnel::updateOrCreate(['user_id' => $user->id], [
                'nrp' => $nrp, 'rank' => $rank, 'gender' => 'L', 'join_date' => '2015-01-01',
            ]);

            if (! $personnel->currentAssignment) {
                $personnel->assignments()->create([
                    'organizational_unit_id' => $unit->id,
                    'position_id' => Position::firstOrCreate(['name' => $positionName], ['is_active' => true])->id,
                    'start_date' => '2024-01-01',
                ]);
            }
        }

        // Penyetuju struktural yang diharapkan (dipakai untuk uji alur persetujuan).
        $this->expectHead('kabag', $bagops, 'penyetuju Kasubag Dalops');
        $this->expectHead('kasat', OrganizationalUnit::where('code', 'SATRESKRIM')->first(), 'penyetuju Kanit Unit Idik I');
        $this->expectHead('kapolsek', $polsek, 'penyetuju Kanit Reskrim Polsek');
    }

    private function unit(string $code, string $name, UnitType $type, OrganizationalUnit $parent): OrganizationalUnit
    {
        return OrganizationalUnit::updateOrCreate(
            ['code' => $code],
            ['name' => $name, 'type' => $type, 'parent_id' => $parent->id, 'is_active' => true]
        );
    }

    /** Peringatkan bila akun kepala unit belum aktif atau penempatan aktifnya tidak di unit tersebut. */
    private function expectHead(string $username, ?OrganizationalUnit $unit, string $purpose): void
    {
        $user = User::with('personnel.currentAssignment')->where('username', $username)->first();

        $ok = $unit && $user?->is_active
            && $user->personnel?->currentAssignment?->organizational_unit_id === $unit->id;

        if (! $ok) {
            $this->command?->warn("Akun '{$username}' ({$purpose}) belum aktif atau tidak ditempatkan di ".($unit?->name ?? 'unit yang dimaksud').'.');
        }
    }
}
