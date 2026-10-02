import * as React from "react"
import { Link, router, usePage } from "@inertiajs/react"
import {
  ClipboardCheck,
  GraduationCap,
  LayoutDashboard,
  LogOut,
  UserRound,
  Users,
} from "lucide-react"

import { useAuth } from "@/context/auth-context"
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarGroup,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarRail,
  useSidebar,
} from "@/components/ui/sidebar"

interface NavItem {
  title: string
  url: string
  icon: React.ComponentType<{ className?: string }>
  /** Awalan jalur lain yang membuat item ini aktif (mis. daftar siswa milik menu Nilai Siswa). */
  alsoActiveFor?: string[]
}

interface NavGroup {
  label: string
  items: NavItem[]
}

const baseNav: NavGroup[] = [
  {
    label: "Menu",
    items: [
      { title: "Dashboard", url: "/dashboard", icon: LayoutDashboard },
      { title: "Nilai Siswa", url: "/nilai", icon: GraduationCap, alsoActiveFor: ["/kelas"] },
      { title: "Supervisi Guru", url: "/supervisi", icon: ClipboardCheck },
    ],
  },
]

const adminNav: NavGroup = {
  label: "Sistem",
  items: [
    { title: "Akun", url: "/akun", icon: Users },
    { title: "Profil Saya", url: "/profil", icon: UserRound },
  ],
}

const guruNav: NavGroup = {
  label: "Sistem",
  items: [{ title: "Profil Saya", url: "/profil", icon: UserRound }],
}

function isActive(pathname: string, item: NavItem): boolean {
  return [item.url, ...(item.alsoActiveFor ?? [])].some(
    (prefix) => pathname === prefix || pathname.startsWith(`${prefix}/`)
  )
}

export function AppSidebar({ ...props }: React.ComponentProps<typeof Sidebar>) {
  const { user } = useAuth()
  const { url } = usePage()
  const pathname = url.split("?")[0]
  const { isMobile, setOpenMobile } = useSidebar()
  const groups = [...baseNav, user?.role === "ADMIN" ? adminNav : guruNav]

  function logout() {
    router.post("/logout")
  }

  return (
    <Sidebar {...props}>
      <SidebarHeader>
        <div className="flex items-center gap-2 px-2 py-1.5">
          <img src="/logo_smk.png" alt="Logo SMK Budi Mulia" className="size-7 shrink-0 object-contain" />
          <div className="flex flex-col leading-none">
            <span className="font-medium">NILAI SISWA TKJ & Supervisi Guru</span>
            <span className="text-xs text-muted-foreground">SMK Budi Mulia</span>
          </div>
        </div>
      </SidebarHeader>
      <SidebarContent>
        {groups.map((group) => (
          <SidebarGroup key={group.label}>
            <SidebarGroupLabel>{group.label}</SidebarGroupLabel>
            <SidebarGroupContent>
              <SidebarMenu>
                {group.items.map((item) => (
                  <SidebarMenuItem key={item.title}>
                    <SidebarMenuButton
                      isActive={isActive(pathname, item)}
                      onClick={() => isMobile && setOpenMobile(false)}
                      render={<Link href={item.url} />}
                    >
                      <item.icon />
                      {item.title}
                    </SidebarMenuButton>
                  </SidebarMenuItem>
                ))}
              </SidebarMenu>
            </SidebarGroupContent>
          </SidebarGroup>
        ))}
      </SidebarContent>
      <SidebarFooter>
        <SidebarMenu>
          <SidebarMenuItem>
            <div className="flex items-center justify-between gap-2 px-2 py-1.5 text-sm">
              <span className="truncate font-medium">{user?.name ?? user?.username}</span>
            </div>
          </SidebarMenuItem>
          <SidebarMenuItem>
            <SidebarMenuButton onClick={logout}>
              <LogOut />
              Keluar
            </SidebarMenuButton>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarFooter>
      <SidebarRail />
    </Sidebar>
  )
}
