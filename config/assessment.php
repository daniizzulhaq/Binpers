
<?php

return [
    // Total bobot kriteria yang disyaratkan agar periode bisa dibuka.
    'weight_total' => 100,

    // Contoh ambang predikat.
    // Dapat diubah sesuai kebijakan; bukan ketentuan resmi Polri.
    'grades' => [
        ['min' => 90, 'label' => 'Sangat Baik'],
        ['min' => 75, 'label' => 'Baik'],
        ['min' => 60, 'label' => 'Cukup'],
        ['min' => 0, 'label' => 'Kurang'],
    ],

    // Pilihan skor sekali ketuk pada form penilaian.
    // Dapat diubah sesuai kebijakan.
    'score_options' => [
        [
            'value' => 50,
            'label' => 'Sangat Rendah',
            'color' => 'danger',
            'hint' => 'Jauh di bawah harapan; sebagian besar indikator tidak terpenuhi.',
        ],
        [
            'value' => 60,
            'label' => 'Rendah',
            'color' => 'warning',
            'hint' => 'Sebagian indikator belum terpenuhi.',
        ],
        [
            'value' => 70,
            'label' => 'Cukup',
            'color' => 'secondary',
            'hint' => 'Memenuhi indikator dasar.',
        ],
        [
            'value' => 80,
            'label' => 'Tinggi',
            'color' => 'primary',
            'hint' => 'Memenuhi seluruh indikator dengan baik.',
        ],
        [
            'value' => 90,
            'label' => 'Sangat Tinggi',
            'color' => 'success',
            'hint' => 'Melampaui indikator dan layak dicontoh.',
        ],
    ],

    // Contoh poin per predikat saat penilaian difinalisasi.
    // Dapat diubah sesuai kebijakan; bukan ketentuan resmi Polri.
    'points' => [
        'by_grade' => [
            'Sangat Baik' => 50,
            'Baik' => 30,
            'Cukup' => 10,
            'Kurang' => 0,
        ],
    ],

    // Batas ukuran foto catatan kinerja (KB).
    'note_image_max_kb' => 4096,

    // Batas ukuran bukti pendukung (KB).
    'evidence_max_kb' => 2048,

    // Daftar pangkat untuk dropdown dan validasi.
    'ranks' => [
        'Bharada',
        'Bharatu',
        'Bharaka',
        'Abripda',
        'Abriptu',
        'Abrippol',
        'Bripda',
        'Briptu',
        'Brigpol',
        'Bripka',
        'Aipda',
        'Aiptu',
        'Ipda',
        'Iptu',
        'AKP',
        'Kompol',
        'AKBP',
        'Kombes Pol',
        'Brigjen Pol',
        'Irjen Pol',
        'Komjen Pol',
        'Jenderal Pol',
    ],
];
