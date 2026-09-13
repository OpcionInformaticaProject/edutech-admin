@extends('layouts.app', ['title' => 'Registrar abono'])
@section('content')
<div class="mx-auto max-w-2xl"><div class="card mb-5 p-6"><p class="text-sm text-slate-500">Estudiante</p><h2 class="text-xl font-black">{{ $enrollment->student->full_name }}</h2>@if($enrollment->data_status==='needs_review')<span class="badge bg-amber-100 text-amber-800">Requiere revisión</span>@endif<div class="mt-4 grid grid-cols-3 gap-3 text-center"><div><small>Acordado</small><strong class="block">{{ $enrollment->agreed_amount === null ? 'Pendiente' : '$'.number_format($enrollment->agreed_amount,0,',','.') }}</strong></div><div><small>Pagado</small><strong class="block">${{ number_format($enrollment->total_paid,0,',','.') }}</strong></div><div><small>Saldo</small><strong class="block text-rose-600">{{ $enrollment->balance === null ? 'No calculable' : '$'.number_format($enrollment->balance,0,',','.') }}</strong></div></div></div>
<form class="card space-y-4 p-6" method="POST" action="{{ route('payments.store',$enrollment) }}">@csrf
<label class="block">Fecha<input class="input mt-1 w-full" type="date" name="payment_date" value="{{ old('payment_date',date('Y-m-d')) }}" required></label>
<label class="block">Valor<input class="input mt-1 w-full" type="number" min="1" step="0.01" name="amount" required></label>
<label class="block">Método<select class="input mt-1 w-full" name="method"><option value="cash">Efectivo</option><option value="nequi">Nequi</option><option value="transfer">Transferencia</option><option value="card">Tarjeta</option><option value="other">Otro</option></select></label>
<label class="block">Referencia<input class="input mt-1 w-full" name="reference"></label><label class="block">Observaciones<textarea class="input mt-1 w-full" name="notes"></textarea></label>
@if($errors->any())<div class="text-sm text-rose-600">{{ $errors->first() }}</div>@endif<button class="btn-primary w-full">Registrar y generar recibo</button></form></div>
@endsection
