<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\MencariMilikSendiri;
use App\Models\Kelas;
use App\Services\NilaiXlsxExporter;
use App\Services\PdfExporter;
use App\Support\NilaiLembar;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class ExportNilaiController extends Controller
{
    use MencariMilikSendiri;

    public function __construct(private NilaiXlsxExporter $xlsx, private PdfExporter $pdf) {}

    /**
     * Tanpa `kelasId` = semua kelas pada tahun ajaran itu (satu lembar seperti template asli).
     * Tanpa `tahunAjaran` = tahun ajaran terbaru milik akun.
     */
    public function nilai(Request $request): Response
    {
        $data = $request->validate([
            'format' => ['required', Rule::in(['xlsx', 'pdf'])],
            'tahunAjaran' => ['nullable', 'string', 'regex:/^\d{4}\/\d{4}$/'],
            'kelasId' => ['nullable', 'string'],
        ]);

        $userId = $request->user()->id;

        if (! empty($data['kelasId'])) {
            $kelas = new EloquentCollection([$this->kelasMilik($request, $data['kelasId'])]);
            $tahunAjaran = $kelas[0]->tahunAjaran;
        } else {
            $tahunAjaran = $data['tahunAjaran'] ?? Kelas::where('userId', $userId)->max('tahunAjaran');
            abort_if($tahunAjaran === null, 404, 'Belum ada kelas untuk diekspor');
            $kelas = Kelas::where('userId', $userId)->where('tahunAjaran', $tahunAjaran)->get();
            abort_if($kelas->isEmpty(), 404, 'Tidak ada kelas pada tahun ajaran ini');
        }

        $kelas = $kelas->load('siswa.nilai')->sort(Kelas::urutAlami(...))->values();
        $lembar = NilaiLembar::dari($kelas, $tahunAjaran);
        $nama = $this->namaBerkas($kelas, $tahunAjaran, $data['format']);

        return $data['format'] === 'xlsx'
            ? $this->xlsx->unduh($lembar, $nama)
            : $this->pdf->nilai($lembar, $nama);
    }

    /** Format kosong untuk diisi lalu diimpor kembali. */
    public function templateKosong(): Response
    {
        return $this->xlsx->unduh(NilaiLembar::kosong(Kelas::tahunAjaranSekarang()), 'Format-Nilai-Siswa.xlsx');
    }

    /** @param  Collection<int, Kelas>  $kelas */
    private function namaBerkas($kelas, string $tahunAjaran, string $format): string
    {
        $bagian = ['Nilai-Siswa'];
        if ($kelas->count() === 1) {
            $bagian[] = Str::slug($kelas[0]->nama);
        } elseif (($jurusan = $kelas->map->jurusan()->unique()->values())->count() === 1 && $jurusan[0] !== null) {
            $bagian[] = $jurusan[0];
        }
        $bagian[] = str_replace('/', '-', $tahunAjaran);

        return implode('-', $bagian).'.'.$format;
    }
}
