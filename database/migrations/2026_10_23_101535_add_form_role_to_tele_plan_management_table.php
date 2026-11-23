<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private string $table = 'tele_plan_management';

    public function up(): void
    {
        // The unique key below fails if a meeting already has more than one row.
        // Fail early with a readable message instead of a raw SQL error.
        $hasDuplicates = DB::table($this->table)->select('meeting_id')->groupBy('meeting_id')->havingRaw('COUNT(*) > 1')->exists();

        if ($hasDuplicates) {
            throw new RuntimeException("{$this->table} has meetings with more than one row. Merge or delete the extras, then re-run.");
        }

        Schema::table($this->table, function (Blueprint $table) {
            // Existing rows become 'requester' rows through the default.
            $table
                ->enum('form_role', ['requester', 'accepter'])
                ->default('requester')
                ->after('meeting_id');
            $table->unsignedInteger('user_id')->nullable()->after('form_role');

            $table->unique(['meeting_id', 'form_role'], 'uq_plan_management_meeting_role');
        });
    }

    public function down(): void
    {
        Schema::table($this->table, function (Blueprint $table) {
            $table->dropUnique('uq_plan_management_meeting_role');
            $table->dropColumn(['form_role', 'user_id']);
        });
    }
};
