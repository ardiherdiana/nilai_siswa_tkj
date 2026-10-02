import * as React from "react"
import { CalendarIcon } from "lucide-react"

import { cn } from "@/lib/utils"
import { dariIso, keIso } from "@/lib/format"
import { Button } from "@/components/ui/button"
import { Calendar } from "@/components/ui/calendar"
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover"

interface DatePickerProps {
  id?: string
  value: string
  onChange: (value: string) => void
  className?: string
}

export function DatePicker({ id, value, onChange, className }: DatePickerProps) {
  const [open, setOpen] = React.useState(false)
  const selected = value ? dariIso(value) : undefined

  return (
    <Popover open={open} onOpenChange={setOpen}>
      <PopoverTrigger
        render={
          <Button
            id={id}
            variant="outline"
            className={cn("w-40 justify-start font-normal", className)}
          />
        }
      >
        <CalendarIcon />
        {selected
          ? selected.toLocaleDateString("id-ID", {
              day: "2-digit",
              month: "short",
              year: "numeric",
            })
          : "Pilih tanggal"}
      </PopoverTrigger>
      <PopoverContent className="w-auto p-0">
        <Calendar
          mode="single"
          selected={selected}
          onSelect={(date) => {
            if (date) onChange(keIso(date))
            setOpen(false)
          }}
        />
      </PopoverContent>
    </Popover>
  )
}
