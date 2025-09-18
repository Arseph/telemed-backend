<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\CocDocuments;
use App\Models\CocApplication;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('coc_attachments', function (Blueprint $table) {
            $table->id();
            $table->string('app_number'); 
            $table->foreignIdFor(CocDocuments::class);
            $table->binary('file_data'); // BLOB column for storing file
            $table->string('file_name'); // To store original file name
            $table->timestamps();
        
            // Foreign key constraint linking app_number in coc_attachments to app_number in coc_applications
           
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coc_attachments');
    }
};
