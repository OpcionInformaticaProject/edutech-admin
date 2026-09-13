<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    /**
     * Create a new class instance.
     */
    public function __construct(private ReceiptNumberGenerator $numbers) {}

    public function register(Enrollment $enrollment, array $data, User $user): Payment
    {
        return DB::transaction(function () use ($enrollment, $data, $user) {
            $previous = $enrollment->balance;
            $payment = $enrollment->payments()->create($data + ['registered_by' => $user->id, 'status' => 'valid', 'historical' => false]);
            [$sequence,$number] = $this->numbers->next($enrollment->group->campus);
            Receipt::create(['organization_id' => $user->organization_id, 'campus_id' => $enrollment->group->campus_id, 'payment_id' => $payment->id, 'sequence' => $sequence, 'number' => $number, 'issued_on' => $payment->payment_date, 'student_name' => $enrollment->student->full_name, 'course_name' => $enrollment->group->course->name, 'previous_balance' => $previous, 'payment_amount' => $payment->amount, 'new_balance' => $previous === null ? null : $previous - (float) $payment->amount, 'payment_method' => $payment->method, 'cashier_name' => $user->name, 'issued_by' => $user->id]);

            return $payment->load('receipt');
        });
    }
}
