import * as React from "react"
import { Head, router, useForm } from "@inertiajs/react"
import { Pencil, Plus, Trash2 } from "lucide-react"

import { useAuth } from "@/context/auth-context"
import type { Akun, Role } from "@/lib/types"
import { DataPagination } from "@/components/data-pagination"
import { Badge } from "@/components/ui/badge"
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
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"

interface FormState {
  id: string | null
  name: string
  username: string
  password: string
  role: Role
  aktif: boolean
}

const EMPTY_FORM: FormState = {
  id: null,
  name: "",
  username: "",
  password: "",
  role: "GURU",
  aktif: true,
}

const NAMA_ROLE: Record<Role, string> = { ADMIN: "Admin", GURU: "Guru" }

export function AkunIndexPage({ akunList }: { akunList: Akun[] }) {
  const { user } = useAuth()
  const [search, setSearch] = React.useState("")
  const [dialogOpen, setDialogOpen] = React.useState(false)
  const [page, setPage] = React.useState(1)
  const [pageSize, setPageSize] = React.useState(50)

  const { data, setData, post, patch, processing, errors, clearErrors, transform } =
    useForm<FormState>(EMPTY_FORM)

  const cari = search.trim().toLowerCase()
  const daftar = cari
    ? akunList.filter(
        (a) => a.name.toLowerCase().includes(cari) || a.username.toLowerCase().includes(cari)
      )
    : akunList
  const paginatedList = daftar.slice((page - 1) * pageSize, page * pageSize)

  function openCreate() {
    clearErrors()
    // Isian awal ditulis eksplisit: setelah simpan sukses, useForm menganggap isian terakhir sebagai nilai awal.
    setData(EMPTY_FORM)
    setDialogOpen(true)
  }

  function openEdit(akun: Akun) {
    clearErrors()
    setData({
      id: akun.id,
      name: akun.name,
      username: akun.username,
      password: "",
      role: akun.role,
      aktif: akun.aktif,
    })
    setDialogOpen(true)
  }

  function handleSubmit(event: React.FormEvent) {
    event.preventDefault()
    clearErrors()

    transform((formData) => ({
      name: formData.name,
      role: formData.role,
      ...(formData.id
        ? { aktif: formData.aktif, password: formData.password || undefined }
        : { username: formData.username, password: formData.password }),
    }))

    const options = { preserveScroll: true, onSuccess: () => setDialogOpen(false) }
    if (data.id) patch(`/akun/${data.id}`, options)
    else post("/akun", options)
  }

  function handleDelete(akun: Akun) {
    if (
      !window.confirm(
        `Hapus akun "${akun.name}"? Semua kelas, siswa, nilai, dan supervisi milik akun ini ikut terhapus dan tidak bisa dikembalikan. Untuk menutup akses saja, nonaktifkan akunnya.`
      )
    ) {
      return
    }
    router.delete(`/akun/${akun.id}`, { preserveScroll: true })
  }

  const formError =
    errors.name ??
    errors.username ??
    errors.password ??
    errors.role ??
    (errors as Record<string, string>).message

  return (
    <div className="flex flex-col gap-4">
      <Head title="Akun" />
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="text-xl font-semibold">Akun</h1>
          <p className="text-sm text-muted-foreground">Kelola akun admin dan guru yang bisa masuk ke aplikasi ini</p>
        </div>
        <Button onClick={openCreate}>
          <Plus /> Tambah Akun
        </Button>
      </div>

      <Input
        placeholder="Cari nama atau username..."
        value={search}
        onChange={(e) => {
          setSearch(e.target.value)
          setPage(1)
        }}
        className="max-w-sm"
      />

      <div className="overflow-x-auto rounded-lg border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead className="w-10">No</TableHead>
              <TableHead>Username</TableHead>
              <TableHead>Nama</TableHead>
              <TableHead>Role</TableHead>
              <TableHead>Status</TableHead>
              <TableHead className="text-right">Aksi</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {daftar.length === 0 ? (
              <TableRow>
                <TableCell colSpan={6} className="text-center text-muted-foreground">
                  Belum ada akun
                </TableCell>
              </TableRow>
            ) : (
              paginatedList.map((akun, index) => (
                <TableRow key={akun.id}>
                  <TableCell className="text-muted-foreground">
                    {(page - 1) * pageSize + index + 1}
                  </TableCell>
                  <TableCell className="font-mono text-xs">{akun.username}</TableCell>
                  <TableCell>{akun.name}</TableCell>
                  <TableCell>
                    <Badge variant={akun.role === "ADMIN" ? "secondary" : "outline"}>
                      {NAMA_ROLE[akun.role]}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <Badge variant={akun.aktif ? "success" : "destructive"}>
                      {akun.aktif ? "Aktif" : "Nonaktif"}
                    </Badge>
                  </TableCell>
                  <TableCell className="text-right">
                    <div className="flex justify-end gap-1">
                      <Button
                        variant="ghost"
                        size="icon-sm"
                        aria-label={`Edit ${akun.name}`}
                        onClick={() => openEdit(akun)}
                      >
                        <Pencil />
                      </Button>
                      <Button
                        variant="ghost"
                        size="icon-sm"
                        aria-label={`Hapus ${akun.name}`}
                        disabled={akun.id === user?.id}
                        onClick={() => handleDelete(akun)}
                      >
                        <Trash2 />
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </div>

      <DataPagination
        page={page}
        pageSize={pageSize}
        total={daftar.length}
        onPageChange={setPage}
        onPageSizeChange={(size) => {
          setPageSize(size)
          setPage(1)
        }}
      />

      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{data.id ? "Edit Akun" : "Tambah Akun"}</DialogTitle>
          </DialogHeader>
          <form onSubmit={handleSubmit}>
            <FieldGroup>
              <Field>
                <FieldLabel htmlFor="name">Nama</FieldLabel>
                <Input
                  id="name"
                  required
                  value={data.name}
                  onChange={(e) => setData("name", e.target.value)}
                />
              </Field>
              <Field>
                <FieldLabel htmlFor="username">Username</FieldLabel>
                <Input
                  id="username"
                  required
                  disabled={!!data.id}
                  autoCapitalize="none"
                  autoCorrect="off"
                  spellCheck={false}
                  value={data.username}
                  onChange={(e) => setData("username", e.target.value)}
                />
                {!data.id && (
                  <FieldDescription>
                    Huruf, angka, titik, garis bawah, dan garis hubung. Tidak bisa diubah setelah dibuat.
                  </FieldDescription>
                )}
              </Field>
              <Field>
                <FieldLabel htmlFor="role">Role</FieldLabel>
                <Select
                  items={{ GURU: "Guru", ADMIN: "Admin" }}
                  value={data.role}
                  onValueChange={(v) => v && setData("role", v as Role)}
                >
                  <SelectTrigger id="role" className="w-full">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="GURU">Guru</SelectItem>
                    <SelectItem value="ADMIN">Admin</SelectItem>
                  </SelectContent>
                </Select>
                <FieldDescription>
                  Admin bisa mengelola akun. Semua akun memakai nilai siswa dan supervisi dengan data masing-masing.
                </FieldDescription>
              </Field>
              <Field>
                <FieldLabel htmlFor="password">
                  {data.id ? "Ganti Password (opsional)" : "Password Awal"}
                </FieldLabel>
                <Input
                  id="password"
                  type="text"
                  required={!data.id}
                  minLength={8}
                  autoComplete="off"
                  placeholder={data.id ? "Kosongkan jika tidak diubah" : "Minimal 8 karakter"}
                  value={data.password}
                  onChange={(e) => setData("password", e.target.value)}
                />
              </Field>
              {data.id && (
                <Field orientation="horizontal">
                  <input
                    id="aktif"
                    type="checkbox"
                    className="size-4"
                    checked={data.aktif}
                    onChange={(e) => setData("aktif", e.target.checked)}
                  />
                  <FieldLabel htmlFor="aktif" className="font-normal">
                    Akun aktif (bisa masuk)
                  </FieldLabel>
                </Field>
              )}
              {formError && (
                <p className="text-sm text-destructive" role="alert">
                  {formError}
                </p>
              )}
              <DialogFooter>
                <Button type="submit" disabled={processing}>
                  {processing ? "Menyimpan..." : "Simpan"}
                </Button>
              </DialogFooter>
            </FieldGroup>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  )
}
