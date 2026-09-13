<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Services\OperationalMetrics;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(OperationalMetrics $metrics): View
    {
        return view('dashboard', [
            ...$metrics->dashboard(auth()->user()->organization_id),
            'recentEnrollments' => Enrollment::with(['student', 'group.course'])->latest()->take(6)->get(),
        ]);
    }
}
