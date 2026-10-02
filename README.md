# Nilai Siswa TKJ & Supervisi Guru

Aplikasi web (Laravel 13 + Inertia + React) untuk guru SMK Budi Mulia Karawang, dengan basis data dan akun sendiri. Tampilan mengikuti design system absensi (token warna, komponen shadcn, sidebar, tema terang/gelap).

- **Nilai siswa**: kelas per tahun ajaran, daftar siswa, 10 nilai tes teori + 10 nilai praktik per siswa. Import daftar siswa (dan nilai) dari Excel berformat template, ekspor ke Excel dan PDF.
- **Supervisi guru**: lembar "Instrumen Observasi Implementasi dan Refleksi Perencanaan Pembelajaran" (15 butir + 3 refleksi), ekspor ke Word dan PDF.
- **Akun**: login dengan username + password. Tidak ada pendaftaran; admin membuat, menonaktifkan, dan mengganti password akun di halaman **Akun**. Setiap akun hanya melihat data miliknya.

Ekspor mengisi template resmi sekolah (`resources/aplikasi/format-nilai-siswa.xlsx`, `format-supervisi-guru.docx`); PDF dibuat dengan dompdf (tanpa LibreOffice). Panduan untuk pengembang dan asisten pemrograman ada di [CLAUDE.md](CLAUDE.md) dan [AGENTS.md](AGENTS.md).

## Menjalankan (lokal)

Butuh PHP 8.3+ (dev: 8.4), Composer, Node 22+, dan MySQL.

```bash
composer install
npm install --ignore-scripts
cp .env.example .env         # lalu: php artisan key:generate
# buat basis data (root tanpa sandi seperti Laragon): CREATE DATABASE nilai_siswa_tkj; CREATE DATABASE nilai_siswa_tkj_testing;
php artisan migrate
php artisan db:seed          # akun contoh: admin / admin123 dan guru / guru123 (hanya untuk lokal)
composer dev                 # server + Vite; matikan setelah selesai
```

Pemeriksaan sebelum commit:

```bash
./vendor/bin/pint
composer test                # PHPUnit ke MySQL nilai_siswa_tkj_testing
npx tsc -b && npm run lint
npm run build
```

## Akun admin pertama

Tidak ada pendaftaran. Di lokal, `php artisan db:seed` membuat `admin` / `admin123` dan `guru` / `guru123`. Di produksi (seeder menolak berjalan), buat admin pertama dengan:

```bash
php artisan nilai:buat-admin <username> --name="Nama Admin"   # sandi ditanyakan tanpa tampil di layar
php artisan nilai:buat-admin <username> --reset               # pemulihan: sandi baru, jadi admin aktif
```

Setelah itu masuk lewat halaman login dan tambahkan akun guru di menu **Akun**.

## Deploy (Hostinger, shared hosting)

1. Basis data MySQL baru untuk Nilai Siswa TKJ & Supervisi Guru; isi `.env` dari `.env.example`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://<domain>`, `DB_*`, `SESSION_SECURE_COOKIE=true`, `INERTIA_ENCRYPT_HISTORY=true`, `SESSION_ENCRYPT=true`. Aplikasi memakai sesi dan cache berkas, antrean `sync`.
2. Build di laptop lalu unggah: `composer install --no-dev --optimize-autoloader` dan `npm run build` (hasilnya di `public/build`; jangan lupa unggah `vendor/` dan `public/build/`). Akar dokumen web = folder `public/`. PHP 8.3+ dengan ekstensi `gd`, `zip`, `xml`, `mbstring`, `pdo_mysql`.
3. `php artisan migrate --force`, lalu `php artisan nilai:buat-admin <username>` (lihat "Akun admin pertama"). Jangan menjalankan `db:seed` di produksi (menolak sendiri).
4. `php artisan config:cache route:cache view:cache` setelah `.env` final. Pastikan `storage/` dan `bootstrap/cache/` bisa ditulis.

## Catatan

- `resources/aplikasi/format-nilai-siswa.xlsx` masih memuat contoh 273 nama siswa dari template awal; dipakai tes impor dan tidak ikut ke hasil ekspor.
- Situs ini bisa ditambahkan ke layar utama HP (manifest dan ikon sudah ada); tidak ada service worker maupun mode offline.
- Dokumen rinci di `md/` (PRD, arsitektur, dll.) belum ditulis.
