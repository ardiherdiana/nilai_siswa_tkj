<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\MencariMilikSendiri;
use App\Models\Nilai;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SiswaController extends Controller
{
    use MencariMilikSendiri;

    /** Daftar siswa satu kelas (halaman `/kelas/{kelas}`). */
    public function index(Request $request, string $kelas)
    {
        $kelas = $this->kelasMilik($request, $kelas);

        return Inertia::render('nilai/siswa-page', [
            'kelas' => $kelas->toProps(),
            'totalNilai' => count(Nilai::kolom()),
            'siswa' => $kelas->siswa()->with('nilai')->get()->map(fn (Siswa $s) => [
                'id' => $s->id,
                'nomor' => $s->nomor,
                'nama' => $s->nama,
                'terisi' => count(array_filter($s->nilai?->toProps() ?? [], fn ($n) => $n !== null)),
            ]),
        ]);
    }

    /** Isi nilai satu siswa (halaman `/kelas/{kelas}/siswa/{siswa}`). */
    public function show(Request $request, string $kelas, string $siswa)
    {
        $kelas = $this->kelasMilik($request, $kelas);
        $siswa = $kelas->siswa()->with('nilai')->find($siswa) ?? abort(404, 'Siswa tidak ditemukan');

        return Inertia::render('nilai/nilai-page', [
            'kelas' => $kelas->toProps(),
            'siswa' => $siswa->toProps(),
        ]);
    }

    public function store(Request $request, string $kelas)
    {
        $kelas = $this->kelasMilik($request, $kelas);
        $data = $request->validate(['nama' => ['required', 'string', 'max:150']]);

        Siswa::create([
            'kelasId' => $kelas->id,
            'nomor' => ((int) $kelas->siswa()->max('nomor')) + 1,
            'nama' => $this->rapikan($data['nama']),
        ]);

        return back()->with('toast', ['type' => 'success', 'message' => 'Siswa ditambahkan']);
    }

    public function update(Request $request, string $siswa)
    {
        $siswa = $this->siswaMilik($request, $siswa);
        $data = $request->validate(['nama' => ['required', 'string', 'max:150']]);

        $siswa->update(['nama' => $this->rapikan($data['nama'])]);

        return back()->with('toast', ['type' => 'success', 'message' => 'Nama siswa diperbarui']);
    }

    public function destroy(Request $request, string $siswa)
    {
        $siswa = $this->siswaMilik($request, $siswa);
        $kelasId = $siswa->kelasId;
        $siswa->delete();

        return redirect("/kelas/{$kelasId}")->with('toast', ['type' => 'success', 'message' => 'Siswa dihapus']);
    }

    private function rapikan(string $nama): string
    {
        return preg_replace('/\s+/', ' ', trim($nama));
    }
}
