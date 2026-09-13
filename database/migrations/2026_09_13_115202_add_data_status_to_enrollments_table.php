<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->decimal('agreed_amount', 12, 2)->nullable()->change();
            $table->string('data_status', 20)->default('complete')->index();
        });
        Schema::table('receipts', function (Blueprint $table) {
            $table->decimal('previous_balance', 12, 2)->nullable()->change();
            $table->decimal('new_balance', 12, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn('data_status');
        });
    }
};
