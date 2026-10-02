import * as React from "react"
import { Head, Link, router } from "@inertiajs/react"
import { Pencil, Trash2, UserPlus } from "lucide-react"

import { downloadFile } from "@/lib/api"
import { namaBerkas } from "@/lib/format"
import type { Kelas, SiswaRingkas } from "@/lib/types"
import { ExportMenu } from "@/components/export-menu"
import { KelasDialog } from "@/components/kelas-dialog"
import { NamaSiswaDialog } from "@/components/nama-siswa-dialog"
import { Button } from "@/components/ui/button"
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from "@/components/ui/breadcrumb"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"

interface Props {
  kelas: Kelas
  totalNilai: number
  siswa: SiswaRingkas[]
}

export function SiswaPage({ kelas, totalNilai, siswa }: Props) {
  const [tambahOpen, setTambahOpen] = React.useState(false)
  const [ubahOpen, setUbahOpen] = React.useState(false)

  function handleHapusKelas() {
    if (
      !window.confirm(
        `Hapus kelas "${kelas.nama}"? Semua siswa dan nilai di kelas ini ikut terhapus dan tidak bisa dikembalikan.`
      )
    ) {
      return
    }
    router.delete(`/kelas/${kelas.id}`)
  }

  return (
    <div className="flex flex-col gap-4">
      <Head title={`Kelas ${kelas.nama}`} />
      <Breadcrumb>
        <BreadcrumbList>
          <BreadcrumbItem>
            <BreadcrumbLink render={<Link href="/nilai" />}>Nilai Siswa</BreadcrumbLink>
          </BreadcrumbItem>
          <BreadcrumbSeparator />
          <BreadcrumbItem>
            <BreadcrumbPage>{kelas.nama}</BreadcrumbPage>
          </BreadcrumbItem>
        </BreadcrumbList>
      </Breadcrumb>

      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="text-xl font-semibold">{kelas.nama}</h1>
          <p className="text-sm text-muted-foreground">
            Tahun ajaran {kelas.tahunAjaran}, {siswa.length} siswa
          </p>
        </div>
        <div className="flex flex-wrap gap-2">
          <ExportMenu
            formats={["xlsx", "pdf"]}
            disabled={siswa.length === 0}
            onExport={(format) =>
              downloadFile(
                "/nilai/ekspor",
                { format, kelasId: kelas.id },
                namaBerkas(["Nilai-Siswa", kelas.nama, kelas.tahunAjaran.replace("/", "-")], format)
              )
            }
          />
          <Button variant="outline" onClick={() => setUbahOpen(true)}>
            <Pencil /> Ubah Kelas
          </Button>
          <Button variant="destructive" onClick={handleHapusKelas}>
            <Trash2 /> Hapus Kelas
          </Button>
          <Button onClick={() => setTambahOpen(true)}>
            <UserPlus /> Tambah Siswa
          </Button>
        </div>
      </div>

      <div className="overflow-x-auto rounded-lg border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead className="w-10">No</TableHead>
              <TableHead>Nama Siswa</TableHead>
              <TableHead>Nilai Terisi</TableHead>
              <TableHead className="text-right">Aksi</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {siswa.length === 0 ? (
              <TableRow>
                <TableCell colSpan={4} className="text-center text-muted-foreground">
                  Belum ada siswa. Klik Tambah Siswa untuk memulai.
                </TableCell>
              </TableRow>
            ) : (
              siswa.map((s, index) => (
                <TableRow key={s.id}>
                  <TableCell className="text-muted-foreground">{index + 1}</TableCell>
                  <TableCell>
                    <Link
                      href={`/kelas/${kelas.id}/siswa/${s.id}`}
                      className="font-medium hover:underline"
                    >
                      {s.nama}
                    </Link>
                  </TableCell>
                  <TableCell className="text-muted-foreground tabular-nums">
                    {s.terisi} dari {totalNilai}
                  </TableCell>
                  <TableCell className="text-right">
                    <Button
                      variant="outline"
                      size="sm"
                      render={<Link href={`/kelas/${kelas.id}/siswa/${s.id}`} />}
                    >
                      Isi Nilai
                    </Button>
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </div>

      <NamaSiswaDialog
        open={tambahOpen}
        onOpenChange={setTambahOpen}
        title="Tambah Siswa"
        action={{ method: "post", url: `/kelas/${kelas.id}/siswa` }}
      />
      <KelasDialog
        open={ubahOpen}
        onOpenChange={setUbahOpen}
        kelas={kelas}
        tahunAjaranAwal={kelas.tahunAjaran}
      />
    </div>
  )
}
