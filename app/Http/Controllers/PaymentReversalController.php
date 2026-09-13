<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReversePaymentRequest;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class PaymentReversalController extends Controller
{
    public function store(ReversePaymentRequest $request, Payment $payment)
    {
        abort_if($payment->status === 'reversed', 422, 'El pago ya fue anulado.');
        DB::transaction(function () use ($payment, $request) {
            $payment->reversal()->create(['reason' => $request->validated('reason'), 'reversed_by' => $request->user()->id, 'reversed_at' => now()]);
            $payment->update(['status' => 'reversed']);
        });

        return back()->with('success', 'Pago anulado mediante reversión.');
    }
}
