<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\Supervisi;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        // Ringkasan tahun ajaran terbaru saja: menjumlah semua tahun ajaran akan menyesatkan.
        $tahunAjaran = Kelas::where('userId', $user->id)->max('tahunAjaran');
        $kelasIds = $tahunAjaran === null
            ? collect()
            : Kelas::where('userId', $user->id)->where('tahunAjaran', $tahunAjaran)->pluck('id');

        $supervisi = Supervisi::where('userId', $user->id);

        return Inertia::render('dashboard-page', [
            'nilai' => [
                'tahunAjaran' => $tahunAjaran,
                'tahunAjaranSekarang' => Kelas::tahunAjaranSekarang(),
                'jumlahKelas' => $kelasIds->count(),
                'jumlahSiswa' => $kelasIds->isEmpty() ? 0 : Siswa::whereIn('kelasId', $kelasIds)->count(),
            ],
            'supervisi' => [
                'total' => (clone $supervisi)->count(),
                'terbaru' => (clone $supervisi)->orderByDesc('tanggal')->orderByDesc('createdAt')->limit(3)
                    ->get(['id', 'guruDinilai', 'tanggal'])
                    ->map(fn (Supervisi $s) => [
                        'id' => $s->id,
                        'guruDinilai' => $s->guruDinilai,
                        'tanggal' => $s->tanggal->format('Y-m-d'),
                    ]),
            ],
            'akun' => $user->isAdmin()
                ? ['total' => User::count(), 'aktif' => User::where('aktif', true)->count()]
                : null,
        ]);
    }
}
