<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->boolean('historical')->default(false)->index();
        });
        DB::table('enrollments')->whereIn('id', DB::table('import_rows')->whereNotNull('enrollment_id')->select('enrollment_id'))->update(['historical' => true]);
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE enrollments ADD CONSTRAINT enrollments_amount_quality_check CHECK (agreed_amount IS NOT NULL OR (historical = true AND data_status = 'needs_review'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn('historical');
        });
    }
};
