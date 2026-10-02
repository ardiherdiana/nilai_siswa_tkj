<?php

namespace Tests\Feature\Nilai;

use App\Models\Kelas;
use App\Models\Nilai;
use App\Models\Siswa;
use App\Models\User;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class KelasSiswaNilaiTest extends TestCase
{
    // ------------------------------------------------------------------ kelas

    public function test_daftar_kelas_terurut_tahun_ajaran_terbaru_lalu_tingkat_jurusan_dan_rombel(): void
    {
        $user = $this->actingAsGuru();
        foreach ([['XII TKJ 1', '2026/2027'], ['X TKJ 10', '2026/2027'], ['X TKJ 2', '2026/2027'], ['XI TKJ 1', '2026/2027'], ['X TKJ 1', '2025/2026']] as [$nama, $ta]) {
            Kelas::factory()->create(['userId' => $user->id, 'nama' => $nama, 'tahunAjaran' => $ta]);
        }
        Kelas::factory()->create(['nama' => 'Milik Orang Lain']);

        $respons = $this->get('/nilai')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('nilai/kelas-page')
            ->has('tahunAjaranSekarang'));

        $this->assertSame(['X TKJ 2', 'X TKJ 10', 'XI TKJ 1', 'XII TKJ 1', 'X TKJ 1'], array_column($this->inertiaProps($respons)['kelas'], 'nama'));
    }

    public function test_tambah_ubah_dan_hapus_kelas(): void
    {
        $user = $this->actingAsGuru();

        $this->from('/nilai')->post('/kelas', ['nama' => '  X   TKJ  1 ', 'tahunAjaran' => '2026/2027'])->assertRedirect('/nilai');
        $kelas = Kelas::where('userId', $user->id)->sole();
        $this->assertSame('X TKJ 1', $kelas->nama);

        $this->from('/nilai')->patch("/kelas/{$kelas->id}", ['nama' => 'X TKJ 2', 'tahunAjaran' => '2026/2027'])->assertRedirect('/nilai');
        $this->assertSame('X TKJ 2', $kelas->fresh()->nama);

        $this->delete("/kelas/{$kelas->id}")->assertRedirect('/nilai');
        $this->assertDatabaseMissing('kelas', ['id' => $kelas->id]);
    }

    public function test_validasi_kelas(): void
    {
        $user = $this->actingAsGuru();
        Kelas::factory()->create(['userId' => $user->id, 'nama' => 'X TKJ 1', 'tahunAjaran' => '2026/2027']);

        $this->from('/nilai')->post('/kelas', [])->assertSessionHasErrors(['nama', 'tahunAjaran']);
        $this->from('/nilai')->post('/kelas', ['nama' => 'X TKJ 3', 'tahunAjaran' => '2026'])->assertSessionHasErrors('tahunAjaran');
        // Nama sama pada tahun ajaran yang sama ditolak, pada tahun ajaran lain boleh.
        $this->from('/nilai')->post('/kelas', ['nama' => 'X TKJ 1', 'tahunAjaran' => '2026/2027'])->assertSessionHasErrors('nama');
        $this->from('/nilai')->post('/kelas', ['nama' => 'X TKJ 1', 'tahunAjaran' => '2027/2028'])->assertSessionHasNoErrors();
        // Akun lain boleh memakai nama yang sama.
        $this->actingAsGuru();
        $this->from('/nilai')->post('/kelas', ['nama' => 'X TKJ 1', 'tahunAjaran' => '2026/2027'])->assertSessionHasNoErrors();
    }

    public function test_kelas_akun_lain_dijawab_404(): void
    {
        $milik = Kelas::factory()->create();
        $this->actingAsGuru();

        $this->get("/kelas/{$milik->id}")->assertNotFound();
        $this->patch("/kelas/{$milik->id}", ['nama' => 'X TKJ 9', 'tahunAjaran' => '2026/2027'])->assertNotFound();
        $this->delete("/kelas/{$milik->id}")->assertNotFound();
        $this->post("/kelas/{$milik->id}/siswa", ['nama' => 'Ani'])->assertNotFound();
        $this->assertDatabaseHas('kelas', ['id' => $milik->id, 'nama' => $milik->nama]);
    }

    // ------------------------------------------------------------------ siswa

    public function test_halaman_kelas_memuat_siswa_beserta_jumlah_nilai_terisi(): void
    {
        $user = $this->actingAsGuru();
        $kelas = Kelas::factory()->create(['userId' => $user->id, 'nama' => 'X TKJ 1']);
        $ani = Siswa::factory()->create(['kelasId' => $kelas->id, 'nomor' => 1, 'nama' => 'Ani']);
        Siswa::factory()->create(['kelasId' => $kelas->id, 'nomor' => 2, 'nama' => 'Budi']);
        Nilai::create(['siswaId' => $ani->id, 'tes1' => 80, 'praktik3' => 90]);

        $this->get("/kelas/{$kelas->id}")->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('nilai/siswa-page')
            ->where('kelas.nama', 'X TKJ 1')
            ->where('totalNilai', 20)
            ->where('siswa.0.nama', 'Ani')
            ->where('siswa.0.terisi', 2)
            ->where('siswa.1.terisi', 0));
    }

    public function test_tambah_ubah_dan_hapus_siswa_dengan_nomor_berlanjut(): void
    {
        $user = $this->actingAsGuru();
        $kelas = Kelas::factory()->create(['userId' => $user->id]);

        $this->post("/kelas/{$kelas->id}/siswa", ['nama' => '  Ani   Yudhoyono '])->assertRedirect();
        $this->post("/kelas/{$kelas->id}/siswa", ['nama' => 'Budi'])->assertRedirect();
        $ani = Siswa::where('nama', 'Ani Yudhoyono')->sole();
        $this->assertSame([1, 2], Siswa::where('kelasId', $kelas->id)->orderBy('nomor')->pluck('nomor')->all());

        $this->patch("/siswa/{$ani->id}", ['nama' => 'Ani Y.'])->assertRedirect();
        $this->assertSame('Ani Y.', $ani->fresh()->nama);

        $this->delete("/siswa/{$ani->id}")->assertRedirect("/kelas/{$kelas->id}");
        $this->assertDatabaseMissing('siswa', ['id' => $ani->id]);

        $this->post("/kelas/{$kelas->id}/siswa", ['nama' => ''])->assertSessionHasErrors('nama');
    }

    public function test_hapus_kelas_ikut_menghapus_siswa_dan_nilainya(): void
    {
        $user = $this->actingAsGuru();
        $kelas = Kelas::factory()->create(['userId' => $user->id]);
        $siswa = Siswa::factory()->create(['kelasId' => $kelas->id]);
        Nilai::create(['siswaId' => $siswa->id, 'tes1' => 70]);

        $this->delete("/kelas/{$kelas->id}");

        $this->assertSame(0, Siswa::count());
        $this->assertSame(0, Nilai::count());
    }

    public function test_siswa_akun_lain_dijawab_404(): void
    {
        $siswa = Siswa::factory()->create();
        $this->actingAsGuru();

        $this->patch("/siswa/{$siswa->id}", ['nama' => 'X'])->assertNotFound();
        $this->delete("/siswa/{$siswa->id}")->assertNotFound();
        $this->put("/siswa/{$siswa->id}/nilai", ['nilai' => ['tes1' => 80]])->assertNotFound();
        $this->get("/kelas/{$siswa->kelasId}/siswa/{$siswa->id}")->assertNotFound();
    }

    // ------------------------------------------------------------------ nilai

    public function test_halaman_nilai_siswa_memuat_20_kolom_nilai(): void
    {
        $user = $this->actingAsGuru();
        $kelas = Kelas::factory()->create(['userId' => $user->id]);
        $siswa = Siswa::factory()->create(['kelasId' => $kelas->id, 'nama' => 'Ani']);
        Nilai::create(['siswaId' => $siswa->id, 'tes2' => 85.5]);

        $this->get("/kelas/{$kelas->id}/siswa/{$siswa->id}")->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('nilai/nilai-page')
            ->where('siswa.nama', 'Ani')
            ->where('siswa.nilai.tes2', 85.5)
            ->where('siswa.nilai.tes1', null)
            ->has('siswa.nilai', 20));
    }

    public function test_simpan_nilai_hanya_mengubah_kolom_yang_dikirim(): void
    {
        $user = $this->actingAsGuru();
        $kelas = Kelas::factory()->create(['userId' => $user->id]);
        $siswa = Siswa::factory()->create(['kelasId' => $kelas->id]);

        $this->put("/siswa/{$siswa->id}/nilai", ['nilai' => ['tes1' => 80, 'praktik10' => '92.567']])->assertRedirect();
        $nilai = $siswa->fresh()->nilai;
        $this->assertSame(80.0, $nilai->tes1);
        $this->assertSame(92.57, $nilai->praktik10);

        // Kolom lain tidak tersentuh; null mengosongkan sel.
        $this->put("/siswa/{$siswa->id}/nilai", ['nilai' => ['tes1' => null, 'tes2' => 75]])->assertRedirect();
        $nilai = $siswa->fresh()->nilai;
        $this->assertNull($nilai->tes1);
        $this->assertSame(75.0, $nilai->tes2);
        $this->assertSame(92.57, $nilai->praktik10);
        $this->assertSame(1, Nilai::count());
    }

    public function test_validasi_nilai(): void
    {
        $user = $this->actingAsGuru();
        $siswa = Siswa::factory()->create(['kelasId' => Kelas::factory()->create(['userId' => $user->id])->id]);
        $url = "/siswa/{$siswa->id}/nilai";

        $this->put($url, ['nilai' => ['tes1' => 101]])->assertSessionHasErrors('nilai.tes1');
        $this->put($url, ['nilai' => ['tes1' => -1]])->assertSessionHasErrors('nilai.tes1');
        $this->put($url, ['nilai' => ['tes1' => 'abc']])->assertSessionHasErrors('nilai.tes1');
        $this->put($url, ['nilai' => ['tes11' => 80]])->assertSessionHasErrors('nilai');
        $this->put($url, ['nilai' => []])->assertSessionHasErrors('nilai');
        $this->put($url, [])->assertSessionHasErrors('nilai');
        $this->assertSame(0, Nilai::count());
    }

    public function test_dashboard_meringkas_tahun_ajaran_terbaru(): void
    {
        $user = $this->actingAsGuru();
        $lama = Kelas::factory()->create(['userId' => $user->id, 'nama' => 'X TKJ 1', 'tahunAjaran' => '2025/2026']);
        $baru = Kelas::factory()->create(['userId' => $user->id, 'nama' => 'X TKJ 1', 'tahunAjaran' => '2026/2027']);
        Siswa::factory()->count(3)->create(['kelasId' => $lama->id]);
        Siswa::factory()->count(2)->create(['kelasId' => $baru->id]);

        $this->get('/dashboard')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('dashboard-page')
            ->where('nilai.tahunAjaran', '2026/2027')
            ->where('nilai.jumlahKelas', 1)
            ->where('nilai.jumlahSiswa', 2)
            ->where('supervisi.total', 0)
            ->where('akun', null));
    }

    public function test_dashboard_admin_memuat_ringkasan_akun(): void
    {
        $this->actingAsAdmin();
        User::factory()->count(2)->create();
        User::factory()->nonaktif()->create();

        $this->get('/dashboard')->assertInertia(fn (AssertableInertia $page) => $page->where('akun.total', 4)->where('akun.aktif', 3));
    }
}
