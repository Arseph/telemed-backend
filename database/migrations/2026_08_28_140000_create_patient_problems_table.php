<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Problem list — the conditions a patient carries, one row each.
 *
 * medical_histories has a single icd10 / date_diagnosis pair, so a patient could
 * only ever have one recorded condition. Comorbidity is the norm rather than the
 * exception, so diagnoses move here and medical_histories keeps the narrative and
 * social sections it models correctly.
 *
 * Deliberately NOT per-consultation: per-visit diagnoses already live in
 * tele_diagnosis_assessment, keyed on meeting_id. This is the standing list.
 *
 * latin1_swedish_ci to match the rest of the schema — every neighbouring table uses
 * it, and mixing collations makes any later varchar join fail with "illegal mix of
 * collations" rather than simply working.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_problems', function (Blueprint $table) {
            $table->charset = 'latin1';
            $table->collation = 'latin1_swedish_ci';

            $table->id();
            $table->integer('patient_id');

            // References diagnosis.id. Named for what it is — medical_histories calls
            // the same thing "icd10" while storing a row id, which reads as the code.
            $table->unsignedInteger('diagnosis_id')->nullable();

            // Used when no ICD-10 entry fits; the controller requires one or the other.
            $table->string('problem', 255)->nullable();

            $table->date('onset_date')->nullable();
            $table->string('status', 20)->default('active');
            $table->date('resolved_date')->nullable();
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            // The list is always read per patient, usually filtered to active ones.
            $table->index(['patient_id', 'status']);
            $table->index('diagnosis_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_problems');
    }
};
