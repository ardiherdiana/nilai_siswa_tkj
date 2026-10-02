<?php

/*
|--------------------------------------------------------------------------
| Nilai Siswa TKJ & Supervisi Guru: nilai siswa dan supervisi guru
|--------------------------------------------------------------------------
| Akun dibuat admin di halaman Akun; kelas, siswa, nilai, dan supervisi terikat ke `users.id` lewat kolom `userId`.
*/

return [
    // Template resmi yang diisi apa adanya (bukan dibuat ulang) supaya hasil export 1:1.
    'templates' => [
        'nilai' => resource_path('aplikasi/format-nilai-siswa.xlsx'),
        'supervisi' => resource_path('aplikasi/format-supervisi-guru.docx'),
    ],

    'kota' => 'Karawang',
    'nama_sekolah' => 'SMK BUDI MULIA KARAWANG',
];
