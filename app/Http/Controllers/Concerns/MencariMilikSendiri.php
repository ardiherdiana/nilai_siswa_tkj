<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\Supervisi;
use Illuminate\Http\Request;

/**
 * Semua data Nilai Siswa TKJ & Supervisi Guru milik satu akun. Data akun lain dijawab 404 (bukan 403) supaya keberadaannya tidak bocor.
 */
trait MencariMilikSendiri
{
    protected function kelasMilik(Request $request, string $id): Kelas
    {
        return Kelas::where('userId', $request->user()->id)->find($id)
            ?? abort(404, 'Kelas tidak ditemukan');
    }

    protected function siswaMilik(Request $request, string $id): Siswa
    {
        return Siswa::whereHas('kelas', fn ($q) => $q->where('userId', $request->user()->id))->find($id)
            ?? abort(404, 'Siswa tidak ditemukan');
    }

    protected function supervisiMilik(Request $request, string $id): Supervisi
    {
        return Supervisi::where('userId', $request->user()->id)->find($id)
            ?? abort(404, 'Supervisi tidak ditemukan');
    }
}
