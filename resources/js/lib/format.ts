const HARI = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"]
const BULAN = [
  "Januari", "Februari", "Maret", "April", "Mei", "Juni",
  "Juli", "Agustus", "September", "Oktober", "November", "Desember",
]

/** "2026-09-24" -> Date pada tengah hari lokal (menghindari geser hari karena zona waktu). */
export function dariIso(iso: string): Date {
  const [y, m, d] = iso.split("-").map(Number)
  return new Date(y, (m ?? 1) - 1, d ?? 1, 12)
}

export function keIso(date: Date): string {
  const p = (n: number) => String(n).padStart(2, "0")
  return `${date.getFullYear()}-${p(date.getMonth() + 1)}-${p(date.getDate())}`
}

/** "Kamis, 24 September 2026", tanpa Intl agar sama di semua perangkat. */
export function tanggalPanjang(iso: string): string {
  const d = dariIso(iso)
  return `${HARI[d.getDay()]}, ${d.getDate()} ${BULAN[d.getMonth()]} ${d.getFullYear()}`
}

/** 85.5 -> "85,5" (koma desimal seperti Excel berlokal Indonesia); null -> "". */
export function tampilAngka(nilai: number | null | undefined): string {
  if (nilai === null || nilai === undefined) return ""
  return String(Math.round(nilai * 100) / 100).replace(".", ",")
}

export type HasilAngka = { ok: true; nilai: number | null } | { ok: false }

/** Membaca isian nilai: kosong = mengosongkan sel; koma atau titik desimal; harus 0-100. */
export function bacaAngka(teks: string): HasilAngka {
  const bersih = teks.trim().replace(",", ".")
  if (bersih === "") return { ok: true, nilai: null }
  if (!/^\d+(\.\d{0,2})?$/.test(bersih)) return { ok: false }
  const nilai = Number(bersih)
  return nilai >= 0 && nilai <= 100 ? { ok: true, nilai } : { ok: false }
}

/** Nama berkas unduhan: bagian dirapikan lalu digabung dengan "-". */
export function namaBerkas(bagian: string[], ekstensi: string): string {
  const bersih = bagian
    .map((b) => b.trim().replace(/[\\/:*?"<>|]+/g, "-").replace(/\s+/g, "-"))
    .filter(Boolean)
    .join("-")
  return `${bersih}.${ekstensi}`
}
