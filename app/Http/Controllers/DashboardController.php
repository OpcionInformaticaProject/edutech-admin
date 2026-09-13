<?php

namespace App\Http\Controllers;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\Group;
use App\Models\Student;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', [
            'students' => Student::count(),
            'activeEnrollments' => Enrollment::where('status', EnrollmentStatus::Active)->count(),
            'pendingEnrollments' => Enrollment::where('status', EnrollmentStatus::Pending)->count(),
            'groups' => Group::where('active', true)->count(),
            'recentEnrollments' => Enrollment::with(['student', 'group.course'])->latest()->take(6)->get(),
        ]);
    }
}
