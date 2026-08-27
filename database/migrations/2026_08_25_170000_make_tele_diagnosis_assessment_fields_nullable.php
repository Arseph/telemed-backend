<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * tele_diagnosis_assessment was created with summary_assess, diagnosis and
 * clinical_classification as NOT NULL with no default. That only held together
 * because the UI validated the entire form before saving, so the first INSERT
 * always carried every column.
 *
 * The form is now filled collaboratively during a consultation — the doctor and the
 * staff member beside the patient each complete different parts, saving field by
 * field — so a partially complete assessment is a legitimate intermediate state and
 * has to be storable. Without this, the first field committed on a new consultation
 * fails with "Field 'summary_assess' doesn't have a default value".
 *
 * Nullable rather than defaulted on purpose: a default would have to invent a value,
 * and clinical_classification = 0 means "Non-Covid-19 Case" — a real clinical
 * statement. NULL correctly means "not yet assessed".
 *
 * Completeness is still enforced at sign-off, where the form validates as a whole.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tele_diagnosis_assessment', function (Blueprint $table) {
            $table->string('summary_assess', 255)->nullable()->change();
            $table->string('diagnosis', 255)->nullable()->change();
            $table->integer('clinical_classification')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Existing NULLs would block the revert, so blank them first. Note this is
        // lossy in spirit: rows that were legitimately "not yet assessed" become
        // empty strings / 0, and 0 reads as "Non-Covid-19 Case".
        DB::table('tele_diagnosis_assessment')->whereNull('summary_assess')->update(['summary_assess' => '']);
        DB::table('tele_diagnosis_assessment')->whereNull('diagnosis')->update(['diagnosis' => '']);
        DB::table('tele_diagnosis_assessment')->whereNull('clinical_classification')->update(['clinical_classification' => 0]);

        Schema::table('tele_diagnosis_assessment', function (Blueprint $table) {
            $table->string('summary_assess', 255)->nullable(false)->change();
            $table->string('diagnosis', 255)->nullable(false)->change();
            $table->integer('clinical_classification')->nullable(false)->change();
        });
    }
};
