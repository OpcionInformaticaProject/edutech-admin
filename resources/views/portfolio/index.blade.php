@extends('layouts.app', ['title' => 'Cartera general'])
@section('content')
<form class="card mb-6 grid gap-3 p-5 md:grid-cols-5" method="GET">
    <input class="input" name="search" value="{{ request('search') }}" placeholder="Buscar estudiante">
    <select class="input" name="campus"><option value="">Todas las sedes</option>@foreach($campuses as $campus)<option value="{{ $campus->id }}" @selected(request('campus')===$campus->id)>{{ $campus->name }}</option>@endforeach</select>
    <select class="input" name="status"><option value="">Todos los estados</option>@foreach(\App\Enums\EnrollmentStatus::cases() as $status)<option value="{{ $status->value }}" @selected(request('status')===$status->value)>{{ \App\Support\Status::label($status) }}</option>@endforeach</select>
    <label class="flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3"><input type="checkbox" name="needs_review" value="1" @checked(request()->boolean('needs_review'))> Datos por revisar</label>
    <button class="btn-primary">Filtrar cartera</button>
</form>
<div class="card overflow-x-auto"><table class="w-full text-sm"><thead><tr><th>Estudiante</th><th>Curso / horario</th><th>Estado</th><th class="text-right">Acordado</th><th class="text-right">Pagado</th><th class="text-right">Saldo</th><th></th></tr></thead><tbody>
@forelse($enrollments as $enrollment)<tr><td><a class="font-bold text-teal-700" href="{{ route('students.show',$enrollment->student) }}">{{ $enrollment->student->full_name }}</a></td><td>{{ $enrollment->group->course->name }}<div class="text-xs text-slate-500">{{ $enrollment->group->name }}</div></td><td><span class="badge {{ \App\Support\Status::badge($enrollment->status) }}">{{ \App\Support\Status::label($enrollment->status) }}</span> @if($enrollment->data_status==='needs_review')<span class="badge status-pending">{{ \App\Support\Status::label($enrollment->data_status) }}</span>@endif</td><td class="text-right">{{ $enrollment->agreed_amount === null ? 'Valor pendiente de definir' : '$'.number_format($enrollment->agreed_amount,0,',','.') }}</td><td class="text-right text-emerald-700">${{ number_format($enrollment->total_paid,0,',','.') }}</td><td class="text-right text-lg font-black {{ $enrollment->balance !== null && $enrollment->balance > 0 ? 'text-rose-600':'text-emerald-700' }}">{{ $enrollment->balance === null ? 'No calculable' : '$'.number_format($enrollment->balance,0,',','.') }}</td><td><a class="btn-primary whitespace-nowrap" href="{{ route('payments.create',$enrollment) }}">Registrar abono</a></td></tr>
@empty<tr><td colspan="7" class="py-12 text-center text-slate-500">No hay matrículas para estos filtros.</td></tr>@endforelse
</tbody></table></div><div class="mt-5">{{ $enrollments->links() }}</div>
<p class="mt-4 text-xs text-amber-700">La mora y las clases pendientes no se calculan cuando la fecha de inicio de cobro no es confiable.</p>
@endsection
