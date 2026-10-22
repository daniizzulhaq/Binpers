<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Enums\UnitType;
use App\Models\OrganizationalUnit;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Akun + personel + penempatan dari daftar personel Polres (tempel dari Excel).
 *
 * File data: database/seeders/data/personel.txt (tidak di-commit karena berisi data asli).
 * Kolom: NO, NAMA, PANGKAT, NRP, JABATAN (dipisah tab atau 3+ spasi); baris berisi satu
 * teks saja dianggap judul bagian/satuan/seksi/polsek/polpos.
 *
 * Username = NRP. Kata sandi awal = PERSONEL_DEFAULT_PASSWORD di .env, hanya untuk akun baru.
 *
 * php artisan db:seed --class=PersonelPolresSeeder
 */
class PersonelPolresSeeder extends Seeder
{
    private const DATA_FILE = 'seeders/data/personel.txt';

    /** Kata kunci sub-unit per jenis induk: [regex, kode, nama]. Urutan menentukan prioritas. */
    private const SUBUNITS = [
        'SAT RESKRIM' => [
            ['/TIPITER/', 'IDIK3', 'UNIT IDIK 3'],
            ['/IDIK\s*(\d)/', 'IDIK$1', 'UNIT IDIK $1'],
        ],
        'SAT RESNARKOBA' => [
            ['/IDIK\s*(\d)/', 'IDIK$1', 'UNIT IDIK $1'],
        ],
        'SAT INTELKAM' => [
            ['/\b[KB]ANIT\s*(\d)\b/', 'UNIT$1', 'UNIT $1'],
        ],
        'SAT SAMAPTA' => [
            ['/DALMAS\s*(\d)/', 'DALMAS$1', 'UNIT DALMAS $1'],
            ['/TURJAWALI/', 'TURJAWALI', 'UNIT TURJAWALI'],
            ['/PAMOBVIT/', 'PAMOBVIT', 'UNIT PAMOBVIT'],
        ],
        'SAT LANTAS' => [
            ['/TURJAGWALI/', 'TURJAGWALI', 'UNIT TURJAGWALI'],
            ['/REG\s*IDENT/', 'REGIDENT', 'UNIT REGIDENT'],
            ['/GAKKUM/', 'GAKKUM', 'UNIT GAKKUM'],
            ['/KAMSEL/', 'KAMSEL', 'UNIT KAMSEL'],
        ],
        'SAT BINMAS' => [
            ['/BINTIBSOS/', 'BINTIBSOS', 'UNIT BINTIBSOS'],
            ['/KANITBHABINKAMTIBMAS/', 'BHABIN', 'UNIT BHABINKAMTIBMAS'],
            ['/BINKAMSA/', 'BINKAMSA', 'UNIT BINKAMSA'],
            ['/BINPOLMAS/', 'BINPOLMAS', 'UNIT BINPOLMAS'],
        ],
        'SIPROPAM' => [
            ['/PROVOS/', 'PROVOS', 'UNIT PROVOS'],
            ['/PAMINAL/', 'PAMINAL', 'UNIT PAMINAL'],
        ],
        'SPKT' => [
            ['/PAMAPTA\s*(\d)/', 'SPKT$1', 'SPKT $1'],
            ['/BANIT\s*(\d)\s*SPKT/', 'SPKT$1', 'SPKT $1'],
            ['/BANIT\s*SPKT\s*(\d)/', 'SPKT$1', 'SPKT $1'],
        ],
        'POLSEK' => [
            ['/^BHABINKAMTIBMAS/', null, null],
            ['/SPKT\s*(\d)/', 'SPKT$1', 'SPKT $1'],
            ['/SIUM/', 'SIUM', 'SIUM'],
            ['/PROPAM/', 'PROPAM', 'UNIT PROPAM'],
            ['/INTELKAM/', 'INTELKAM', 'UNIT INTELKAM'],
            ['/RESKRIM/', 'RESKRIM', 'UNIT RESKRIM'],
            ['/BINMAS/', 'BINMAS', 'UNIT BINMAS'],
            ['/SAMAPTA/', 'SAMAPTA', 'UNIT SAMAPTA'],
        ],
    ];

    /** Jabatan anggota (bukan kepala) berdasarkan awalan teks jabatan. */
    private const MEMBER_POSITIONS = [
        'BHABINKAMTIBMAS' => 'Bhabinkamtibmas',
        'BAMIN' => 'Bamin',
        'BANUM' => 'Banum',
        'BAUR' => 'Baur',
        'BANIT' => 'Banit',
        'BA ' => 'Banit',
        'PAURMIN' => 'Paurmin',
        'PAUR' => 'Paur',
        'PASIAGA' => 'Pasiaga',
        'KAUR' => 'Kaur',
        'KASUBNIT' => 'Kasubnit',
        'KASUBSI' => 'Kasubsi',
        'KASI KASUBSI' => 'Kasubsi',
        'SUBBAG' => 'Kasubbag',
    ];

    /** Jabatan anggota yang punya role sendiri; jabatan lain memakai role Anggota. */
    private const MEMBER_ROLES = [
        'Kasubbag' => RoleName::Kasubbag,
        'Kasubsi' => RoleName::Kasubsi,
        'Kaur' => RoleName::Kaur,
        'Paur' => RoleName::Paur,
        'Paurmin' => RoleName::Paur,
        'Pasiaga' => RoleName::Paur,
        'Kasubnit' => RoleName::Kasubnit,
    ];

    private array $units = [];

    private array $positions = [];

    private int $created = 0;

    private int $updated = 0;

    public function run(): void
    {
        $path = database_path(self::DATA_FILE);

        if (! is_file($path)) {
            $this->command?->warn("File data personel tidak ditemukan: {$path}");

            return;
        }

        $rows = $this->parse(file($path, FILE_IGNORE_NEW_LINES));
        $passwordHash = Hash::make(env('PERSONEL_DEFAULT_PASSWORD', 'polres123'));

        DB::transaction(function () use ($rows, $passwordHash) {
            foreach ($rows as $row) {
                $this->seedPerson($row, $passwordHash);
            }
        });

        $this->command?->info("Personel: {$this->created} akun baru, {$this->updated} diperbarui, ".count($this->units).' unit.');
    }

    /** @return array<int, array{section: string, name: string, rank: string, nrp: string, title: string}> */
    private function parse(array $lines): array
    {
        $rows = [];
        $seen = [];
        $section = null;

        foreach ($lines as $i => $line) {
            $cells = array_values(array_filter(
                array_map('trim', preg_split('/\t|\s{3,}/', trim($line))),
                fn ($c) => $c !== ''
            ));

            if (count($cells) === 1 && ! ctype_digit($cells[0])) {
                $section = $this->squish($cells[0]);

                continue;
            }

            if (count($cells) < 5 || ! ctype_digit($cells[0]) || ctype_digit($cells[1]) || $section === null) {
                continue; // header tabel, nomor kolom/halaman, baris kosong
            }

            $nrp = preg_replace('/\D/', '', $cells[3]);

            if ($nrp === '' || isset($seen[$nrp])) {
                $this->command?->warn('Baris '.($i + 1).": NRP kosong/ganda ({$cells[3]}), dilewati.");

                continue;
            }

            $seen[$nrp] = true;
            $rows[] = [
                'section' => $section,
                'name' => $this->squish($cells[1]),
                'rank' => $this->squish($cells[2]),
                'nrp' => $nrp,
                'title' => $this->squish($cells[4]),
            ];
        }

        return $rows;
    }

    private function seedPerson(array $row, string $passwordHash): void
    {
        [$unit, $role, $positionName] = $this->placement($row['section'], $row['title']);

        $owner = Personnel::with('user')->where('nrp', $row['nrp'])->first()?->user;

        // NRP sudah dipakai akun lain (dibuat manual / data demo): jangan diambil alih.
        if ($owner && $owner->username !== $row['nrp']) {
            $this->command?->warn("NRP {$row['nrp']} ({$row['name']}) sudah dipakai akun '{$owner->username}', dilewati.");

            return;
        }

        $user = $owner ?? User::where('username', $row['nrp'])->first();

        if ($user) {
            $user->update(['name' => $row['name'], 'is_active' => true]);
            $this->updated++;
        } else {
            $user = User::create([
                'name' => $row['name'],
                'username' => $row['nrp'],
                'password' => $passwordHash,
                'is_active' => true,
            ]);
            $this->created++;
        }

        // Pertahankan role HRD bila sudah diberikan lewat aplikasi.
        $roles = [$role->value];
        if ($user->hasRole(RoleName::Hrd->value)) {
            $roles[] = RoleName::Hrd->value;
        }
        $user->syncRoles($roles);

        $personnel = Personnel::updateOrCreate(['user_id' => $user->id], [
            'nrp' => $row['nrp'],
            'rank' => $this->rank($row['rank']),
        ]);

        $position = $this->positions[$positionName] ??= Position::firstOrCreate(['name' => $positionName], ['is_active' => true]);
        $current = $personnel->currentAssignment;
        $notes = 'Jabatan: '.$row['title'];

        if ($current && $current->organizational_unit_id == $unit->id && $current->position_id == $position->id) {
            $current->update(['notes' => $notes]);

            return;
        }

        $current?->update(['end_date' => now()->toDateString()]);

        $personnel->assignments()->create([
            'organizational_unit_id' => $unit->id,
            'position_id' => $position->id,
            'start_date' => now()->toDateString(),
            'notes' => $notes,
        ]);
    }

    /** @return array{0: OrganizationalUnit, 1: RoleName, 2: string} unit penempatan, role, nama jabatan */
    private function placement(string $section, string $title): array
    {
        $polres = $this->polres();
        $job = $this->squish(preg_replace('/^PS\.?\s*/', '', $title));

        if ($section === 'PIMPINAN') {
            return str_starts_with($job, 'WAKAPOLRES')
                ? [$polres, RoleName::Wakapolres, 'Wakapolres']
                : [$polres, RoleName::Kapolres, 'Kapolres'];
        }

        $type = match (true) {
            str_starts_with($section, 'BAG ') => UnitType::Bagian,
            str_starts_with($section, 'SAT ') => UnitType::Satuan,
            str_starts_with($section, 'POLSEK ') => UnitType::Polsek,
            str_starts_with($section, 'POLPOS ') => UnitType::Unit,
            default => UnitType::Seksi,
        };
        $parent = $this->unit(str_replace(' ', '', $section), $section, $type, $polres);

        // Kepala bagian/satuan/seksi/polsek/polpos: ditempatkan di unit induk.
        $head = match (true) {
            $type === UnitType::Bagian && str_starts_with($job, 'KABAG') => [RoleName::Kabag, 'Kepala Bagian'],
            $type === UnitType::Satuan && str_starts_with($job, 'KASAT') => [RoleName::Kasat, 'Kepala Satuan'],
            $type === UnitType::Seksi && str_starts_with($job, 'KASI') && ! str_contains($job, 'KASUBSI') => [
                in_array($section, ['SIWAS', 'SIPROPAM'], true) ? RoleName::KasiPengawas : RoleName::Kasi,
                'Kepala Seksi',
            ],
            $type === UnitType::Polsek && str_starts_with($job, 'KAPOLSEK') => [RoleName::Kapolsek, 'Kapolsek'],
            $type === UnitType::Polsek && str_starts_with($job, 'WAKAPOLSEK') => [RoleName::Wakapolsek, 'Wakapolsek'],
            $type === UnitType::Unit && str_starts_with($job, 'KAPOLPOS') => [RoleName::Kapolpos, 'Kapolpos'],
            default => null,
        };

        if ($head) {
            return [$parent, $head[0], $head[1]];
        }

        $unit = $this->subunit($section, $type, $job, $parent) ?? $parent;

        if ($unit !== $parent && str_starts_with($job, 'PAMAPTA')) {
            return [$unit, RoleName::Pamapta, 'Pamapta'];
        }

        // Kepala sub-unit lain (Kanit, Kasium & Ka SPKT polsek) memakai role Kanit.
        if ($unit !== $parent && preg_match('/^(KANIT|PANIT|KASPKT|KASIUM)/', $job, $m)) {
            $position = match (true) {
                $m[1] === 'KASPKT' => 'Ka SPKT',
                $m[1] === 'KASIUM' => 'Kasium',
                // Sama dengan jabatan yang dikecualikan aturan Kapolsek (AssessmentRuleSeeder).
                $type === UnitType::Polsek && str_contains($job, 'PROPAM') => 'Kanit Provost',
                default => 'Kepala Unit',
            };

            return [$unit, RoleName::Kanit, $position];
        }

        $position = $this->memberPosition($job);

        return [$unit, self::MEMBER_ROLES[$position] ?? RoleName::Anggota, $position];
    }

    private function subunit(string $section, UnitType $type, string $job, OrganizationalUnit $parent): ?OrganizationalUnit
    {
        $patterns = self::SUBUNITS[$type === UnitType::Polsek ? 'POLSEK' : $section] ?? [];
        if ($type === UnitType::Polsek) {
            $job = trim(str_replace($section, '', $job)); // nama polsek bisa memuat kata kunci
        }

        foreach ($patterns as [$regex, $code, $name]) {
            if (! preg_match($regex, $job, $m)) {
                continue;
            }

            if ($code === null) {
                return null;
            }

            $n = $m[1] ?? '';

            return $this->unit(
                $parent->code.'-'.str_replace('$1', $n, $code),
                str_replace('$1', $n, $name).' '.$section,
                UnitType::Unit,
                $parent
            );
        }

        return null;
    }

    private function memberPosition(string $job): string
    {
        foreach (self::MEMBER_POSITIONS as $prefix => $position) {
            if (str_starts_with($job, $prefix)) {
                return $position;
            }
        }

        return 'Anggota';
    }

    private function polres(): OrganizationalUnit
    {
        return $this->units['POLRES'] ??= OrganizationalUnit::firstOrCreate(
            ['code' => 'POLRES'],
            ['name' => 'POLRES', 'type' => UnitType::Polres, 'is_active' => true]
        );
    }

    private function unit(string $code, string $name, UnitType $type, OrganizationalUnit $parent): OrganizationalUnit
    {
        if (strlen($code) > 30) {
            throw new \RuntimeException("Kode unit terlalu panjang: {$code}");
        }

        return $this->units[$code] ??= OrganizationalUnit::updateOrCreate(
            ['code' => $code],
            ['name' => $name, 'type' => $type, 'parent_id' => $parent->id, 'is_active' => true]
        );
    }

    /** Samakan penulisan pangkat dengan daftar di config (mis. "BRIPDA" → "Bripda"). */
    private function rank(string $rank): string
    {
        foreach (config('assessment.ranks') as $known) {
            if (strcasecmp($known, $rank) === 0) {
                return $known;
            }
        }

        return Str::title(Str::lower($rank));
    }

    private function squish(string $value): string
    {
        return Str::squish(str_replace("\u{00A0}", ' ', $value));
    }
}
