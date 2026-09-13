<?php

namespace App\Services;

use App\Models\ChargeSchedule;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class OperationalMetrics
{
    public function dashboard(string $organizationId): array
    {
        $validPayments = DB::table('payments')->select('enrollment_id', DB::raw('SUM(amount) as paid'))
            ->where('status', 'valid')->groupBy('enrollment_id');
        $portfolioPending = Enrollment::query()->join('students', 'students.id', '=', 'enrollments.student_id')
            ->leftJoinSub($validPayments, 'payment_totals', 'payment_totals.enrollment_id', '=', 'enrollments.id')
            ->where('students.organization_id', $organizationId)->whereNotNull('agreed_amount')
            ->selectRaw('COALESCE(SUM(CASE WHEN agreed_amount - COALESCE(payment_totals.paid, 0) > 0 THEN agreed_amount - COALESCE(payment_totals.paid, 0) ELSE 0 END), 0) AS total')->value('total');
        $overdue = ChargeSchedule::query()->whereHas('enrollment.student', fn ($query) => $query->where('organization_id', $organizationId))
            ->where('status', 'pending')->whereNotNull('due_date')->whereDate('due_date', '<', today());
        $payments = Payment::query()->where('status', 'valid')->whereHas('enrollment.student', fn ($query) => $query->where('organization_id', $organizationId));

        return [
            'activeStudents' => Student::where('organization_id', $organizationId)->where('active', true)->count(),
            'activeEnrollments' => Enrollment::where('status', 'active')->whereHas('student', fn ($query) => $query->where('organization_id', $organizationId))->count(),
            'portfolioPending' => (float) $portfolioPending,
            'overduePortfolio' => (float) (clone $overdue)->sum('amount'),
            'todayRevenue' => (float) (clone $payments)->whereDate('payment_date', today())->sum('amount'),
            'monthRevenue' => (float) (clone $payments)->whereBetween('payment_date', [today()->startOfMonth(), today()->endOfMonth()])->sum('amount'),
            'delinquentStudents' => (clone $overdue)->distinct('enrollment_id')->count('enrollment_id'),
        ];
    }
}
