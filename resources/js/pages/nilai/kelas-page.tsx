import * as React from "react"
import { Head, Link, router } from "@inertiajs/react"
import { Download, Plus, Upload } from "lucide-react"
import { toast } from "sonner"

import { downloadFile, errorMessage, uploadFile } from "@/lib/api"
import { namaBerkas } from "@/lib/format"
import type { ImportHasil, Kelas } from "@/lib/types"
import { ExportMenu } from "@/components/export-menu"
import { KelasDialog } from "@/components/kelas-dialog"
import { Button } from "@/components/ui/button"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"

interface Props {
  kelas: Kelas[]
  tahunAjaranSekarang: string
}

function kelompokkan(kelas: Kelas[]): [string, Kelas[]][] {
  // Server sudah mengurutkan (tahun ajaran terbaru, lalu X < XI < XII); di sini hanya dikelompokkan.
  const peta = new Map<string, Kelas[]>()
  for (const k of kelas) peta.set(k.tahunAjaran, [...(peta.get(k.tahunAjaran) ?? []), k])
  return [...peta.entries()]
}

export function KelasPage({ kelas, tahunAjaranSekarang }: Props) {
  const fileInputRef = React.useRef<HTMLInputElement>(null)
  const [tambahOpen, setTambahOpen] = React.useState(false)
  const [mengimpor, setMengimpor] = React.useState(false)
  const [hasilImpor, setHasilImpor] = React.useState<ImportHasil | null>(null)
  const [mengunduhFormat, setMengunduhFormat] = React.useState(false)

  const kelompok = kelompokkan(kelas)

  async function handleImpor(event: React.ChangeEvent<HTMLInputElement>) {
    const file = event.target.files?.[0]
    event.target.value = ""
    if (!file) return

    setMengimpor(true)
    try {
      setHasilImpor(await uploadFile<ImportHasil>("/nilai/impor", "berkas", file))
      router.reload({ only: ["kelas"] })
    } catch (err) {
      toast.error(errorMessage(err, "Gagal mengimpor berkas"))
    } finally {
      setMengimpor(false)
    }
  }

  async function handleFormatKosong() {
    setMengunduhFormat(true)
    try {
      await downloadFile("/nilai/format-kosong", {}, "Format-Nilai-Siswa.xlsx")
    } catch (err) {
      toast.error(errorMessage(err, "Gagal mengunduh format"))
    } finally {
      setMengunduhFormat(false)
    }
  }

  return (
    <div className="flex flex-col gap-4">
      <Head title="Nilai Siswa" />
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="text-xl font-semibold">Nilai Siswa</h1>
          <p className="text-sm text-muted-foreground">
            Pilih kelas untuk mengisi nilai tes teori dan praktik
          </p>
        </div>
        <div className="flex flex-wrap gap-2">
          <input
            ref={fileInputRef}
            type="file"
            accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
            className="hidden"
            onChange={handleImpor}
          />
          <Button variant="outline" disabled={mengimpor} onClick={() => fileInputRef.current?.click()}>
            <Upload /> {mengimpor ? "Mengimpor..." : "Import Excel"}
          </Button>
          <Button variant="outline" disabled={mengunduhFormat} onClick={handleFormatKosong}>
            <Download /> {mengunduhFormat ? "Mengunduh..." : "Format Excel Kosong"}
          </Button>
          <Button onClick={() => setTambahOpen(true)}>
            <Plus /> Tambah Kelas
          </Button>
        </div>
      </div>

      {kelompok.length === 0 ? (
        <div className="rounded-lg border p-6 text-center text-sm text-muted-foreground">
          Belum ada kelas. Import daftar siswa dari Excel berformat template, atau tambah kelas dan
          siswa satu per satu.
        </div>
      ) : (
        kelompok.map(([tahunAjaran, daftar]) => (
          <section key={tahunAjaran} className="flex flex-col gap-2">
            <div className="flex flex-wrap items-center justify-between gap-2">
              <h2 className="text-base font-semibold">Tahun Ajaran {tahunAjaran}</h2>
              <ExportMenu
                size="sm"
                formats={["xlsx", "pdf"]}
                onExport={(format) =>
                  downloadFile(
                    "/nilai/ekspor",
                    { format, tahunAjaran },
                    namaBerkas(["Nilai-Siswa", tahunAjaran.replace("/", "-")], format)
                  )
                }
              />
            </div>
            <div className="overflow-x-auto rounded-lg border">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead className="w-10">No</TableHead>
                    <TableHead>Kelas</TableHead>
                    <TableHead>Jumlah Siswa</TableHead>
                    <TableHead className="text-right">Aksi</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {daftar.map((k, index) => (
                    <TableRow key={k.id}>
                      <TableCell className="text-muted-foreground">{index + 1}</TableCell>
                      <TableCell>
                        <Link href={`/kelas/${k.id}`} className="font-medium hover:underline">
                          {k.nama}
                        </Link>
                      </TableCell>
                      <TableCell>{k.jumlahSiswa} siswa</TableCell>
                      <TableCell className="text-right">
                        <Button variant="outline" size="sm" render={<Link href={`/kelas/${k.id}`} />}>
                          Buka
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          </section>
        ))
      )}

      <KelasDialog
        open={tambahOpen}
        onOpenChange={setTambahOpen}
        tahunAjaranAwal={kelompok[0]?.[0] ?? tahunAjaranSekarang}
      />

      <Dialog open={hasilImpor !== null} onOpenChange={(open) => !open && setHasilImpor(null)}>
        <DialogContent>
          {hasilImpor && (
            <>
              <DialogHeader>
                <DialogTitle>Import Selesai</DialogTitle>
                <DialogDescription>
                  {hasilImpor.totalDitambah} siswa ditambahkan ke {hasilImpor.kelas.length} kelas
                  (tahun ajaran {hasilImpor.tahunAjaran}).
                  {hasilImpor.totalDilewati > 0 &&
                    ` ${hasilImpor.totalDilewati} siswa sudah ada dan dilewati.`}
                  {hasilImpor.barisDiabaikan > 0 &&
                    ` ${hasilImpor.barisDiabaikan} baris tanpa kelas diabaikan.`}
                </DialogDescription>
              </DialogHeader>
              <ul className="flex max-h-60 flex-col divide-y overflow-y-auto rounded-lg border text-sm">
                {hasilImpor.kelas.map((k) => (
                  <li key={k.nama} className="flex items-center justify-between gap-3 px-3 py-2">
                    <span className="font-medium">{k.nama}</span>
                    <span className="text-muted-foreground">
                      +{k.ditambah}
                      {k.dilewati > 0 && `, ${k.dilewati} dilewati`}
                    </span>
                  </li>
                ))}
              </ul>
              <DialogFooter>
                <Button onClick={() => setHasilImpor(null)}>Tutup</Button>
              </DialogFooter>
            </>
          )}
        </DialogContent>
      </Dialog>
    </div>
  )
}
