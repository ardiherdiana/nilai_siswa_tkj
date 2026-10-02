import { StrictMode } from "react"
import { createRoot } from "react-dom/client"
import { createInertiaApp, router } from "@inertiajs/react"
import { toast } from "sonner"

import "./index.css"
import { ThemeProvider } from "@/components/theme-provider.tsx"
import { Toaster } from "@/components/ui/sonner.tsx"
import { DashboardLayout } from "@/layouts/dashboard-layout"
import type { FlashToast } from "@/lib/types"

// Halaman tanpa sidebar. Semua halaman lain dibungkus DashboardLayout.
const BARE_PAGES = new Set(["login-page"])

window.addEventListener("pageshow", (event) => {
  if (event.persisted) {
    window.location.reload()
  }
})

// Toast dari flash sesi server (`->with('toast', [...])`).
router.on("success", (event) => {
  const flash = (event.detail.page.props as { flash?: { toast?: FlashToast | null } }).flash
  const message = flash?.toast
  if (!message) return
  const show = toast[message.type] ?? toast.info
  show(message.message)
})

type PageModule = Record<string, React.ComponentType>

// Halaman dimuat per kebutuhan supaya bundle awal (halaman masuk) tetap kecil.
const pages = import.meta.glob("./pages/**/*.tsx") as Record<string, () => Promise<PageModule>>

createInertiaApp({
  title: (title) => (title ? `${title} - NILAI SISWA TKJ & Supervisi Guru` : "NILAI SISWA TKJ & Supervisi Guru - SMK Budi Mulia"),
  resolve: async (name) => {
    const load = pages[`./pages/${name}.tsx`]
    if (!load) throw new Error(`Page not found: ${name}`)

    const component = Object.values(await load())[0]

    if (!BARE_PAGES.has(name)) {
      // @ts-expect-error - Inertia's persistent-layout convention
      component.layout = (page: React.ReactNode) => <DashboardLayout>{page}</DashboardLayout>
    }

    return component
  },
  setup({ el, App, props }) {
    createRoot(el).render(
      <StrictMode>
        <ThemeProvider>
          <App {...props} />
          <Toaster />
        </ThemeProvider>
      </StrictMode>
    )
  },
})
