import type { FormEvent } from "react"
import { useForm } from "@inertiajs/react"

import type { Kelas } from "@/lib/types"
import { Button } from "@/components/ui/button"
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog"
import { Field, FieldDescription, FieldGroup, FieldLabel } from "@/components/ui/field"
import { Input } from "@/components/ui/input"

interface KelasDialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  /** Kelas yang diubah; kosong berarti menambah kelas baru. */
  kelas?: Kelas
  /** Tahun ajaran awal untuk kelas baru. */
  tahunAjaranAwal: string
}

// Isi dialog dirender hanya saat terbuka, jadi isian selalu mulai dari nilai awal yang benar.
function KelasForm({ onOpenChange, kelas, tahunAjaranAwal }: Omit<KelasDialogProps, "open">) {
  const { data, setData, post, patch, processing, errors, transform } = useForm({
    nama: kelas?.nama ?? "",
    tahunAjaran: kelas?.tahunAjaran ?? tahunAjaranAwal,
  })

  function handleSubmit(event: FormEvent) {
    event.preventDefault()
    transform((values) => ({ nama: values.nama.trim(), tahunAjaran: values.tahunAjaran.trim() }))
    const options = { preserveScroll: true, onSuccess: () => onOpenChange(false) }
    if (kelas) patch(`/kelas/${kelas.id}`, options)
    else post("/kelas", options)
  }

  return (
    <>
      <DialogHeader>
        <DialogTitle>{kelas ? "Ubah Kelas" : "Tambah Kelas"}</DialogTitle>
      </DialogHeader>
      <form onSubmit={handleSubmit}>
        <FieldGroup>
          <Field>
            <FieldLabel htmlFor="nama-kelas">Nama Kelas</FieldLabel>
            <Input
              id="nama-kelas"
              required
              autoFocus
              placeholder="X TKJ 1"
              value={data.nama}
              onChange={(e) => setData("nama", e.target.value)}
              aria-invalid={!!errors.nama}
            />
            {errors.nama && (
              <p className="text-sm text-destructive" role="alert">
                {errors.nama}
              </p>
            )}
          </Field>
          <Field>
            <FieldLabel htmlFor="tahun-ajaran">Tahun Ajaran</FieldLabel>
            <Input
              id="tahun-ajaran"
              required
              placeholder="2026/2027"
              inputMode="numeric"
              value={data.tahunAjaran}
              onChange={(e) => setData("tahunAjaran", e.target.value)}
              aria-invalid={!!errors.tahunAjaran}
            />
            {errors.tahunAjaran ? (
              <p className="text-sm text-destructive" role="alert">
                {errors.tahunAjaran}
              </p>
            ) : (
              <FieldDescription>Format 2026/2027.</FieldDescription>
            )}
          </Field>
          <DialogFooter>
            <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
              Batal
            </Button>
            <Button type="submit" disabled={processing}>
              {processing ? "Menyimpan..." : "Simpan"}
            </Button>
          </DialogFooter>
        </FieldGroup>
      </form>
    </>
  )
}

export function KelasDialog({ open, onOpenChange, kelas, tahunAjaranAwal }: KelasDialogProps) {
  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <KelasForm onOpenChange={onOpenChange} kelas={kelas} tahunAjaranAwal={tahunAjaranAwal} />
      </DialogContent>
    </Dialog>
  )
}
