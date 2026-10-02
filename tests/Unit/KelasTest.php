<?php

namespace Tests\Unit;

use App\Models\Kelas;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class KelasTest extends TestCase
{
    public function test_jurusan_diambil_dari_nama_kelas(): void
    {
        $this->assertSame('TKJ', Kelas::jurusanDari('X TKJ 1'));
        $this->assertSame('DKV', Kelas::jurusanDari('XII dkv 12'));
        $this->assertNull(Kelas::jurusanDari('Kelas Bebas'));
    }

    public function test_urutan_kelas_memakai_tingkat_jurusan_lalu_nomor_rombel_sebagai_angka(): void
    {
        $nama = ['XII TKJ 1', 'X TKJ 10', 'XI TKJ 1', 'X TKJ 2', 'X AKL 1', 'Lain-lain'];
        $kelas = array_map(fn (string $n) => (new Kelas)->forceFill(['nama' => $n]), $nama);

        usort($kelas, Kelas::urutAlami(...));

        $this->assertSame(['X AKL 1', 'X TKJ 2', 'X TKJ 10', 'XI TKJ 1', 'XII TKJ 1', 'Lain-lain'], array_map(fn (Kelas $k) => $k->nama, $kelas));
    }

    public function test_tahun_ajaran_dimulai_bulan_juli(): void
    {
        $this->assertSame('2026/2027', Kelas::tahunAjaranSekarang(Carbon::parse('2026-07-01')));
        $this->assertSame('2026/2027', Kelas::tahunAjaranSekarang(Carbon::parse('2026-09-26')));
        $this->assertSame('2026/2027', Kelas::tahunAjaranSekarang(Carbon::parse('2027-06-30')));
        $this->assertSame('2025/2026', Kelas::tahunAjaranSekarang(Carbon::parse('2026-06-30')));
    }
}
