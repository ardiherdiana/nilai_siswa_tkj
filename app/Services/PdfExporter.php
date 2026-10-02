<?php

namespace App\Services;

use App\Models\Supervisi;
use App\Support\NilaiLembar;
use App\Support\SupervisiInstrumen;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\HeaderUtils;

/**
 * PDF dibuat dengan dompdf (sudah dipakai modul SPPD), bukan dikonversi dari docx/xlsx lewat
 * LibreOffice: hosting produksi (shared hosting) tidak menyediakan LibreOffice.
 */
class PdfExporter
{
    public function __construct(private NilaiPdfRenderer $lembarNilai) {}

    public function nilai(NilaiLembar $lembar, string $namaBerkas): Response
    {
        return new Response($this->lembarNilai->render($lembar), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $namaBerkas),
        ]);
    }

    public function supervisi(Supervisi $supervisi, string $namaBerkas): Response
    {
        return Pdf::loadView('pdf.supervisi', [
            'supervisi' => array_merge($supervisi->toProps(), ['tanggal' => $supervisi->tanggal]),
            'bagian' => SupervisiInstrumen::bagian(),
            'refleksi' => SupervisiInstrumen::refleksi(),
        ])->setPaper('a4', 'portrait')->download($namaBerkas);
    }
}
