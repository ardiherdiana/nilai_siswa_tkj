import axios from "axios"

axios.defaults.withCredentials = true
axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest"

// Halaman dimuat lewat Inertia. Modul ini hanya untuk permintaan XHR yang hasilnya bukan halaman:
// unggah Excel (laporan JSON) dan unduh berkas (xlsx, docx, pdf).

export class ApiError extends Error {
  status: number

  constructor(status: number, message: string) {
    super(message)
    this.status = status
  }
}

interface ErrorBody {
  message?: string
  errors?: Record<string, string[]>
}

// Unduhan meminta blob, jadi isi galat JSON dari server ikut berupa blob.
async function readBody(data: unknown): Promise<ErrorBody | null> {
  try {
    if (data instanceof Blob) return JSON.parse(await data.text()) as ErrorBody
    return (data ?? null) as ErrorBody | null
  } catch {
    return null
  }
}

async function errorFrom(err: unknown): Promise<ApiError> {
  if (axios.isAxiosError(err)) {
    const status = err.response?.status ?? 0
    if (status === 0) return new ApiError(0, "Tidak bisa terhubung ke server")
    const body = await readBody(err.response?.data)
    const firstError = body?.errors ? Object.values(body.errors)[0]?.[0] : undefined
    return new ApiError(status, firstError ?? body?.message ?? `Permintaan gagal (${status})`)
  }
  return new ApiError(0, "Permintaan gagal")
}

export function errorMessage(err: unknown, fallback: string): string {
  return err instanceof ApiError ? err.message : fallback
}

export async function uploadFile<T>(path: string, fieldName: string, file: File): Promise<T> {
  const body = new FormData()
  body.append(fieldName, file)
  try {
    const res = await axios.post<T>(path, body)
    return res.data
  } catch (err) {
    throw await errorFrom(err)
  }
}

export async function downloadFile(
  path: string,
  query: Record<string, string | undefined>,
  filename: string
) {
  const params = new URLSearchParams()
  for (const [key, value] of Object.entries(query)) {
    if (value !== undefined) params.set(key, value)
  }

  let res
  try {
    res = await axios.get(`${path}?${params.toString()}`, { responseType: "blob" })
  } catch (err) {
    throw await errorFrom(err)
  }

  const objectUrl = URL.createObjectURL(res.data)
  const link = document.createElement("a")
  link.href = objectUrl
  link.download = filename
  document.body.appendChild(link)
  link.click()
  link.remove()
  URL.revokeObjectURL(objectUrl)
}
