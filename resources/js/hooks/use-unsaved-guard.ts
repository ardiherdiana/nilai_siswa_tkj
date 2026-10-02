import * as React from "react"
import { router } from "@inertiajs/react"

/**
 * Meminta konfirmasi sebelum meninggalkan halaman yang isiannya belum disimpan: perpindahan halaman Inertia
 * (tautan, tombol kembali) dan penutupan atau muat ulang tab. Permintaan simpan (bukan GET) tidak dijaga.
 */
export function useUnsavedGuard(dirty: boolean, message: string) {
  React.useEffect(() => {
    if (!dirty) return

    const stopInertia = router.on("before", (event) => {
      if (event.detail.visit.method !== "get") return
      if (!window.confirm(message)) event.preventDefault()
    })
    const onBeforeUnload = (event: BeforeUnloadEvent) => event.preventDefault()
    window.addEventListener("beforeunload", onBeforeUnload)

    return () => {
      stopInertia()
      window.removeEventListener("beforeunload", onBeforeUnload)
    }
  }, [dirty, message])
}
