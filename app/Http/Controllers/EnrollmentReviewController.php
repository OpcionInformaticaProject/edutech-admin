<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewEnrollmentRequest;
use App\Models\Enrollment;
use App\Models\EnrollmentReview;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnrollmentReviewController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeUser($request);
        $enrollments = Enrollment::query()->where('data_status', 'needs_review')
            ->with(['student.contacts', 'group.course', 'importRows'])
            ->withSum('validPayments as valid_payments_sum_amount', 'amount')->paginate(25);

        return view('enrollment-reviews.index', compact('enrollments'));
    }

    public function edit(Request $request, Enrollment $enrollment)
    {
        $this->authorizeUser($request);
        abort_unless($enrollment->data_status === 'needs_review', 404);
        $enrollment->load(['student.contacts', 'group.course', 'importRows']);
        $groups = Group::query()->whereHas('course.program', fn ($query) => $query->where('organization_id', $request->user()->organization_id))->with('course')->orderBy('name')->get();

        return view('enrollment-reviews.edit', compact('enrollment', 'groups'));
    }

    public function update(ReviewEnrollmentRequest $request, Enrollment $enrollment)
    {
        DB::transaction(function () use ($request, $enrollment) {
            $enrollment = Enrollment::query()->lockForUpdate()->findOrFail($enrollment->id);
            abort_unless($enrollment->data_status === 'needs_review', 422, 'La matrícula ya fue revisada.');
            $importRow = $enrollment->importRows()->oldest('source_row')->first();
            $before = $enrollment->only(['enrollment_date', 'billing_start_date', 'agreed_amount', 'group_id', 'status', 'data_status']);
            $enrollment->update($request->validated() + ['data_status' => 'complete']);
            EnrollmentReview::create(['enrollment_id' => $enrollment->id, 'import_row_id' => $importRow?->id, 'reviewed_by' => $request->user()->id, 'before_values' => $before, 'after_values' => $enrollment->fresh()->only(array_keys($before)), 'reviewed_at' => now()]);
        });

        return redirect()->route('enrollment-reviews.index')->with('success', 'Matrícula revisada y auditoría guardada.');
    }

    private function authorizeUser(Request $request): void
    {
        abort_unless(in_array($request->user()->role->value, ['superadmin', 'admin', 'cartera', 'coordinador'], true), 403);
    }
}
