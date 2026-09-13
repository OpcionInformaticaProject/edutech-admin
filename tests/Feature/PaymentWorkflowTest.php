<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Group;
use App\Models\Payment;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use App\Services\PaymentService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_payment_balance_receipt_sequence_and_reversal(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::firstOrFail();
        $program = Program::create(['organization_id' => $user->organization_id, 'name' => 'Prueba', 'code' => 'P', 'active' => true]);
        $course = Course::create(['program_id' => $program->id, 'name' => 'Curso', 'code' => 'C', 'active' => true]);
        $group = Group::create(['course_id' => $course->id, 'campus_id' => $user->campus_id, 'name' => 'Sabado', 'schedule' => 'Sabado', 'active' => true]);
        $student = Student::create(['organization_id' => $user->organization_id, 'campus_id' => $user->campus_id, 'first_name' => 'Ana', 'last_name' => 'Prueba', 'active' => true]);
        $enrollment = Enrollment::create(['student_id' => $student->id, 'group_id' => $group->id, 'agreed_amount' => 500000, 'status' => 'active']);
        $service = app(PaymentService::class);
        $first = $service->register($enrollment->load(['student', 'group.course', 'group.campus']), ['payment_date' => '2026-09-13', 'amount' => 100000, 'method' => 'cash'], $user);
        $second = $service->register($enrollment->fresh()->load(['student', 'group.course', 'group.campus']), ['payment_date' => '2026-09-13', 'amount' => 50000, 'method' => 'nequi', 'reference' => 'ABC'], $user);
        $this->assertSame('REC-SR-000001', $first->receipt->number);
        $this->assertSame('REC-SR-000002', $second->receipt->number);
        $this->assertSame(350000.0, $enrollment->fresh()->balance);
        $this->actingAs($user)->withSession(['_token' => 'test-token'])->post(route('payments.reverse', $first), ['_token' => 'test-token', 'reason' => 'Pago registrado por error'])->assertRedirect();
        $this->assertSame('reversed', Payment::find($first->id)->status);
        $this->assertSame(450000.0, $enrollment->fresh()->balance);
        $this->assertDatabaseHas('payment_reversals', ['payment_id' => $first->id]);
    }
}
