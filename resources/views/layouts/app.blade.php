<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Panel' }} · {{ auth()->user()->organization?->name ?? 'EDUTECH' }}</title>
    @if(auth()->user()->organization?->brandingUrl('favicon'))<link rel="icon" href="{{ auth()->user()->organization->brandingUrl('favicon') }}">@endif
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-900 antialiased">
<div x-data="{ open:false }" class="min-h-screen lg:flex">
    <aside :class="open ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-40 flex w-72 flex-col bg-slate-950 text-white transition-transform lg:static lg:translate-x-0">
        <div class="flex h-20 items-center gap-3 border-b border-white/10 px-7">
            <x-brand-logo :organization="auth()->user()->organization" class="h-11 max-w-44 text-white" />
        </div>
        <nav class="flex-1 space-y-1 overflow-y-auto px-4 py-5 text-sm">
            <div class="nav-section">Inicio</div><a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">⌂ <span>Dashboard</span></a>
            <div class="nav-section">Estudiantes</div><a class="nav-link {{ request()->routeIs('students.*') ? 'active' : '' }}" href="{{ route('students.index') }}">♙ <span>Directorio</span></a>
            <div class="nav-section">Matrículas</div><a class="nav-link {{ request()->routeIs('enrollments.*') ? 'active' : '' }}" href="{{ route('enrollments.index') }}">▤ <span>Matrículas</span></a>
            <div class="nav-section">Cartera</div><a class="nav-link {{ request()->routeIs('portfolio.*') ? 'active' : '' }}" href="{{ route('portfolio.index') }}">$ <span>Cartera general</span></a>
            <div class="nav-section">Pagos</div><a class="nav-link {{ request()->routeIs('payments.*', 'receipts.*') || (request()->routeIs('reports.show') && in_array(request()->route('report'), ['revenue','student-payments'], true)) ? 'active' : '' }}" href="{{ route('reports.show','revenue') }}">◎ <span>Movimientos</span></a>
            <div class="nav-section">Informes</div><a class="nav-link {{ request()->routeIs('reports.index') || (request()->routeIs('reports.show') && !in_array(request()->route('report'), ['revenue','student-payments'], true)) ? 'active' : '' }}" href="{{ route('reports.index') }}">▦ <span>Informes operativos</span></a>
            <div class="nav-section">Administración</div><a class="nav-link {{ request()->routeIs('enrollment-reviews.*') ? 'active' : '' }}" href="{{ route('enrollment-reviews.index') }}">! <span>Datos por revisar</span></a><a class="nav-link {{ request()->routeIs('imports.*') ? 'active' : '' }}" href="{{ route('imports.create') }}">⇧ <span>Importar Excel</span></a>@if(in_array(auth()->user()->role->value, ['superadmin','admin'], true))<a class="nav-link {{ request()->routeIs('branding.*') ? 'active' : '' }}" href="{{ route('branding.edit') }}">◐ <span>Apariencia / Marca</span></a>@endif
        </nav>
        <div class="border-t border-white/10 p-4">
            <div class="mb-3 flex items-center gap-3 px-3"><div class="grid size-9 place-items-center rounded-full bg-teal-400/20 font-bold text-teal-300">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</div><div class="min-w-0"><div class="truncate text-sm font-semibold">{{ auth()->user()->name }}</div><div class="text-xs capitalize text-slate-400">{{ auth()->user()->role->value }}</div></div></div>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="w-full rounded-xl px-3 py-2 text-left text-sm text-slate-400 hover:bg-white/5 hover:text-white">Cerrar sesión</button></form>
        </div>
    </aside>
    <div x-show="open" @click="open=false" class="fixed inset-0 z-30 bg-slate-950/60 lg:hidden"></div>
    <main class="min-w-0 flex-1">
        <header class="flex h-20 items-center justify-between border-b border-slate-200 bg-white px-5 lg:px-9">
            <div class="flex items-center gap-4"><button @click="open=true" class="rounded-lg border border-slate-200 px-3 py-2 lg:hidden">☰</button><x-brand-logo :organization="auth()->user()->organization" class="hidden h-9 max-w-32 sm:block lg:hidden" /><div><p class="text-xs font-semibold uppercase tracking-wider text-teal-600">{{ auth()->user()->organization?->name ?? 'EDUTECH' }} Admin</p><h1 class="text-xl font-bold">{{ $title ?? 'Panel' }}</h1></div></div>
            <div class="hidden text-right sm:block"><p class="text-sm font-medium">{{ now()->translatedFormat('l, d \d\e F') }}</p><p class="text-xs text-slate-400">Sede Santa Rosa</p></div>
        </header>
        <div class="p-5 lg:p-9">
            @if(session('success'))<div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>@endif
            {{ $slot ?? '' }}
            @yield('content')
        </div>
    </main>
</div>
</body></html>
