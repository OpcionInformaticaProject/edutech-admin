<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Payment;
use App\Services\OperationalMetrics;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(OperationalMetrics $metrics): View
    {
        $organizationId = auth()->user()->organization_id;

        return view('dashboard', [
            ...$metrics->dashboard($organizationId),
            'recentEnrollments' => Enrollment::whereHas('student', fn ($q) => $q->where('organization_id', $organizationId))->with(['student', 'group.course'])->latest()->take(6)->get(),
            'recentPayments' => Payment::where('status', 'valid')->whereHas('enrollment.student', fn ($q) => $q->where('organization_id', $organizationId))->with(['enrollment.student', 'receipt'])->latest('payment_date')->take(6)->get(),
            'reviewAlerts' => Enrollment::where('data_status', 'needs_review')->whereHas('student', fn ($q) => $q->where('organization_id', $organizationId))->count(),
            'topGroups' => DB::table('groups')->join('courses', 'courses.id', '=', 'groups.course_id')->join('programs', 'programs.id', '=', 'courses.program_id')->join('enrollments', 'enrollments.group_id', '=', 'groups.id')->where('programs.organization_id', $organizationId)->whereNotNull('enrollments.agreed_amount')->groupBy('groups.id', 'groups.name', 'courses.name')->select('groups.name', 'courses.name as course_name')->selectRaw("SUM(CASE WHEN enrollments.agreed_amount - COALESCE((SELECT SUM(p.amount) FROM payments p WHERE p.enrollment_id = enrollments.id AND p.status = 'valid'),0) > 0 THEN enrollments.agreed_amount - COALESCE((SELECT SUM(p.amount) FROM payments p WHERE p.enrollment_id = enrollments.id AND p.status = 'valid'),0) ELSE 0 END) as pending")->orderByDesc('pending')->limit(5)->get(),
        ]);
    }
}
