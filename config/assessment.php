<?php

return [
    // Total bobot kriteria yang disyaratkan agar periode bisa dibuka.
    'weight_total' => 100,

    // CONTOH ambang predikat. Dapat diubah sesuai kebijakan; bukan ketentuan resmi Polri.
    'grades' => [
        ['min' => 90, 'label' => 'Sangat Baik'],
        ['min' => 75, 'label' => 'Baik'],
        ['min' => 60, 'label' => 'Cukup'],
        ['min' => 0, 'label' => 'Kurang'],
    ],

    // CONTOH poin per predikat saat penilaian difinalisasi. Dapat diubah; bukan ketentuan resmi Polri.
    'points' => [
        'by_grade' => [
            'Sangat Baik' => 50,
            'Baik' => 30,
            'Cukup' => 10,
            'Kurang' => 0,
        ],
    ],

    'note_image_max_kb' => 4096,
    // Batas ukuran bukti pendukung (KB).
    'evidence_max_kb' => 2048,

    // Daftar pangkat untuk dropdown dan validasi. Sesuaikan bila perlu.
    'ranks' => [
        'Bharada', 'Bharatu', 'Bharaka', 'Abripda', 'Abriptu', 'Abrippol',
        'Bripda', 'Briptu', 'Brigpol', 'Bripka', 'Aipda', 'Aiptu',
        'Ipda', 'Iptu', 'AKP', 'Kompol', 'AKBP', 'Kombes Pol',
        'Brigjen Pol', 'Irjen Pol', 'Komjen Pol', 'Jenderal Pol',
    ],
];
