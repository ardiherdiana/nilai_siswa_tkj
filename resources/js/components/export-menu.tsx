import * as React from "react"
import { Download, FileSpreadsheet, FileText } from "lucide-react"
import { toast } from "sonner"

import { errorMessage } from "@/lib/api"
import { Button } from "@/components/ui/button"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"

export type ExportFormat = "xlsx" | "docx" | "pdf"

const LABEL: Record<ExportFormat, string> = {
  xlsx: "Excel (.xlsx)",
  docx: "Word (.docx)",
  pdf: "PDF",
}

interface ExportMenuProps {
  formats: ExportFormat[]
  /** Mengunduh berkas dalam format yang dipilih. Galatnya ditampilkan sebagai toast. */
  onExport: (format: ExportFormat) => Promise<void>
  disabled?: boolean
  size?: "sm" | "default"
}

export function ExportMenu({ formats, onExport, disabled, size = "default" }: ExportMenuProps) {
  const [busy, setBusy] = React.useState(false)

  async function run(format: ExportFormat) {
    setBusy(true)
    try {
      await onExport(format)
    } catch (err) {
      toast.error(errorMessage(err, "Gagal mengunduh berkas"))
    } finally {
      setBusy(false)
    }
  }

  return (
    <DropdownMenu>
      <DropdownMenuTrigger
        render={<Button variant="outline" size={size} disabled={disabled || busy} />}
      >
        <Download /> {busy ? "Mengunduh..." : "Ekspor"}
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end">
        {formats.map((format) => (
          <DropdownMenuItem key={format} onClick={() => void run(format)}>
            {format === "pdf" ? <FileText /> : <FileSpreadsheet />}
            {LABEL[format]}
          </DropdownMenuItem>
        ))}
      </DropdownMenuContent>
    </DropdownMenu>
  )
}
