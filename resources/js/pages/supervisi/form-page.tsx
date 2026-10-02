import * as React from "react"
import { Head, Link, router, useForm } from "@inertiajs/react"
import { Save, Trash2 } from "lucide-react"

import { downloadFile } from "@/lib/api"
import { namaBerkas } from "@/lib/format"
import type { ButirIsi, Instrumen, Supervisi } from "@/lib/types"
import { useUnsavedGuard } from "@/hooks/use-unsaved-guard"
import { DatePicker } from "@/components/date-picker"
import { ExportMenu } from "@/components/export-menu"
import { Button } from "@/components/ui/button"
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from "@/components/ui/breadcrumb"
import { Card, CardContent } from "@/components/ui/card"
import { Field, FieldDescription, FieldGroup, FieldLabel } from "@/components/ui/field"
import { Input } from "@/components/ui/input"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { Textarea } from "@/components/ui/textarea"

interface Props {
  supervisi: Supervisi | null
  instrumen: Instrumen
  pemberiDefault: string
  hariIni: string
}

interface FormData {
  guruDinilai: string
  pemberiUmpanBalik: string
  tanggal: string
  butir: ButirIsi[]
  refleksi: string[]
}

const BAGIAN_DATA = "data"
const BAGIAN_REFLEKSI = "refleksi"

type ButirInstrumen = Instrumen["bagian"][number]["butir"][number]

function ButirKartu({
  butir,
  isi,
  onBukti,
  onCatatan,
}: {
  butir: ButirInstrumen
  isi: ButirIsi
  onBukti: (teks: string) => void
  onCatatan: (teks: string) => void
}) {
  let urut = 0
  return (
    <Card>
      <CardContent className="flex flex-col gap-4">
        <div className="flex gap-3">
          <span className="w-6 shrink-0 text-base font-semibold text-secondary-foreground tabular-nums">
            {butir.nomor}
          </span>
          <div className="flex flex-col gap-1 text-sm">
            {butir.paragraf.map((p, i) => (
              <p key={i} className={p.daftar ? "pl-3" : undefined}>
                {p.daftar ? `${String.fromCharCode(96 + ++urut)}. ${p.teks}` : p.teks}
              </p>
            ))}
          </div>
        </div>
        <div className="grid gap-4 md:grid-cols-2">
          <Field>
            <FieldLabel htmlFor={`bukti-${butir.nomor}`}>Bukti Pembelajaran</FieldLabel>
            <Textarea
              id={`bukti-${butir.nomor}`}
              rows={3}
              placeholder="Apa yang teramati"
              value={isi.bukti}
              onChange={(e) => onBukti(e.target.value)}
            />
          </Field>
          <Field>
            <FieldLabel htmlFor={`catatan-${butir.nomor}`}>Catatan</FieldLabel>
            <Textarea
              id={`catatan-${butir.nomor}`}
              rows={3}
              placeholder="Umpan balik untuk guru"
              value={isi.catatan}
              onChange={(e) => onCatatan(e.target.value)}
            />
          </Field>
        </div>
      </CardContent>
    </Card>
  )
}

export function SupervisiFormPage({ supervisi, instrumen, pemberiDefault, hariIni }: Props) {
  const [bagian, setBagian] = React.useState(BAGIAN_DATA)

  const form = useForm<FormData>({
    guruDinilai: supervisi?.guruDinilai ?? "",
    pemberiUmpanBalik: supervisi?.pemberiUmpanBalik ?? pemberiDefault,
    tanggal: supervisi?.tanggal ?? hariIni,
    butir: supervisi?.butir ?? Array.from({ length: 15 }, (_, i) => ({ nomor: i + 1, bukti: "", catatan: "" })),
    refleksi: supervisi?.refleksi ?? ["", "", ""],
  })
  const { data, setData, errors } = form

  useUnsavedGuard(form.isDirty, "Isian lembar yang belum disimpan akan hilang. Lanjutkan?")

  const ubahButir = (nomor: number, kunci: "bukti" | "catatan", teks: string) =>
    setData(
      "butir",
      data.butir.map((b) => (b.nomor === nomor ? { ...b, [kunci]: teks } : b))
    )
  const ubahRefleksi = (i: number, teks: string) =>
    setData(
      "refleksi",
      data.refleksi.map((r, j) => (j === i ? teks : r))
    )

  // Bagian yang sudah berisi ditandai titik pada tabnya.
  const terisi = React.useMemo(() => {
    const ada = new Set<string>()
    for (const b of instrumen.bagian) {
      const nomor = b.butir.map((x) => x.nomor)
      if (data.butir.some((x) => nomor.includes(x.nomor) && (x.bukti.trim() || x.catatan.trim()))) {
        ada.add(b.judul)
      }
    }
    if (data.refleksi.some((r) => r.trim())) ada.add(BAGIAN_REFLEKSI)
    return ada
  }, [instrumen.bagian, data.butir, data.refleksi])

  const opsiBagian = [
    { value: BAGIAN_DATA, label: "Data" },
    ...instrumen.bagian.map((b) => ({ value: b.judul, label: b.judul })),
    { value: BAGIAN_REFLEKSI, label: "Refleksi" },
  ]

  function handleSimpan(event?: React.FormEvent) {
    event?.preventDefault()
    // Kesalahan isian ada di bagian Data; buka bagian itu supaya pesannya terlihat.
    const options = {
      preserveScroll: true,
      onError: () => setBagian(BAGIAN_DATA),
    }
    if (supervisi) form.put(`/supervisi/${supervisi.id}`, options)
    else form.post("/supervisi", options)
  }

  function handleHapus() {
    if (!supervisi) return
    if (!window.confirm("Hapus lembar supervisi ini? Lembar ini dihapus permanen dan tidak bisa dikembalikan.")) {
      return
    }
    router.delete(`/supervisi/${supervisi.id}`)
  }

  return (
    <form onSubmit={handleSimpan} className="flex max-w-4xl flex-col gap-4">
      <Head title={supervisi ? "Lembar Supervisi" : "Lembar Supervisi Baru"} />
      <Breadcrumb>
        <BreadcrumbList>
          <BreadcrumbItem>
            <BreadcrumbLink render={<Link href="/supervisi" />}>Supervisi Guru</BreadcrumbLink>
          </BreadcrumbItem>
          <BreadcrumbSeparator />
          <BreadcrumbItem>
            <BreadcrumbPage>{supervisi ? "Lembar Supervisi" : "Lembar Baru"}</BreadcrumbPage>
          </BreadcrumbItem>
        </BreadcrumbList>
      </Breadcrumb>

      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="text-xl font-semibold">{supervisi ? "Lembar Supervisi" : "Lembar Baru"}</h1>
          <p className="text-sm text-muted-foreground">
            Instrumen observasi implementasi dan refleksi perencanaan pembelajaran
          </p>
        </div>
        <div className="flex flex-wrap gap-2">
          {supervisi && (
            <ExportMenu
              formats={["docx", "pdf"]}
              disabled={form.isDirty}
              onExport={(format) =>
                downloadFile(
                  `/supervisi/${supervisi.id}/ekspor`,
                  { format },
                  namaBerkas(["Supervisi", supervisi.guruDinilai, supervisi.tanggal], format)
                )
              }
            />
          )}
          {supervisi && (
            <Button type="button" variant="destructive" onClick={handleHapus}>
              <Trash2 /> Hapus
            </Button>
          )}
          <Button type="submit" disabled={form.processing || (!!supervisi && !form.isDirty)}>
            <Save /> {form.processing ? "Menyimpan..." : supervisi ? "Simpan Perubahan" : "Simpan Lembar"}
          </Button>
        </div>
      </div>
      {supervisi && form.isDirty && (
        <p className="text-sm text-muted-foreground">Simpan perubahan dulu untuk mengekspor lembar ini.</p>
      )}

      <Tabs value={bagian} onValueChange={setBagian}>
        <div className="overflow-x-auto">
          <TabsList>
            {opsiBagian.map((o) => (
              <TabsTrigger key={o.value} value={o.value} className="px-3">
                {o.label}
                {terisi.has(o.value) && (
                  <span className="size-1.5 rounded-full bg-primary" aria-label="Sudah terisi" />
                )}
              </TabsTrigger>
            ))}
          </TabsList>
        </div>

        <TabsContent value={BAGIAN_DATA}>
          <Card>
            <CardContent>
              <FieldGroup>
                <Field>
                  <FieldLabel htmlFor="guruDinilai">Penyusun Perencanaan</FieldLabel>
                  <Input
                    id="guruDinilai"
                    required
                    value={data.guruDinilai}
                    onChange={(e) => setData("guruDinilai", e.target.value)}
                    aria-invalid={!!errors.guruDinilai}
                  />
                  {errors.guruDinilai ? (
                    <p className="text-sm text-destructive" role="alert">
                      {errors.guruDinilai}
                    </p>
                  ) : (
                    <FieldDescription>Guru yang disupervisi.</FieldDescription>
                  )}
                </Field>
                <Field>
                  <FieldLabel htmlFor="pemberiUmpanBalik">Pemberi Umpan Balik</FieldLabel>
                  <Input
                    id="pemberiUmpanBalik"
                    required
                    value={data.pemberiUmpanBalik}
                    onChange={(e) => setData("pemberiUmpanBalik", e.target.value)}
                    aria-invalid={!!errors.pemberiUmpanBalik}
                  />
                  {errors.pemberiUmpanBalik ? (
                    <p className="text-sm text-destructive" role="alert">
                      {errors.pemberiUmpanBalik}
                    </p>
                  ) : (
                    <FieldDescription>Nama supervisor; dicetak juga sebagai penanda tangan.</FieldDescription>
                  )}
                </Field>
                <Field>
                  <FieldLabel htmlFor="tanggal">Hari/Tanggal</FieldLabel>
                  <DatePicker id="tanggal" value={data.tanggal} onChange={(v) => setData("tanggal", v)} />
                  {errors.tanggal && (
                    <p className="text-sm text-destructive" role="alert">
                      {errors.tanggal}
                    </p>
                  )}
                </Field>
              </FieldGroup>
            </CardContent>
          </Card>
        </TabsContent>

        {instrumen.bagian.map((b) => (
          <TabsContent key={b.judul} value={b.judul} className="flex flex-col gap-4">
            {b.butir.map((butir) => (
              <ButirKartu
                key={butir.nomor}
                butir={butir}
                isi={data.butir.find((x) => x.nomor === butir.nomor)!}
                onBukti={(t) => ubahButir(butir.nomor, "bukti", t)}
                onCatatan={(t) => ubahButir(butir.nomor, "catatan", t)}
              />
            ))}
          </TabsContent>
        ))}

        <TabsContent value={BAGIAN_REFLEKSI} className="flex flex-col gap-4">
          {instrumen.refleksi.map((r, i) => (
            <Card key={r.nomor}>
              <CardContent className="flex flex-col gap-3">
                <Field>
                  <FieldLabel htmlFor={`refleksi-${r.nomor}`} className="leading-snug">
                    {r.nomor}. {r.pertanyaan}
                  </FieldLabel>
                  <Textarea
                    id={`refleksi-${r.nomor}`}
                    rows={4}
                    placeholder="Tulis jawaban"
                    value={data.refleksi[i]}
                    onChange={(e) => ubahRefleksi(i, e.target.value)}
                  />
                </Field>
              </CardContent>
            </Card>
          ))}
        </TabsContent>
      </Tabs>
    </form>
  )
}
