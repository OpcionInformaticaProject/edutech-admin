<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ChargeSchedule;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Group;
use App\Models\Payment;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use App\Support\Status;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_technical_statuses_are_translated_centrally(): void
    {
        $this->assertSame('Activo', Status::label('active'));
        $this->assertSame('Retirado', Status::label('withdrawn'));
        $this->assertSame('Por revisar', Status::label('needs_review'));
        $this->assertSame('Revisado', Status::label('complete'));
        $this->assertSame('Reversado', Status::label('reversed'));
        $this->assertSame('Pendiente de pago', Status::label('pending_payment'));
    }

    public function test_dashboard_and_reports_use_valid_portfolio_revenue_and_overdue_data(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::firstOrFail();
        [$enrollment, $otherEnrollment] = $this->records($user);

        $this->actingAs($user)->get(route('dashboard'))->assertOk()
            ->assertSee('Cartera pendiente')->assertSee('$850.000')->assertSee('Cartera vencida')->assertSee('$80.000')->assertSee('Recaudo de hoy')->assertSee('$150.000')->assertSee('Recaudo de la semana')->assertSee('Recaudo del mes')->assertSee('Estudiantes con saldo pendiente');
        $this->get(route('reports.index'))->assertOk()->assertSee('Cartera general')->assertSee('Resumen por curso / grupo');
        $this->get(route('reports.show', ['report' => 'portfolio', 'student_id' => $enrollment->student_id]))->assertOk()->assertSee('Ana Reporte')->assertViewHas('records', fn ($records) => $records->total() === 1 && $records->first()->id === $enrollment->id);
        $this->get(route('reports.show', ['report' => 'revenue', 'student_id' => $otherEnrollment->student_id]))->assertOk()->assertSee('$50.000')->assertViewHas('records', fn ($records) => $records->total() === 1 && $records->first()->enrollment_id === $otherEnrollment->id);
        $this->get(route('reports.show', ['report' => 'overdue']))->assertOk()->assertSee('$20.000');
        $this->get(route('reports.show', ['report' => 'high-delinquency']))->assertOk()->assertSee('4 vencidas')->assertSee('$80.000');
        $this->get(route('reports.show', ['report' => 'revenue', 'format' => 'csv']))->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_teacher_cannot_access_operational_reports(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::firstOrFail();
        $teacher = User::create(['organization_id' => $admin->organization_id, 'campus_id' => $admin->campus_id, 'name' => 'Docente', 'email' => 'docente@edutech.test', 'role' => UserRole::Docente, 'active' => true, 'password' => 'password']);

        $this->actingAs($teacher)->get(route('reports.index'))->assertForbidden();
        $this->get(route('reports.show', 'portfolio'))->assertForbidden();
    }

    private function records(User $user): array
    {
        $program = Program::create(['organization_id' => $user->organization_id, 'name' => 'Programa', 'code' => 'INF', 'active' => true]);
        $course = Course::create(['program_id' => $program->id, 'name' => 'Curso', 'code' => 'CUR', 'active' => true]);
        $group = Group::create(['course_id' => $course->id, 'campus_id' => $user->campus_id, 'name' => 'Grupo A', 'schedule' => 'Sábado', 'active' => true]);
        $student = Student::create(['organization_id' => $user->organization_id, 'campus_id' => $user->campus_id, 'first_name' => 'Ana', 'last_name' => 'Reporte', 'active' => true]);
        $other = Student::create(['organization_id' => $user->organization_id, 'campus_id' => $user->campus_id, 'first_name' => 'Luis', 'last_name' => 'Filtro', 'active' => true]);
        $enrollment = Enrollment::create(['student_id' => $student->id, 'group_id' => $group->id, 'enrollment_date' => today(), 'agreed_amount' => 500000, 'status' => 'active']);
        $otherEnrollment = Enrollment::create(['student_id' => $other->id, 'group_id' => $group->id, 'enrollment_date' => today(), 'agreed_amount' => 500000, 'status' => 'active']);
        Payment::create(['enrollment_id' => $enrollment->id, 'payment_date' => today(), 'amount' => 100000, 'method' => 'cash', 'registered_by' => $user->id, 'status' => 'valid']);
        Payment::create(['enrollment_id' => $otherEnrollment->id, 'payment_date' => today(), 'amount' => 50000, 'method' => 'cash', 'registered_by' => $user->id, 'status' => 'valid']);
        Payment::create(['enrollment_id' => $enrollment->id, 'payment_date' => today(), 'amount' => 999999, 'method' => 'cash', 'registered_by' => $user->id, 'status' => 'reversed']);
        foreach (range(1, 4) as $number) {
            ChargeSchedule::create(['enrollment_id' => $enrollment->id, 'installment_number' => $number, 'due_date' => today()->subDays($number), 'amount' => 20000, 'status' => 'pending']);
        }

        return [$enrollment, $otherEnrollment];
    }
}
