<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Models\Enrollment;
use App\Services\PaymentService;

class PaymentController extends Controller
{
    public function create(Enrollment $enrollment)
    {
        return view('payments.create', ['enrollment' => $enrollment->load(['student', 'group.course'])]);
    }

    public function store(StorePaymentRequest $request, Enrollment $enrollment, PaymentService $service)
    {
        $payment = $service->register($enrollment->load(['student', 'group.course', 'group.campus']), $request->validated(), $request->user());

        return redirect()->route('receipts.show', $payment->receipt)->with('success', 'Abono registrado correctamente.');
    }
}
