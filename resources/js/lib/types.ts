export type Role = "ADMIN" | "GURU"

export interface AuthUser {
  id: string
  username: string
  name: string
  role: Role
}

export interface FlashToast {
  type: "success" | "error" | "warning" | "info"
  message: string
}

export interface Akun {
  id: string
  username: string
  name: string
  role: Role
  aktif: boolean
}

export interface Kelas {
  id: string
  nama: string
  tahunAjaran: string
  jumlahSiswa: number
}

export const KOLOM_TES = [
  "tes1", "tes2", "tes3", "tes4", "tes5", "tes6", "tes7", "tes8", "tes9", "tes10",
] as const
export const KOLOM_PRAKTIK = [
  "praktik1", "praktik2", "praktik3", "praktik4", "praktik5",
  "praktik6", "praktik7", "praktik8", "praktik9", "praktik10",
] as const
export type KolomNilai = (typeof KOLOM_TES)[number] | (typeof KOLOM_PRAKTIK)[number]

export interface SiswaRingkas {
  id: string
  nomor: number
  nama: string
  /** Jumlah nilai yang sudah terisi dari 20. */
  terisi: number
}

export interface SiswaNilai {
  id: string
  nomor: number
  nama: string
  nilai: Record<KolomNilai, number | null>
}

export interface ImportHasil {
  tahunAjaran: string
  kelas: { nama: string; ditambah: number; dilewati: number }[]
  totalDitambah: number
  totalDilewati: number
  barisDiabaikan: number
}

export interface SupervisiRingkas {
  id: string
  guruDinilai: string
  pemberiUmpanBalik: string
  tanggal: string
}

export interface ButirIsi {
  nomor: number
  bukti: string
  catatan: string
}

export interface Supervisi extends SupervisiRingkas {
  butir: ButirIsi[]
  refleksi: string[]
}

export interface Instrumen {
  bagian: {
    judul: string
    butir: { nomor: number; paragraf: { teks: string; daftar: boolean }[] }[]
  }[]
  refleksi: { nomor: number; pertanyaan: string }[]
}
