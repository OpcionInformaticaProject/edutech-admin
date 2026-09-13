<?php

namespace App\Http\Controllers;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\Group;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EnrollmentController extends Controller
{
    public function index(): View
    {
        return view('enrollments.index', ['enrollments' => Enrollment::with(['student', 'group.course'])->latest()->paginate(15)]);
    }

    public function create(Request $request): View
    {
        return view('enrollments.form', ['students' => Student::orderBy('last_name')->get(), 'groups' => Group::with('course')->where('active', true)->get(), 'statuses' => EnrollmentStatus::cases(), 'selectedStudent' => $request->string('student_id')]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['student_id' => ['required', 'exists:students,id'], 'group_id' => ['required', 'exists:groups,id', Rule::unique('enrollments')->where(fn ($q) => $q->where('student_id', $request->student_id))], 'enrollment_date' => ['required', 'date'], 'billing_start_date' => ['required', 'date'], 'agreed_amount' => ['required', 'numeric', 'min:0'], 'status' => ['required', Rule::enum(EnrollmentStatus::class)], 'notes' => ['nullable', 'string', 'max:2000']]);
        $enrollment = Enrollment::create($data);

        return redirect()->route('students.show', $enrollment->student)->with('success', 'Matrícula registrada.');
    }
}
