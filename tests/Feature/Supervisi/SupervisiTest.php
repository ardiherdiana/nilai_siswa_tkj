<?php

namespace Tests\Feature\Supervisi;

use App\Models\Supervisi;
use DOMDocument;
use DOMXPath;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;
use ZipArchive;

class SupervisiTest extends TestCase
{
    private function payload(array $override = []): array
    {
        return $override + [
            'guruDinilai' => 'Asep Wahyudin, S.Kom',
            'pemberiUmpanBalik' => 'Latif Ashari',
            'tanggal' => '2026-09-24',
            'butir' => [
                ['nomor' => 1, 'bukti' => 'RPP bagian awal dilaksanakan', 'catatan' => 'Sudah selaras'],
                ['nomor' => 15, 'bukti' => '', 'catatan' => 'Rubrik jelas'],
            ],
            'refleksi' => ['Pelajaran: diskusi berjalan baik', '', 'Tindak lanjut:'."\n".'perbaiki waktu'],
        ];
    }

    /** @return array{0: string, 1: DOMDocument, 2: DOMXPath, 3: string} xml mentah, dokumen, xpath, core.xml */
    private function bukaDocx(string $isi): array
    {
        $path = tempnam(sys_get_temp_dir(), 'uji-').'.docx';
        file_put_contents($path, $isi);
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path));
        $xml = $zip->getFromName('word/document.xml');
        $core = $zip->getFromName('docProps/core.xml');
        $entri = $zip->numFiles;
        $zip->close();
        $this->assertSame(15, $entri, 'jumlah berkas di dalam docx sama dengan template');

        $dom = new DOMDocument;
        $this->assertTrue($dom->loadXML($xml), 'document.xml harus XML yang valid');
        $xp = new DOMXPath($dom);
        $xp->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        return [$xml, $dom, $xp, $core];
    }

    /** Membuat lembar lewat rute sungguhan lalu mengembalikan modelnya. */
    private function buat(array $override = []): Supervisi
    {
        $this->post('/supervisi', $this->payload($override))->assertRedirect();

        return Supervisi::latest('createdAt')->firstOrFail();
    }

    public function test_halaman_form_memuat_instrumen_15_butir_dalam_4_bagian_dan_3_refleksi(): void
    {
        $user = $this->actingAsGuru(['name' => 'Latif Ashari']);

        $respons = $this->get('/supervisi/baru')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('supervisi/form-page')
            ->where('supervisi', null)
            ->where('pemberiDefault', $user->name)
            ->where('instrumen.bagian.0.butir.0.paragraf.1.teks', 'awal pembelajaran')
            ->where('instrumen.bagian.0.butir.0.paragraf.1.daftar', true)
            ->has('hariIni'));

        $instrumen = $this->inertiaProps($respons)['instrumen'];
        $this->assertSame(['Keselarasan', 'Implementasi Kerangka Pembelajaran', 'Langkah Pembelajaran', 'Asesmen'], array_column($instrumen['bagian'], 'judul'));
        $this->assertSame(range(1, 15), collect($instrumen['bagian'])->flatMap(fn ($b) => array_column($b['butir'], 'nomor'))->all());
        $this->assertSame([16, 17, 18], array_column($instrumen['refleksi'], 'nomor'));
    }

    public function test_buat_lihat_ubah_hapus_supervisi_selalu_berbentuk_lengkap(): void
    {
        $this->actingAsGuru();

        $supervisi = $this->buat();
        $this->assertCount(15, $supervisi->toProps()['butir']);
        $this->assertCount(3, $supervisi->toProps()['refleksi']);

        $this->get('/supervisi')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('supervisi/index-page')->where('daftar.0.guruDinilai', 'Asep Wahyudin, S.Kom')->where('daftar.0.tanggal', '2026-09-24'));

        $this->get("/supervisi/{$supervisi->id}")->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('supervisi/form-page')
            ->where('supervisi.butir.0.bukti', 'RPP bagian awal dilaksanakan')
            ->where('supervisi.butir.1.bukti', '')
            ->where('supervisi.butir.14.catatan', 'Rubrik jelas')
            ->has('supervisi.butir', 15)
            ->has('supervisi.refleksi', 3));

        $this->put("/supervisi/{$supervisi->id}", $this->payload(['guruDinilai' => 'Guru Baru', 'butir' => [], 'refleksi' => []]))->assertRedirect();
        $ubah = $supervisi->fresh()->toProps();
        $this->assertSame('Guru Baru', $ubah['guruDinilai']);
        $this->assertSame('', $ubah['butir'][0]['bukti']);

        $this->delete("/supervisi/{$supervisi->id}")->assertRedirect('/supervisi');
        $this->get("/supervisi/{$supervisi->id}")->assertNotFound();
    }

    public function test_simpan_lembar_baru_mengarahkan_ke_halaman_lembar_itu(): void
    {
        $this->actingAsGuru();

        $res = $this->post('/supervisi', $this->payload());

        $res->assertRedirect('/supervisi/'.Supervisi::firstOrFail()->id);
    }

    public function test_validasi_supervisi(): void
    {
        $this->actingAsGuru();

        $this->post('/supervisi', [])->assertSessionHasErrors(['guruDinilai', 'pemberiUmpanBalik', 'tanggal']);
        $this->post('/supervisi', $this->payload(['tanggal' => '24/09/2026']))->assertSessionHasErrors('tanggal');
        $this->post('/supervisi', $this->payload(['butir' => [['nomor' => 16, 'bukti' => 'x']]]))->assertSessionHasErrors('butir.0.nomor');
        $this->post('/supervisi', $this->payload(['refleksi' => ['a', 'b', 'c', 'd']]))->assertSessionHasErrors('refleksi');
        $this->assertSame(0, Supervisi::count());
    }

    public function test_supervisi_hanya_bisa_diakses_pemiliknya(): void
    {
        $milik = Supervisi::factory()->create();
        $this->actingAsGuru();

        $this->get('/supervisi')->assertInertia(fn (AssertableInertia $page) => $page->has('daftar', 0));
        $this->get("/supervisi/{$milik->id}")->assertNotFound();
        $this->put("/supervisi/{$milik->id}", $this->payload())->assertNotFound();
        $this->delete("/supervisi/{$milik->id}")->assertNotFound();
        $this->get("/supervisi/{$milik->id}/ekspor?format=docx")->assertNotFound();
        $this->assertDatabaseHas('supervisi', ['id' => $milik->id]);
    }

    public function test_export_docx_mengisi_template_asli_tanpa_mengubah_strukturnya(): void
    {
        $this->actingAsGuru();
        $supervisi = $this->buat();

        $res = $this->get("/supervisi/{$supervisi->id}/ekspor?format=docx")->assertOk();
        $this->assertStringContainsString('wordprocessingml.document', $res->headers->get('Content-Type'));
        $this->assertStringContainsString('Supervisi-asep-wahyudin-skom-2026-09-24.docx', $res->headers->get('Content-Disposition'));

        [, $dom, $xp, $core] = $this->bukaDocx($res->streamedContent());
        $teks = $dom->documentElement->textContent;

        // Isian masuk.
        foreach (['Asep Wahyudin, S.Kom', 'Latif Ashari', 'Kamis, 24 September 2026', 'RPP bagian awal dilaksanakan', 'Sudah selaras', 'Rubrik jelas', 'Pelajaran: diskusi berjalan baik', 'perbaiki waktu', 'Karawang, 24 September 2026'] as $harapan) {
            $this->assertStringContainsString($harapan, $teks, $harapan);
        }
        // Baris titik-titik diganti nama penanda tangan.
        $this->assertStringNotContainsString('..........................', $teks);
        // Teks baku template tetap ada dan strukturnya utuh: 24 baris tabel, 15 butir + 3 refleksi bernomor.
        $this->assertStringContainsString('INSTRUMEN OBSERVASI IMPLEMENTASI DAN REFLEKSI', $teks);
        $this->assertStringContainsString('Bukti Pembelajaran', $teks);
        $this->assertSame(24, $xp->query('//w:tbl/w:tr')->length);
        $this->assertSame(1, $xp->query('//w:tbl')->length);
        // Kolom Bukti butir 1 diisi di paragraf kosong bawaan (tidak menambah paragraf), dengan font template.
        $bukti1 = $xp->query('//w:tbl/w:tr[3]/w:tc[3]/w:p')->item(0);
        $this->assertSame('RPP bagian awal dilaksanakan', trim($bukti1->textContent));
        $this->assertSame('Arial', $xp->query('.//w:rFonts/@w:ascii', $bukti1)->item(0)->nodeValue);
        // Baris baru pada isian menjadi <w:br/>.
        $this->assertSame(1, $xp->query('//w:tbl/w:tr[24]//w:br')->length);
        // Nama pembuat asli template tidak ikut terbawa.
        $this->assertStringNotContainsString('Asep Wahyudin', $core);
        $this->assertStringNotContainsString('LATIF_AS', $core);
        $this->assertStringContainsString('Nilai Siswa TKJ &amp; Supervisi Guru', $core);
    }

    public function test_export_docx_aman_untuk_karakter_khusus_dan_karakter_kontrol(): void
    {
        $this->actingAsGuru();
        $supervisi = $this->buat([
            'guruDinilai' => 'Tom & Jerry <b>"x"</b>',
            'butir' => [['nomor' => 2, 'bukti' => "A & B <script>\x01\x08 ok", 'catatan' => '=1+1']],
        ]);

        $res = $this->get("/supervisi/{$supervisi->id}/ekspor?format=docx")->assertOk();
        [, $dom] = $this->bukaDocx($res->streamedContent());

        $this->assertStringContainsString('Tom & Jerry <b>"x"</b>', $dom->documentElement->textContent);
        $this->assertStringContainsString('A & B <script> ok', $dom->documentElement->textContent);
    }

    public function test_export_pdf_supervisi(): void
    {
        $this->actingAsGuru();
        $supervisi = $this->buat();

        $res = $this->get("/supervisi/{$supervisi->id}/ekspor?format=pdf")->assertOk();

        $this->assertSame('application/pdf', $res->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $res->getContent());
        $this->getJson("/supervisi/{$supervisi->id}/ekspor?format=odt")->assertStatus(422);
    }
}
