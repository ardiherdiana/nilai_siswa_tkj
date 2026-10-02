import type { FormEvent } from "react"
import { useForm } from "@inertiajs/react"

import { Button } from "@/components/ui/button"
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog"
import { Field, FieldGroup, FieldLabel } from "@/components/ui/field"
import { Input } from "@/components/ui/input"

interface NamaSiswaDialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  title: string
  /** Tujuan permintaan: menambah siswa (POST) atau mengubah namanya (PATCH). */
  action: { method: "post" | "patch"; url: string }
  namaAwal?: string
}

// Isi dialog dirender hanya saat terbuka, jadi isian selalu mulai dari nama awal.
function NamaForm({ onOpenChange, title, action, namaAwal = "" }: Omit<NamaSiswaDialogProps, "open">) {
  const { data, setData, post, patch, processing, errors, transform } = useForm({ nama: namaAwal })

  function handleSubmit(event: FormEvent) {
    event.preventDefault()
    transform((values) => ({ nama: values.nama.trim() }))
    const options = { preserveScroll: true, onSuccess: () => onOpenChange(false) }
    if (action.method === "post") post(action.url, options)
    else patch(action.url, options)
  }

  return (
    <>
      <DialogHeader>
        <DialogTitle>{title}</DialogTitle>
      </DialogHeader>
      <form onSubmit={handleSubmit}>
        <FieldGroup>
          <Field>
            <FieldLabel htmlFor="nama-siswa">Nama Siswa</FieldLabel>
            <Input
              id="nama-siswa"
              required
              autoFocus
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

export function NamaSiswaDialog({ open, onOpenChange, ...rest }: NamaSiswaDialogProps) {
  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <NamaForm onOpenChange={onOpenChange} {...rest} />
      </DialogContent>
    </Dialog>
  )
}
