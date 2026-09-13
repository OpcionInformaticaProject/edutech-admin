<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use App\Models\Enrollment;
use Illuminate\Http\Request;

class PortfolioController extends Controller
{
    public function __invoke(Request $request)
    {
        $enrollments = Enrollment::query()->with(['student', 'group.course', 'group.campus'])
            ->withSum('validPayments as valid_payments_sum_amount', 'amount')
            ->when($request->filled('campus'), fn ($query) => $query->whereHas('group', fn ($group) => $group->where('campus_id', $request->string('campus'))))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->boolean('needs_review'), fn ($query) => $query->where('data_status', 'needs_review'))
            ->when($request->filled('search'), fn ($query) => $query->whereHas('student', fn ($student) => $student->whereRaw("concat(first_name, ' ', last_name) ilike ?", ['%'.$request->string('search').'%'])))
            ->latest()->paginate(25)->withQueryString();

        return view('portfolio.index', ['enrollments' => $enrollments, 'campuses' => Campus::orderBy('name')->get()]);
    }
}
