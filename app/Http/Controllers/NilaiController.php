<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\MencariMilikSendiri;
use App\Models\Nilai;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class NilaiController extends Controller
{
    use MencariMilikSendiri;

    /**
     * Simpan sel nilai yang berubah untuk satu siswa dalam satu transaksi: `nilai` berisi kolom (`tes1`..`tes10`,
     * `praktik1`..`praktik10`) menuju angka 0-100, atau `null` untuk mengosongkan sel. Kolom yang tidak
     * dikirim tidak disentuh.
     */
    public function update(Request $request, string $siswa)
    {
        $siswa = $this->siswaMilik($request, $siswa);

        $tidakDikenal = array_diff(array_keys((array) $request->input('nilai')), Nilai::kolom());
        if ($tidakDikenal !== []) {
            throw ValidationException::withMessages(['nilai' => 'Kolom nilai tidak dikenal']);
        }

        $data = $request->validate([
            'nilai' => ['required', 'array', 'min:1', 'max:'.count(Nilai::kolom())],
            'nilai.*' => ['nullable', 'numeric', 'between:0,100'],
        ], [], ['nilai' => 'Nilai', 'nilai.*' => 'Nilai']);

        DB::transaction(function () use ($siswa, $data) {
            $nilai = Nilai::firstOrNew(['siswaId' => $siswa->id]);
            foreach ($data['nilai'] as $kolom => $angka) {
                $nilai->{$kolom} = $angka === null ? null : round((float) $angka, 2);
            }
            $nilai->save();
        });

        return back()->with('toast', ['type' => 'success', 'message' => 'Nilai tersimpan']);
    }
}
