<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('tax_id', 30)->nullable()->unique();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('municipalities', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code', 10)->unique();
            $table->string('name')->index();
            $table->string('department')->index();
            $table->timestamps();
            $table->unique(['department', 'name']);
        });

        Schema::create('campuses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('municipality_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('code', 30);
            $table->string('address')->nullable();
            $table->string('phone', 30)->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
            $table->unique(['organization_id', 'code']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignUlid('organization_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignUlid('campus_id')->nullable()->after('organization_id')->constrained()->nullOnDelete();
            $table->string('role', 20)->default('consulta')->after('email')->index();
            $table->boolean('active')->default(true)->after('role')->index();
        });

        Schema::create('students', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('campus_id')->nullable()->constrained()->nullOnDelete();
            $table->string('document_type', 10);
            $table->string('document_number', 30);
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->date('birth_date')->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('address')->nullable();
            $table->foreignUlid('municipality_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
            $table->unique(['organization_id', 'document_type', 'document_number']);
            $table->index(['organization_id', 'last_name', 'first_name']);
        });

        Schema::create('contacts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('student_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('relationship', 50);
            $table->string('document_number', 30)->nullable();
            $table->string('phone', 30);
            $table->string('email')->nullable();
            $table->boolean('is_primary')->default(false)->index();
            $table->boolean('authorized_pickup')->default(false);
            $table->timestamps();
            $table->index(['student_id', 'relationship']);
        });

        Schema::create('programs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 30);
            $table->text('description')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
            $table->unique(['organization_id', 'code']);
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('program_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 30);
            $table->unsignedInteger('duration_hours')->nullable();
            $table->decimal('default_amount', 12, 2)->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
            $table->unique(['program_id', 'code']);
        });

        Schema::create('groups', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('course_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('campus_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('schedule');
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
            $table->unique(['course_id', 'campus_id', 'name']);
        });

        Schema::create('enrollments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('student_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('group_id')->constrained()->restrictOnDelete();
            $table->date('enrollment_date');
            $table->date('billing_start_date');
            $table->decimal('agreed_amount', 12, 2);
            $table->string('status', 20)->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'group_id']);
            $table->index(['status', 'enrollment_date']);
            $table->index(['group_id', 'status']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('superadmin','admin','cartera','coordinador','docente','consulta'))");
            DB::statement("ALTER TABLE enrollments ADD CONSTRAINT enrollments_status_check CHECK (status IN ('active','inactive','withdrawn','completed','suspended','pending'))");
            DB::statement('ALTER TABLE enrollments ADD CONSTRAINT enrollments_amount_check CHECK (agreed_amount >= 0)');
            DB::statement('ALTER TABLE groups ADD CONSTRAINT groups_dates_check CHECK (ends_on IS NULL OR starts_on IS NULL OR ends_on >= starts_on)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('groups');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('programs');
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('students');
        Schema::table('users', fn (Blueprint $table) => $table->dropConstrainedForeignId('campus_id'));
        Schema::table('users', fn (Blueprint $table) => $table->dropConstrainedForeignId('organization_id'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['role', 'active']));
        Schema::dropIfExists('campuses');
        Schema::dropIfExists('municipalities');
        Schema::dropIfExists('organizations');
    }
};
