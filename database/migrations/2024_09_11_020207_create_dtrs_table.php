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
        Schema::create('dtrs', function (Blueprint $table) {
            $table->id()->increments();
            $table->foreignIdFor(User::class);
            $table->datetime('time_in_am')->nullable();
            $table->datetime('time_out_am')->nullable();
            $table->datetime('time_in_pm')->nullable();
            $table->datetime('time_out_pm')->nullable();
            $table->date('date_log')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dtrs');
    }
};
