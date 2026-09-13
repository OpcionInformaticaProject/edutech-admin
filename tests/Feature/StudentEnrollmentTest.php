<?php

namespace Tests\Feature;

use App\Enums\EnrollmentStatus;
use App\Enums\UserRole;
use App\Models\Campus;
use App\Models\Course;
use App\Models\Group;
use App\Models\Organization;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_create_student_and_enrollment(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);
        $organization = Organization::create(['name' => 'EDUTECH']);
        $campus = Campus::create(['organization_id' => $organization->id, 'name' => 'Santa Rosa', 'code' => 'SRC']);
        $user = User::factory()->create(['organization_id' => $organization->id, 'campus_id' => $campus->id, 'role' => UserRole::Admin, 'active' => true]);
        $program = Program::create(['organization_id' => $organization->id, 'name' => 'Inglés', 'code' => 'ING']);
        $course = Course::create(['program_id' => $program->id, 'name' => 'Inglés A1', 'code' => 'A1']);
        $group = Group::create(['course_id' => $course->id, 'campus_id' => $campus->id, 'name' => 'A1-01', 'schedule' => 'Lunes 18:00']);

        $this->actingAs($user)->post(route('students.store'), [
            'document_type' => 'TI', 'document_number' => '100200300', 'first_name' => 'Ana', 'last_name' => 'García', 'campus_id' => $campus->id, 'active' => 1,
        ])->assertRedirect();
        $student = Student::firstOrFail();
        $this->assertTrue($student->id !== '' && strlen($student->id) === 26);

        $this->actingAs($user)->post(route('enrollments.store'), [
            'student_id' => $student->id, 'group_id' => $group->id, 'enrollment_date' => '2026-09-12', 'billing_start_date' => '2026-09-15', 'agreed_amount' => 250000, 'status' => 'active',
        ])->assertRedirect(route('students.show', $student));
        $this->assertDatabaseHas('enrollments', ['student_id' => $student->id, 'status' => EnrollmentStatus::Active->value]);
        $this->assertCount(1, $student->fresh()->enrollments);
    }
}
