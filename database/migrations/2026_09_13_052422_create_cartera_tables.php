<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('document_type', 10)->nullable()->change();
            $table->string('document_number', 30)->nullable()->change();
            $table->string('source_key')->nullable()->unique();
        });
        Schema::table('enrollments', function (Blueprint $table) {
            $table->date('enrollment_date')->nullable()->change();
            $table->date('billing_start_date')->nullable()->change();
        });
        Schema::create('imports', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('campus_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('original_filename');
            $table->string('stored_path');
            $table->char('file_hash', 64);
            $table->string('status', 20)->default('previewed')->index();
            $table->string('main_sheet')->nullable();
            $table->json('summary')->nullable();
            $table->json('mapping')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'file_hash']);
        });
        Schema::create('import_rows', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('import_id')->constrained()->cascadeOnDelete();
            $table->string('source_sheet');
            $table->unsignedInteger('source_row');
            $table->string('source_column')->nullable();
            $table->json('source_raw_value')->nullable();
            $table->char('fingerprint', 64);
            $table->json('normalized_data')->nullable();
            $table->json('inconsistencies')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->foreignUlid('student_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('enrollment_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->unique(['import_id', 'source_sheet', 'source_row']);
            $table->index(['fingerprint', 'status']);
        });
        Schema::create('charge_schedules', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('enrollment_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('installment_number');
            $table->date('due_date')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('status', 20)->default('pending')->index();
            $table->foreignUlid('import_row_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['enrollment_id', 'installment_number']);
        });
        Schema::create('payments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('enrollment_id')->constrained()->restrictOnDelete();
            $table->date('payment_date')->nullable()->index();
            $table->decimal('amount', 12, 2);
            $table->string('method', 40);
            $table->string('reference')->nullable()->index();
            $table->text('notes')->nullable();
            $table->foreignUlid('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('import_row_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_key')->nullable()->unique();
            $table->string('status', 20)->default('valid')->index();
            $table->boolean('historical')->default(false);
            $table->timestamps();
        });
        Schema::create('receipts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('campus_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('payment_id')->unique()->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('sequence');
            $table->string('number')->unique();
            $table->string('legacy_reference')->nullable()->index();
            $table->date('issued_on')->nullable();
            $table->string('student_name');
            $table->string('course_name');
            $table->decimal('previous_balance', 12, 2);
            $table->decimal('payment_amount', 12, 2);
            $table->decimal('new_balance', 12, 2);
            $table->string('payment_method', 40);
            $table->string('cashier_name');
            $table->foreignUlid('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['campus_id', 'sequence']);
        });
        Schema::create('payment_reversals', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('payment_id')->unique()->constrained()->restrictOnDelete();
            $table->text('reason');
            $table->foreignUlid('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at');
            $table->timestamps();
        });
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE imports ADD CONSTRAINT imports_status_check CHECK (status IN ('previewed','confirmed','failed','duplicate'))");
            DB::statement("ALTER TABLE import_rows ADD CONSTRAINT import_rows_status_check CHECK (status IN ('pending','valid','warning','error','imported','skipped'))");
            DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_status_check CHECK (status IN ('valid','reversed'))");
            DB::statement('ALTER TABLE payments ADD CONSTRAINT payments_amount_check CHECK (amount > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_reversals');
        Schema::dropIfExists('receipts');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('charge_schedules');
        Schema::dropIfExists('import_rows');
        Schema::dropIfExists('imports');
        Schema::table('students', fn (Blueprint $table) => $table->dropColumn('source_key'));
    }
};
