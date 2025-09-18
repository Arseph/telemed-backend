<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class);
            $table->string('type_of_leave');
            $table->string('location')->nullable();
            $table->string('abroad_destination')->nullable();
            $table->string('sick_leave_type')->nullable();
            $table->string('illness_details')->nullable();
            $table->string('study_leave_type')->nullable();
            $table->integer('working_days');
            $table->string('commutation');
            $table->string('remaining_leave_credits');
            $table->string('recommendation');
            $table->string('disapproval_reason')->nullable();
            $table->string('inclusive_days');
            $table->integer('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaves');
    }
};
