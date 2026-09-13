<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Barryvdh\DomPDF\Facade\Pdf;

class ReceiptController extends Controller
{
    public function show(Receipt $receipt)
    {
        return view('receipts.show', ['receipt' => $receipt->load('payment.enrollment.student')]);
    }

    public function pdf(Receipt $receipt)
    {
        return Pdf::loadView('receipts.pdf', ['receipt' => $receipt->load('payment.enrollment.student')])->stream($receipt->number.'.pdf');
    }
}
