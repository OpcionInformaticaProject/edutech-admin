@extends('layouts.app', ['title' => 'Datos por revisar'])
@section('content')
<div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">Estas matrículas provienen del histórico y conservan datos incompletos. Los pagos, recibos y metadatos de origen no se modifican durante la revisión.</div>
<div class="space-y-5">
@forelse($enrollments as $enrollment)
@php($source = $enrollment->importRows->sortBy('source_row')->first())
<article class="card p-5 lg:p-7">
    <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-start"><div><div class="flex flex-wrap items-center gap-2"><h2 class="text-xl font-black">{{ $enrollment->student->full_name }}</h2><span class="badge bg-amber-100 text-amber-800">Requiere revisión</span></div><p class="mt-1 text-sm text-slate-500">{{ $enrollment->student->document_type ?: 'Sin documento' }} {{ $enrollment->student->document_number }}</p></div><a class="btn-primary" href="{{ route('enrollment-reviews.edit', $enrollment) }}">Revisar matrícula</a></div>
    <dl class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4"><div><dt>Teléfonos / contactos</dt><dd>{{ $enrollment->student->phone ?: 'Sin teléfono' }}@foreach($enrollment->student->contacts as $contact)<div>{{ $contact->phone }} · {{ $contact->relationship }}</div>@endforeach</dd></div><div><dt>Curso / grupo</dt><dd>{{ $enrollment->group->course->name }}<div>{{ $enrollment->group->name }}</div></dd></div><div><dt>Horario original</dt><dd>{{ data_get($source?->normalized_data, 'schedule') ?: 'No informado' }}</dd></div><div><dt>Origen</dt><dd>{{ $source?->source_sheet ?? 'Sin hoja' }} · fila {{ $source?->source_row ?? '—' }}</dd></div><div><dt>Valor acordado</dt><dd>{{ $enrollment->agreed_amount === null ? 'Valor pendiente de definir' : '$'.number_format($enrollment->agreed_amount, 0, ',', '.') }}</dd></div><div><dt>Pagos acumulados</dt><dd>${{ number_format($enrollment->total_paid, 0, ',', '.') }}</dd></div><div><dt>Saldo</dt><dd>{{ $enrollment->balance === null ? 'No calculable' : '$'.number_format($enrollment->balance, 0, ',', '.') }}</dd></div><div><dt>Motivo / inconsistencias</dt><dd>{{ implode(' · ', $source?->inconsistencies ?? []) ?: 'Dato marcado para revisión' }}</dd></div></dl>
</article>
@empty<div class="card p-12 text-center"><h2 class="text-xl font-black">No hay datos pendientes</h2><p class="mt-2 text-slate-500">Todas las matrículas importadas están completas o ya fueron revisadas.</p></div>@endforelse
</div><div class="mt-6">{{ $enrollments->links() }}</div>
@endsection
