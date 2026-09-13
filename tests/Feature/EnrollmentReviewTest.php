<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Group;
use App\Models\Import;
use App\Models\ImportRow;
use App\Models\Payment;
use App\Models\Program;
use App\Models\Receipt;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_validation_review_audit_and_historical_records_integrity(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::firstOrFail();
        $program = Program::create(['organization_id' => $user->organization_id, 'name' => 'Programa', 'code' => 'REV', 'active' => true]);
        $course = Course::create(['program_id' => $program->id, 'name' => 'Curso original', 'code' => 'ORI', 'active' => true]);
        $group = Group::create(['course_id' => $course->id, 'campus_id' => $user->campus_id, 'name' => 'Sin horario', 'schedule' => 'Sin definir', 'active' => true]);
        $newGroup = Group::create(['course_id' => $course->id, 'campus_id' => $user->campus_id, 'name' => 'Sábado 8 AM', 'schedule' => 'Sábado 8 AM', 'active' => true]);
        $student = Student::create(['organization_id' => $user->organization_id, 'campus_id' => $user->campus_id, 'first_name' => 'Dato', 'last_name' => 'Pendiente', 'active' => true]);
        $enrollment = Enrollment::create(['student_id' => $student->id, 'group_id' => $group->id, 'agreed_amount' => null, 'status' => 'active', 'data_status' => 'needs_review', 'historical' => true]);
        $completeStudent = Student::create(['organization_id' => $user->organization_id, 'campus_id' => $user->campus_id, 'first_name' => 'Dato', 'last_name' => 'Completo', 'active' => true]);
        Enrollment::create(['student_id' => $completeStudent->id, 'group_id' => $group->id, 'agreed_amount' => 100000, 'status' => 'active', 'data_status' => 'complete']);
        $import = Import::create(['organization_id' => $user->organization_id, 'campus_id' => $user->campus_id, 'uploaded_by' => $user->id, 'original_filename' => 'origen.xlsx', 'stored_path' => 'imports/origen.xlsx', 'file_hash' => str_repeat('a', 64), 'status' => 'confirmed']);
        $row = ImportRow::create(['import_id' => $import->id, 'source_sheet' => 'CARTERA SANTA ROSA', 'source_row' => 359, 'source_column' => 'A:GD', 'source_raw_value' => ['A' => 'Dato Pendiente'], 'fingerprint' => str_repeat('b', 64), 'normalized_data' => ['schedule' => 'dato original'], 'inconsistencies' => ['Valor de curso ausente'], 'status' => 'imported', 'student_id' => $student->id, 'enrollment_id' => $enrollment->id]);
        $payment = Payment::create(['enrollment_id' => $enrollment->id, 'payment_date' => null, 'amount' => 50000, 'method' => 'historical', 'reference' => 'NEQUI', 'registered_by' => $user->id, 'import_row_id' => $row->id, 'status' => 'valid', 'historical' => true]);
        $receipt = Receipt::create(['organization_id' => $user->organization_id, 'campus_id' => $user->campus_id, 'payment_id' => $payment->id, 'sequence' => 1, 'number' => 'REC-SR-000001', 'issued_on' => null, 'student_name' => $student->full_name, 'course_name' => $course->name, 'previous_balance' => null, 'payment_amount' => 50000, 'new_balance' => null, 'payment_method' => 'historical', 'cashier_name' => $user->name, 'issued_by' => $user->id]);

        $this->actingAs($user)->get(route('enrollment-reviews.index'))->assertOk()->assertSee('Dato Pendiente')->assertDontSee('Dato Completo')->assertSee('fila 359');
        $token = 'review-token';
        $invalid = ['_token' => $token, 'enrollment_date' => '2026-09-13', 'billing_start_date' => '2026-09-01', 'agreed_amount' => '', 'group_id' => $newGroup->id, 'status' => 'active'];
        $this->actingAs($user)->withSession(['_token' => $token])->from(route('enrollment-reviews.edit', $enrollment))->put(route('enrollment-reviews.update', $enrollment), $invalid)->assertRedirect()->assertSessionHasErrors(['billing_start_date', 'agreed_amount']);
        $valid = ['_token' => $token, 'enrollment_date' => '2026-08-01', 'billing_start_date' => '2026-08-15', 'agreed_amount' => 300000, 'group_id' => $newGroup->id, 'status' => 'active'];
        $this->actingAs($user)->withSession(['_token' => $token])->put(route('enrollment-reviews.update', $enrollment), $valid)->assertRedirect(route('enrollment-reviews.index'));

        $enrollment->refresh();
        $this->assertSame('complete', $enrollment->data_status);
        $this->assertSame('300000.00', $enrollment->agreed_amount);
        $this->assertSame($newGroup->id, $enrollment->group_id);
        $this->assertDatabaseHas('enrollment_reviews', ['enrollment_id' => $enrollment->id, 'import_row_id' => $row->id, 'reviewed_by' => $user->id]);
        $this->assertSame(['A' => 'Dato Pendiente'], $row->fresh()->source_raw_value);
        $this->assertSame('50000.00', $payment->fresh()->amount);
        $this->assertSame('REC-SR-000001', $receipt->fresh()->number);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('receipts', 1);
    }
}
