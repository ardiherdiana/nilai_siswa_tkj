import { Head, Link } from "@inertiajs/react"
import { ClipboardCheck, GraduationCap, Plus, Users } from "lucide-react"

import { useAuth } from "@/context/auth-context"
import { tanggalPanjang } from "@/lib/format"
import { initials } from "@/lib/utils"
import { Avatar, AvatarFallback } from "@/components/ui/avatar"
import { Button } from "@/components/ui/button"
import { Card, CardAction, CardContent, CardHeader, CardTitle } from "@/components/ui/card"

interface Props {
  nilai: {
    tahunAjaran: string | null
    tahunAjaranSekarang: string
    jumlahKelas: number
    jumlahSiswa: number
  }
  supervisi: {
    total: number
    terbaru: { id: string; guruDinilai: string; tanggal: string }[]
  }
  akun: { total: number; aktif: number } | null
}

function Angka({ nilai, label }: { nilai: number; label: string }) {
  return (
    <div>
      <div className="text-2xl font-semibold tabular-nums">{nilai}</div>
      <p className="text-sm text-muted-foreground">{label}</p>
    </div>
  )
}

export function DashboardPage({ nilai, supervisi, akun }: Props) {
  const { user } = useAuth()

  return (
    <div className="flex flex-col gap-6">
      <Head title="Dashboard" />
      <div className="flex items-center gap-3">
        <Avatar size="lg">
          <AvatarFallback>{initials(user?.name ?? user?.username ?? "?")}</AvatarFallback>
        </Avatar>
        <div>
          <h1 className="text-xl font-semibold">Halo, {user?.name ?? user?.username}</h1>
          <p className="text-sm text-muted-foreground">Ringkasan pekerjaan Anda</p>
        </div>
      </div>

      <div className="grid gap-4 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-base">
              <GraduationCap className="size-4 text-muted-foreground" /> Nilai Siswa
            </CardTitle>
            <CardAction>
              <Button variant="outline" size="sm" render={<Link href="/nilai" />}>
                Buka
              </Button>
            </CardAction>
          </CardHeader>
          <CardContent className="flex flex-col gap-4">
            <p className="text-sm text-muted-foreground">
              Nilai tes teori dan praktik per kelas, ekspor ke Excel atau PDF.
            </p>
            {nilai.tahunAjaran ? (
              <>
                <p className="text-sm font-medium">Tahun ajaran {nilai.tahunAjaran}</p>
                <div className="grid grid-cols-2 gap-4">
                  <Angka nilai={nilai.jumlahKelas} label="Kelas" />
                  <Angka nilai={nilai.jumlahSiswa} label="Siswa" />
                </div>
              </>
            ) : (
              <p className="text-sm text-muted-foreground">
                Belum ada kelas untuk {nilai.tahunAjaranSekarang}.
              </p>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-base">
              <ClipboardCheck className="size-4 text-muted-foreground" /> Supervisi Guru
            </CardTitle>
            <CardAction>
              <Button variant="outline" size="sm" render={<Link href="/supervisi/baru" />}>
                <Plus /> Lembar Baru
              </Button>
            </CardAction>
          </CardHeader>
          <CardContent className="flex flex-col gap-4">
            <p className="text-sm text-muted-foreground">
              Lembar observasi pembelajaran, ekspor ke Word atau PDF.
            </p>
            <Angka nilai={supervisi.total} label="Lembar tersimpan" />
            {supervisi.terbaru.length > 0 && (
              <ul className="flex flex-col divide-y rounded-lg border text-sm">
                {supervisi.terbaru.map((s) => (
                  <li key={s.id}>
                    <Link
                      href={`/supervisi/${s.id}`}
                      className="flex flex-wrap items-center justify-between gap-x-3 px-3 py-2 hover:bg-muted"
                    >
                      <span className="font-medium">{s.guruDinilai}</span>
                      <span className="text-muted-foreground">{tanggalPanjang(s.tanggal)}</span>
                    </Link>
                  </li>
                ))}
              </ul>
            )}
          </CardContent>
        </Card>

        {akun && (
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-base">
                <Users className="size-4 text-muted-foreground" /> Akun
              </CardTitle>
              <CardAction>
                <Button variant="outline" size="sm" render={<Link href="/akun" />}>
                  Kelola
                </Button>
              </CardAction>
            </CardHeader>
            <CardContent className="flex flex-col gap-4">
              <p className="text-sm text-muted-foreground">
                Akun admin dan guru yang bisa masuk ke aplikasi ini.
              </p>
              <div className="grid grid-cols-2 gap-4">
                <Angka nilai={akun.aktif} label="Akun aktif" />
                <Angka nilai={akun.total} label="Semua akun" />
              </div>
            </CardContent>
          </Card>
        )}
      </div>
    </div>
  )
}
