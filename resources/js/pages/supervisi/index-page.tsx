import { Head, Link, router } from "@inertiajs/react"
import { Plus, Trash2 } from "lucide-react"

import { tanggalPanjang } from "@/lib/format"
import type { SupervisiRingkas } from "@/lib/types"
import { Button } from "@/components/ui/button"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"

export function SupervisiIndexPage({ daftar }: { daftar: SupervisiRingkas[] }) {
  function handleHapus(item: SupervisiRingkas) {
    if (
      !window.confirm(
        `Hapus lembar supervisi "${item.guruDinilai}"? Lembar ini dihapus permanen dan tidak bisa dikembalikan.`
      )
    ) {
      return
    }
    router.delete(`/supervisi/${item.id}`, { preserveScroll: true })
  }

  return (
    <div className="flex flex-col gap-4">
      <Head title="Supervisi Guru" />
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="text-xl font-semibold">Supervisi Guru</h1>
          <p className="text-sm text-muted-foreground">
            Lembar observasi implementasi dan refleksi perencanaan pembelajaran
          </p>
        </div>
        <Button render={<Link href="/supervisi/baru" />}>
          <Plus /> Buat Lembar Baru
        </Button>
      </div>

      <div className="overflow-x-auto rounded-lg border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead className="w-10">No</TableHead>
              <TableHead>Penyusun Perencanaan</TableHead>
              <TableHead>Pemberi Umpan Balik</TableHead>
              <TableHead>Hari/Tanggal</TableHead>
              <TableHead className="text-right">Aksi</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {daftar.length === 0 ? (
              <TableRow>
                <TableCell colSpan={5} className="text-center text-muted-foreground">
                  Belum ada lembar supervisi. Klik Buat Lembar Baru untuk mencatat hasil observasi.
                </TableCell>
              </TableRow>
            ) : (
              daftar.map((item, index) => (
                <TableRow key={item.id}>
                  <TableCell className="text-muted-foreground">{index + 1}</TableCell>
                  <TableCell>
                    <Link href={`/supervisi/${item.id}`} className="font-medium hover:underline">
                      {item.guruDinilai}
                    </Link>
                  </TableCell>
                  <TableCell>{item.pemberiUmpanBalik}</TableCell>
                  <TableCell>{tanggalPanjang(item.tanggal)}</TableCell>
                  <TableCell className="text-right">
                    <div className="flex justify-end gap-1">
                      <Button variant="outline" size="sm" render={<Link href={`/supervisi/${item.id}`} />}>
                        Buka
                      </Button>
                      <Button
                        variant="ghost"
                        size="icon-sm"
                        aria-label={`Hapus lembar ${item.guruDinilai}`}
                        onClick={() => handleHapus(item)}
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
    </div>
  )
}
