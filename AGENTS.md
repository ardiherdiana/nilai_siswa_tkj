Ini aplikasi web Laravel 13 + Inertia 3 + React 19 + Tailwind 4 + shadcn (`base-nova`) untuk Nilai Siswa TKJ & Supervisi Guru, pencatatan nilai siswa dan lembar supervisi guru SMK Budi Mulia Karawang. Basis data dan akun sendiri. Tampilannya mengikuti design system absensi.

Panduan teknis (konvensi, alur, jebakan) ada di [CLAUDE.md](CLAUDE.md); cara menjalankan dan deploy di [README.md](README.md).

## Versi paket berubah, jangan percaya ingatan

Laravel, Inertia, React, Tailwind, Vite, dan Base UI sering memindahkan atau mengganti API antar versi mayor. Sebelum menulis kode yang menyentuhnya:

1. Baca versi di `composer.json` dan `package.json`.
2. Baca dokumentasi versi itu, bukan versi terbaru secara umum.
3. Yang sudah berbeda dari kebiasaan lama: Tailwind 4 dikonfigurasi di CSS (`resources/js/index.css`), bukan `tailwind.config.js`; `composer dev` menjalankan `php artisan dev`; model memakai atribut `#[Fillable]`/`#[Hidden]`; komponen shadcn `base-nova` memakai prop `render` (bukan `asChild`) dan menu memakai `onClick` (bukan `onSelect`); TypeScript 6 dengan `erasableSyntaxOnly` dan `verbatimModuleSyntax`; `useForm` Inertia 3 menganggap isian terakhir sebagai nilai awal setelah simpan berhasil.

## Perintah

```bash
composer test                # PHPUnit terhadap MySQL `nilai_siswa_tkj_testing`
./vendor/bin/pint            # format PHP
npx tsc -b                   # cek tipe frontend
npm run lint                 # ESLint (peringatan di components/ui adalah bawaan shadcn)
npm run build                # tsc -b lalu vite build
composer dev                 # server + Vite (nyalakan hanya saat perlu, lalu matikan)
```

Jalankan `pint`, `composer test`, `npx tsc -b`, `npm run lint`, dan `npm run build` sebelum menyatakan pekerjaan selesai. Verifikasi lewat perintah itu, bukan lewat dev server yang dibiarkan menyala.

## Aturan

- **Jangan membiarkan dev server menyala.** Bila perlu memeriksa di browser, nyalakan sebentar lalu matikan.
- **Jangan membaca atau mencetak isi `.env*`.** Cari kuncinya saja (mis. `grep ^APP_ENV= .env`).
- **Jangan menjalankan `migrate:fresh`, `migrate:refresh`, atau `db:wipe`** pada basis data yang berisi data nyata tanpa izin pemilik. `db:seed` menolak berjalan di produksi.
- **Tampilan 1:1 dengan absensi.** `resources/js/index.css`, `components/ui/*`, `theme-provider`, `theme-toggle`, `data-pagination`, dan `layouts/dashboard-layout` disalin dari `../absensi/resources/js`; jangan diubah. Warna hanya lewat token Tailwind, dua tema (terang dan gelap) harus terbaca, teks antarmuka dan rute berbahasa Indonesia.
- **Pesan galat** untuk pengguna berbahasa Indonesia, ditulis di tempat kode melempar `abort()` atau validasi (`lang/id/validation.php` untuk pesan bawaan Laravel).
- **Jangan meng-commit** `.env*` selain `.env.example`, dump basis data, atau arsip; commit hanya bila diminta. `.gitignore` bawaan Laravel di `storage/`, `bootstrap/cache/`, dan `database/` dipertahankan apa adanya; aturan ignore baru masuk ke `.gitignore` root saja.
- **Dokumen di `md/` dan `README.md` diperbarui sekaligus atas permintaan pemilik**, bukan tiap perubahan kode kecil.
