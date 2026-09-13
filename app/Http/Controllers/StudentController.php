<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use App\Models\Municipality;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function create(): View
    {
        return view('students.form', $this->formData(new Student));
    }

    public function show(Student $student): View
    {
        return view('students.show', compact('student'))->with('student', $student->load(['contacts', 'campus', 'municipality', 'enrollments.group.course']));
    }

    public function edit(Student $student): View
    {
        return view('students.form', $this->formData($student));
    }

    public function store(Request $request): RedirectResponse
    {
        $student = Student::create($this->validated($request) + ['organization_id' => $request->user()->organization_id]);

        return redirect()->route('students.show', $student)->with('success', 'Estudiante creado correctamente.');
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $student->update($this->validated($request, $student));

        return redirect()->route('students.show', $student)->with('success', 'Información actualizada.');
    }

    private function validated(Request $request, ?Student $student = null): array
    {
        return $request->validate([
            'document_type' => ['required', Rule::in(['TI', 'CC', 'RC', 'CE', 'PA'])],
            'document_number' => ['required', 'string', 'max:30', Rule::unique('students')->where(fn ($q) => $q->where('organization_id', $request->user()->organization_id))->ignore($student)],
            'first_name' => ['required', 'string', 'max:100'], 'last_name' => ['required', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'], 'gender' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'], 'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'], 'campus_id' => ['nullable', 'exists:campuses,id'],
            'municipality_id' => ['nullable', 'exists:municipalities,id'], 'active' => ['required', 'boolean'],
        ]);
    }

    private function formData(Student $student): array
    {
        return ['student' => $student, 'campuses' => Campus::orderBy('name')->get(), 'municipalities' => Municipality::orderBy('name')->get()];
    }
}
