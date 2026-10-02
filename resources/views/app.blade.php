<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <meta name="description" content="NILAI SISWA TKJ &amp; Supervisi Guru SMK Budi Mulia" />
    <meta name="theme-color" content="#0e57a6" />

    <link rel="icon" href="/favicon.ico" sizes="any" />
    <link rel="apple-touch-icon" href="/apple-touch-icon-180x180.png" />
    <link rel="manifest" href="/manifest.json" />

    <meta name="mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
    <meta name="apple-mobile-web-app-title" content="NILAI SISWA TKJ &amp; Supervisi Guru" />

    <title>NILAI SISWA TKJ &amp; Supervisi Guru - SMK Budi Mulia</title>

    {{-- Terapkan tema sebelum React dimuat supaya tidak berkedip (kunci localStorage sama dengan ThemeProvider). --}}
    <script nonce="{{ Vite::cspNonce() }}">
        try {
            var t = localStorage.getItem('theme');
            var dark = t === 'dark' || ((t === null || t === 'system') && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.add(dark ? 'dark' : 'light');
        } catch (e) {}
    </script>

    @viteReactRefresh
    @vite(['resources/js/main.tsx'])
    @inertiaHead
</head>
<body class="h-full">
    @inertia
</body>
</html>
