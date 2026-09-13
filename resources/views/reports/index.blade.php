@extends('layouts.app', ['title' => 'Informes'])
@section('content')
<div class="mb-7"><p class="text-slate-500">Información operativa basada exclusivamente en registros existentes.</p><h2 class="mt-1 text-2xl font-black">Centro de informes</h2></div>
<div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">@foreach($reports as $slug=>$label)<a href="{{ route('reports.show',$slug) }}" class="card group transition hover:-translate-y-1 hover:border-teal-300"><div class="mb-5 grid size-11 place-items-center rounded-xl bg-teal-50 font-black text-teal-700">{{ $loop->iteration }}</div><h3 class="text-lg font-black">{{ $label }}</h3><p class="mt-2 text-sm text-slate-500">Consultar filtros, totales y detalle.</p><span class="mt-5 inline-block text-sm font-bold text-teal-700">Abrir informe →</span></a>@endforeach</div>
@endsection
