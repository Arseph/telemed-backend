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
        Schema::create('pass_slips', function (Blueprint $table) {
            $table->id()->increments();
            $table->integer('user_id');
            $table->date('request_date');
            $table->time('request_time_out');
            $table->string('reason',10);
            $table->string('nature_business')->nullable();
            $table->time('estimated_arrival');
            $table->time('actual_time')->nullable();
            $table->integer('supervisor_id');
            $table->integer('approver_id');
            $table->boolean('is_supervisor_approved')->default(false);
            $table->boolean('is_approver_approved')->default(false);
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('status')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pass_slips');
    }
};
