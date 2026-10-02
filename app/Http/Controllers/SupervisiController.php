<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\MencariMilikSendiri;
use App\Models\Supervisi;
use App\Services\PdfExporter;
use App\Services\SupervisiDocxExporter;
use App\Support\SupervisiInstrumen;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class SupervisiController extends Controller
{
    use MencariMilikSendiri;

    private const ATRIBUT = [
        'guruDinilai' => 'Penyusun perencanaan',
        'pemberiUmpanBalik' => 'Pemberi umpan balik',
        'tanggal' => 'Tanggal',
        'butir' => 'Butir',
        'butir.*.nomor' => 'Nomor butir',
        'butir.*.bukti' => 'Bukti pembelajaran',
        'butir.*.catatan' => 'Catatan',
        'refleksi' => 'Refleksi',
        'refleksi.*' => 'Jawaban refleksi',
    ];

    public function __construct(private SupervisiDocxExporter $docx, private PdfExporter $pdf) {}

    public function index(Request $request)
    {
        $daftar = Supervisi::where('userId', $request->user()->id)
            ->orderByDesc('tanggal')->orderByDesc('createdAt')
            ->get(['id', 'guruDinilai', 'pemberiUmpanBalik', 'tanggal']);

        return Inertia::render('supervisi/index-page', [
            'daftar' => $daftar->map(fn (Supervisi $s) => [
                'id' => $s->id,
                'guruDinilai' => $s->guruDinilai,
                'pemberiUmpanBalik' => $s->pemberiUmpanBalik,
                'tanggal' => $s->tanggal->format('Y-m-d'),
            ]),
        ]);
    }

    public function create(Request $request)
    {
        return $this->form($request, null);
    }

    public function show(Request $request, string $supervisi)
    {
        return $this->form($request, $this->supervisiMilik($request, $supervisi));
    }

    public function store(Request $request)
    {
        $supervisi = Supervisi::create($this->dataTervalidasi($request) + ['userId' => $request->user()->id]);

        return redirect("/supervisi/{$supervisi->id}")
            ->with('toast', ['type' => 'success', 'message' => 'Lembar supervisi tersimpan']);
    }

    public function update(Request $request, string $supervisi)
    {
        $this->supervisiMilik($request, $supervisi)->update($this->dataTervalidasi($request));

        return back()->with('toast', ['type' => 'success', 'message' => 'Lembar supervisi tersimpan']);
    }

    public function destroy(Request $request, string $supervisi)
    {
        $this->supervisiMilik($request, $supervisi)->delete();

        return redirect('/supervisi')->with('toast', ['type' => 'success', 'message' => 'Lembar supervisi dihapus']);
    }

    public function export(Request $request, string $supervisi): Response
    {
        $data = $request->validate(['format' => ['required', Rule::in(['docx', 'pdf'])]]);
        $supervisi = $this->supervisiMilik($request, $supervisi);
        $nama = 'Supervisi-'.Str::slug($supervisi->guruDinilai).'-'.$supervisi->tanggal->format('Y-m-d').'.'.$data['format'];

        if ($data['format'] === 'pdf') {
            return $this->pdf->supervisi($supervisi, $nama);
        }

        return response()->download($this->docx->buat($supervisi), $nama, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }

    /** Isi baku instrumen (butir 1-18) dikirim ke form supaya bentuknya selalu sama dengan template. */
    private function form(Request $request, ?Supervisi $supervisi)
    {
        return Inertia::render('supervisi/form-page', [
            'supervisi' => $supervisi?->toProps(),
            'instrumen' => [
                'bagian' => SupervisiInstrumen::bagian(),
                'refleksi' => SupervisiInstrumen::refleksi(),
            ],
            'pemberiDefault' => $request->user()->name,
            'hariIni' => now()->format('Y-m-d'),
        ]);
    }

    /** @return array<string, mixed> */
    private function dataTervalidasi(Request $request): array
    {
        $data = $request->validate([
            'guruDinilai' => ['required', 'string', 'max:150'],
            'pemberiUmpanBalik' => ['required', 'string', 'max:150'],
            'tanggal' => ['required', 'date_format:Y-m-d'],
            'butir' => ['nullable', 'array', 'max:'.SupervisiInstrumen::JUMLAH_BUTIR],
            'butir.*.nomor' => ['required', 'integer', 'between:1,'.SupervisiInstrumen::JUMLAH_BUTIR],
            'butir.*.bukti' => ['nullable', 'string', 'max:5000'],
            'butir.*.catatan' => ['nullable', 'string', 'max:5000'],
            'refleksi' => ['nullable', 'array', 'max:'.SupervisiInstrumen::JUMLAH_REFLEKSI],
            'refleksi.*' => ['nullable', 'string', 'max:5000'],
        ], [], self::ATRIBUT);

        return [
            'guruDinilai' => trim($data['guruDinilai']),
            'pemberiUmpanBalik' => trim($data['pemberiUmpanBalik']),
            'tanggal' => $data['tanggal'],
            'butir' => SupervisiInstrumen::lengkapiButir($data['butir'] ?? []),
            'refleksi' => SupervisiInstrumen::lengkapiRefleksi($data['refleksi'] ?? []),
        ];
    }
}
