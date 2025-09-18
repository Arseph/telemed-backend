<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\CocDocuments;
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('coc_applications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('app_number');
            $table->string('rpo_number')->nullable();
            $table->foreignIdFor(User::class);
            $table->integer('hours')->nullable();
            $table->integer('balance')->nullable();
            $table->integer('status');
            $table->foreignIdFor(CocDocuments::class);
            $table->date('date_issued');
            $table->date('date_end');
            $table->string('remarks');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coc_applications');
    }
};
