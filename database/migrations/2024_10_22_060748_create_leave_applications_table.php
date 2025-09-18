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
        Schema::create('leave_applications', function (Blueprint $table) {
            $table->id();  // Primary key: leave application ID
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');  // Foreign key to users table
            $table->foreignId('leave_type_id')->constrained('leave_types')->onDelete('restrict');  // Foreign key to leave_types table
            $table->foreignId('leave_detail_id')->constrained('leave_details')->onDelete('restrict');  // Link to leave_details table
            $table->text('leave_remarks')->constrained();  // remarks
            $table->date('start_date');  // Start of the leave
            $table->date('end_date');    // End of the leave
            $table->string('commutation')->default('N/A'); // 'Not Requested' or 'Requested'
            $table->boolean('recom_status')->default(false);  // Approved/rejected status
            $table->text('recom_remarks')->nullable();  // Admin's remarks on leave
            $table->softDeletes();  // Support for soft deletes (adds deleted_at column)
            $table->timestamps();  // Created_at and updated_at columns
        
            // Optional indexes for faster querying
            $table->index(['user_id', 'leave_type_id']);
        });
        
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_applications');
    }
};
