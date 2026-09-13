@extends('layouts.app', ['title' => 'Apariencia / Marca'])
@section('content')
<div class="mx-auto max-w-3xl"><div class="card p-6 lg:p-8"><div class="mb-7"><h2 class="text-xl font-black">Identidad de {{ $organization->name }}</h2><p class="mt-2 text-sm text-slate-500">Los logotipos mantienen sus proporciones. El logo de acceso usa el principal cuando no tiene uno propio.</p></div>
<form method="POST" action="{{ route('branding.update') }}" enctype="multipart/form-data" class="space-y-7">@csrf @method('PUT')
@foreach(['logo'=>'Logo principal','login_logo'=>'Logo para login (opcional)','favicon'=>'Favicon (opcional)'] as $field=>$label)<div class="grid gap-4 rounded-2xl border border-slate-200 p-5 sm:grid-cols-[10rem_1fr] sm:items-center"><div class="grid h-24 place-items-center rounded-xl bg-slate-100 p-3"><x-brand-logo :organization="$organization" :variant="$field" class="max-h-16 max-w-full" /></div><label class="block"><span class="font-bold">{{ $label }}</span><input class="input mt-2 w-full" type="file" name="{{ $field }}" accept=".png,.jpg,.jpeg,.webp,.svg"><small class="mt-1 block text-slate-500">PNG, JPG, WEBP o SVG. {{ $field === 'favicon' ? 'Máximo 512 KB.' : 'Máximo 2 MB.' }}</small>@error($field)<span class="error">{{ $message }}</span>@enderror</label></div>@endforeach
<button class="btn-primary">Guardar apariencia</button></form></div></div>
@endsection
