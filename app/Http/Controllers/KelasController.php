<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\MencariMilikSendiri;
use App\Models\Kelas;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class KelasController extends Controller
{
    use MencariMilikSendiri;

    public function index(Request $request)
    {
        $kelas = Kelas::where('userId', $request->user()->id)
            ->withCount('siswa')
            ->get()
            // Tahun ajaran terbaru dulu, lalu X < XI < XII, jurusan, nomor rombel.
            ->sort(fn (Kelas $a, Kelas $b) => strcmp($b->tahunAjaran, $a->tahunAjaran) ?: Kelas::urutAlami($a, $b))
            ->values();

        return Inertia::render('nilai/kelas-page', [
            'kelas' => $kelas->map->toProps(),
            'tahunAjaranSekarang' => Kelas::tahunAjaranSekarang(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->aturan($request));

        Kelas::create([
            'userId' => $request->user()->id,
            'nama' => $this->rapikan($data['nama']),
            'tahunAjaran' => $data['tahunAjaran'],
        ]);

        return back()->with('toast', ['type' => 'success', 'message' => 'Kelas ditambahkan']);
    }

    public function update(Request $request, string $kelas)
    {
        $kelas = $this->kelasMilik($request, $kelas);
        $data = $request->validate($this->aturan($request, $kelas));

        $kelas->update(['nama' => $this->rapikan($data['nama']), 'tahunAjaran' => $data['tahunAjaran']]);

        return back()->with('toast', ['type' => 'success', 'message' => 'Kelas diperbarui']);
    }

    public function destroy(Request $request, string $kelas)
    {
        $this->kelasMilik($request, $kelas)->delete();

        return redirect('/nilai')->with('toast', ['type' => 'success', 'message' => 'Kelas dihapus']);
    }

    /** @return array<string, mixed> */
    private function aturan(Request $request, ?Kelas $kelas = null): array
    {
        return [
            'nama' => [
                'required', 'string', 'max:100',
                Rule::unique('kelas', 'nama')
                    ->where(fn ($q) => $q->where('userId', $request->user()->id)->where('tahunAjaran', $request->input('tahunAjaran')))
                    ->ignore($kelas?->id),
            ],
            'tahunAjaran' => ['required', 'string', 'regex:/^\d{4}\/\d{4}$/'],
        ];
    }

    private function rapikan(string $nama): string
    {
        return preg_replace('/\s+/', ' ', trim($nama));
    }
}
