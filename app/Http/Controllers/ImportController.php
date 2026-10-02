<?php

namespace App\Http\Controllers;

use App\Services\SiswaImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function __construct(private SiswaImportService $import) {}

    /** Dipanggil lewat XHR (axios) karena hasilnya berupa laporan yang ditampilkan di dialog, lalu halaman dimuat ulang. */
    public function siswa(Request $request): JsonResponse
    {
        $data = $request->validate([
            'berkas' => ['required', 'file', 'mimes:xlsx', 'max:5120'],
            'tahunAjaran' => ['nullable', 'string', 'regex:/^\d{4}\/\d{4}$/'],
        ]);

        return response()->json($this->import->impor($request->user(), $request->file('berkas'), $data['tahunAjaran'] ?? null));
    }
}
