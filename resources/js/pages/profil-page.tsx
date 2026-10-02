import type { FormEvent } from "react"
import { Head, useForm } from "@inertiajs/react"

import { useAuth } from "@/context/auth-context"
import { initials } from "@/lib/utils"
import { LogoutButton } from "@/components/logout-button"
import { Avatar, AvatarFallback } from "@/components/ui/avatar"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Field, FieldGroup, FieldLabel } from "@/components/ui/field"
import { Input } from "@/components/ui/input"

export function ProfilPage() {
  const { user } = useAuth()

  const passwordForm = useForm({
    oldPassword: "",
    newPassword: "",
    confirmPassword: "",
  })

  function handlePasswordSubmit(event: FormEvent) {
    event.preventDefault()

    if (passwordForm.data.newPassword !== passwordForm.data.confirmPassword) {
      passwordForm.setError("confirmPassword", "Konfirmasi password baru tidak cocok")
      return
    }

    passwordForm.transform(({ oldPassword, newPassword }) => ({ oldPassword, newPassword }))
    passwordForm.patch("/profil/password", {
      onSuccess: () => passwordForm.reset(),
    })
  }

  return (
    <div className="flex max-w-lg flex-col gap-6">
      <Head title="Profil Saya" />
      <div>
        <h1 className="text-xl font-semibold">Profil Saya</h1>
        <p className="text-sm text-muted-foreground">
          Kelola password akun {user?.username}
        </p>
      </div>

      <Card>
        <CardContent className="flex items-center gap-4">
          <Avatar size="lg">
            <AvatarFallback>{initials(user?.name ?? user?.username ?? "?")}</AvatarFallback>
          </Avatar>
          <div className="min-w-0">
            <div className="truncate font-medium">{user?.name}</div>
            <div className="truncate text-sm text-muted-foreground">{user?.username}</div>
          </div>
          <Badge variant="secondary" className="ml-auto">
            {user?.role === "ADMIN" ? "Admin" : "Guru"}
          </Badge>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle className="text-base">Ganti Password</CardTitle>
        </CardHeader>
        <CardContent>
          <form onSubmit={handlePasswordSubmit}>
            <FieldGroup>
              <Field>
                <FieldLabel htmlFor="oldPassword">Password Lama</FieldLabel>
                <Input
                  id="oldPassword"
                  type="password"
                  required
                  autoComplete="current-password"
                  value={passwordForm.data.oldPassword}
                  onChange={(e) => passwordForm.setData("oldPassword", e.target.value)}
                />
              </Field>
              <Field>
                <FieldLabel htmlFor="newPassword">Password Baru</FieldLabel>
                <Input
                  id="newPassword"
                  type="password"
                  required
                  minLength={8}
                  autoComplete="new-password"
                  value={passwordForm.data.newPassword}
                  onChange={(e) => passwordForm.setData("newPassword", e.target.value)}
                />
              </Field>
              <Field>
                <FieldLabel htmlFor="confirmPassword">Konfirmasi Password Baru</FieldLabel>
                <Input
                  id="confirmPassword"
                  type="password"
                  required
                  minLength={8}
                  autoComplete="new-password"
                  value={passwordForm.data.confirmPassword}
                  onChange={(e) => passwordForm.setData("confirmPassword", e.target.value)}
                />
              </Field>
              {(passwordForm.errors.oldPassword ??
                passwordForm.errors.newPassword ??
                passwordForm.errors.confirmPassword) && (
                <p className="text-sm text-destructive" role="alert">
                  {passwordForm.errors.oldPassword ??
                    passwordForm.errors.newPassword ??
                    passwordForm.errors.confirmPassword}
                </p>
              )}
              <Field>
                <Button type="submit" disabled={passwordForm.processing} className="w-fit">
                  {passwordForm.processing ? "Menyimpan..." : "Simpan Password"}
                </Button>
              </Field>
            </FieldGroup>
          </form>
        </CardContent>
      </Card>

      <LogoutButton />
    </div>
  )
}
