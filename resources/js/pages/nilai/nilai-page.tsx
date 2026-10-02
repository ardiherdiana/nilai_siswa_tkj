import * as React from "react"
import { Head, Link, router } from "@inertiajs/react"
import { Pencil, Trash2 } from "lucide-react"

import { bacaAngka, tampilAngka } from "@/lib/format"
import { KOLOM_PRAKTIK, KOLOM_TES, type Kelas, type KolomNilai, type SiswaNilai } from "@/lib/types"
import { useUnsavedGuard } from "@/hooks/use-unsaved-guard"
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
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Field, FieldLabel } from "@/components/ui/field"
import { Input } from "@/components/ui/input"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"

interface Props {
  kelas: Kelas
  siswa: SiswaNilai
}

type Jenis = "tes" | "praktik"

const KOLOM: Record<Jenis, readonly KolomNilai[]> = { tes: KOLOM_TES, praktik: KOLOM_PRAKTIK }
const NAMA_JENIS: Record<Jenis, string> = { tes: "Tes", praktik: "Praktik" }
const RINGKAS_JENIS: Record<Jenis, string> = { tes: "Tes teori", praktik: "Praktik" }

/** Isian yang sedang diketik: hanya kolom yang disentuh yang ada di sini. */
type Draft = Partial<Record<KolomNilai, string>>

export function NilaiPage({ kelas, siswa }: Props) {
  const [jenis, setJenis] = React.useState<Jenis>("tes")
  const [draft, setDraft] = React.useState<Draft>({})
  const [ubahNamaOpen, setUbahNamaOpen] = React.useState(false)
  const [menyimpan, setMenyimpan] = React.useState(false)

  // Bandingkan draf dengan nilai di server: hanya sel yang benar-benar berubah yang dikirim.
  const { berubah, tidakValid } = React.useMemo(() => {
    const berubah: Partial<Record<KolomNilai, number | null>> = {}
    const tidakValid = new Set<KolomNilai>()
    for (const [kolom, teks] of Object.entries(draft) as [KolomNilai, string][]) {
      const hasil = bacaAngka(teks)
      if (!hasil.ok) tidakValid.add(kolom)
      else if (hasil.nilai !== siswa.nilai[kolom]) berubah[kolom] = hasil.nilai
    }
    return { berubah, tidakValid }
  }, [draft, siswa.nilai])

  const jumlahBerubah = Object.keys(berubah).length
  const dirty = jumlahBerubah > 0 || tidakValid.size > 0
  useUnsavedGuard(dirty, "Nilai yang belum disimpan akan hilang. Lanjutkan?")

  const teksIsian = (kolom: KolomNilai) => draft[kolom] ?? tampilAngka(siswa.nilai[kolom])
  // Terisi = ada di server, atau di draf yang sedang diketik (kosong di draf berarti sengaja dihapus).
  const terisi = (kolom: KolomNilai) =>
    draft[kolom] !== undefined ? draft[kolom].trim() !== "" : siswa.nilai[kolom] !== null

  function handleSimpan() {
    setMenyimpan(true)
    router.put(
      `/siswa/${siswa.id}/nilai`,
      { nilai: berubah },
      {
        preserveScroll: true,
        onSuccess: () => setDraft({}),
        onFinish: () => setMenyimpan(false),
      }
    )
  }

  function handleHapus() {
    if (
      !window.confirm(`Hapus ${siswa.nama}? Nilai siswa ini ikut terhapus dan tidak bisa dikembalikan.`)
    ) {
      return
    }
    router.delete(`/siswa/${siswa.id}`)
  }

  return (
    <div className="flex max-w-3xl flex-col gap-4">
      <Head title={siswa.nama} />
      <Breadcrumb>
        <BreadcrumbList>
          <BreadcrumbItem>
            <BreadcrumbLink render={<Link href="/nilai" />}>Nilai Siswa</BreadcrumbLink>
          </BreadcrumbItem>
          <BreadcrumbSeparator />
          <BreadcrumbItem>
            <BreadcrumbLink render={<Link href={`/kelas/${kelas.id}`} />}>{kelas.nama}</BreadcrumbLink>
          </BreadcrumbItem>
          <BreadcrumbSeparator />
          <BreadcrumbItem>
            <BreadcrumbPage>{siswa.nama}</BreadcrumbPage>
          </BreadcrumbItem>
        </BreadcrumbList>
      </Breadcrumb>

      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="text-xl font-semibold">{siswa.nama}</h1>
          <p className="text-sm text-muted-foreground">
            Kelas {kelas.nama}, tahun ajaran {kelas.tahunAjaran}
          </p>
        </div>
        <div className="flex gap-2">
          <Button variant="outline" onClick={() => setUbahNamaOpen(true)}>
            <Pencil /> Ubah Nama
          </Button>
          <Button variant="destructive" onClick={handleHapus}>
            <Trash2 /> Hapus
          </Button>
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="text-base">Nilai</CardTitle>
        </CardHeader>
        <CardContent className="flex flex-col gap-4">
          <Tabs value={jenis} onValueChange={(v) => setJenis(v as Jenis)}>
            <TabsList>
              <TabsTrigger value="tes">Tes Teori</TabsTrigger>
              <TabsTrigger value="praktik">Praktik</TabsTrigger>
            </TabsList>
            {(Object.keys(KOLOM) as Jenis[]).map((j) => (
              <TabsContent key={j} value={j} className="flex flex-col gap-4">
                <p className="text-sm text-muted-foreground tabular-nums">
                  {RINGKAS_JENIS[j]}:{" "}
                  {KOLOM[j].filter(terisi).length} dari {KOLOM[j].length} nilai terisi
                </p>
                <div className="grid gap-4 sm:grid-cols-2">
                  {KOLOM[j].map((kolom, i) => (
                    <Field key={kolom}>
                      <FieldLabel htmlFor={kolom}>
                        Nilai {NAMA_JENIS[j]} {i + 1}
                      </FieldLabel>
                      <Input
                        id={kolom}
                        inputMode="decimal"
                        maxLength={6}
                        placeholder="-"
                        className="tabular-nums"
                        value={teksIsian(kolom)}
                        aria-invalid={tidakValid.has(kolom)}
                        onFocus={(e) => e.target.select()}
                        onChange={(e) => setDraft((d) => ({ ...d, [kolom]: e.target.value }))}
                      />
                    </Field>
                  ))}
                </div>
              </TabsContent>
            ))}
          </Tabs>

          <p className="text-sm text-muted-foreground">
            Kosongkan isian untuk menghapus nilai. Rentang 0 sampai 100, boleh berkoma.
          </p>
          {tidakValid.size > 0 && (
            <p className="text-sm text-destructive" role="alert">
              Ada nilai yang bukan angka 0 sampai 100. Perbaiki isian yang bergaris merah.
            </p>
          )}
          <div>
            <Button disabled={jumlahBerubah === 0 || tidakValid.size > 0 || menyimpan} onClick={handleSimpan}>
              {menyimpan
                ? "Menyimpan..."
                : jumlahBerubah > 0
                  ? `Simpan ${jumlahBerubah} Perubahan`
                  : "Simpan"}
            </Button>
          </div>
        </CardContent>
      </Card>

      <NamaSiswaDialog
        open={ubahNamaOpen}
        onOpenChange={setUbahNamaOpen}
        title="Ubah Nama Siswa"
        action={{ method: "patch", url: `/siswa/${siswa.id}` }}
        namaAwal={siswa.nama}
      />
    </div>
  )
}
