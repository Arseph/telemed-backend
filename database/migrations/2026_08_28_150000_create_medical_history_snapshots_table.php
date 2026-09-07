<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Point-in-time copies of a patient's medical history.
 *
 * The audits table already records that something changed and what the diff was, but
 * it stores JSON diffs — reconstructing "what did this patient's history look like in
 * May" means replaying them by hand, which nobody will do. These are full states,
 * readable directly.
 *
 * Written whenever the history or the problem list changes, not only during a
 * consultation: staff edit from the patient profile too, and those changes would
 * otherwise leave unexplained gaps between snapshots. meeting_id records which
 * consultation a change happened in, when it happened in one at all.
 *
 * payload is JSON rather than mirrored columns for two reasons: the snapshot table
 * never needs migrating when medical_histories changes shape, and it can hold the
 * problem list rows in the same blob — which mirrored columns cannot do cleanly.
 * These are read back for display, never queried across, so JSON costs nothing.
 *
 * latin1 to match the rest of the schema; see the patient_problems migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_history_snapshots', function (Blueprint $table) {
            $table->charset = 'latin1';
            $table->collation = 'latin1_swedish_ci';

            $table->id();
            $table->integer('patient_id');

            // The consultation this change happened during, if any.
            $table->integer('meeting_id')->nullable();

            // Who made the change. Nullable because a snapshot is still worth having
            // if the user cannot be resolved — better a record with no name than none.
            $table->unsignedBigInteger('taken_by')->nullable();

            // What prompted it, e.g. 'history' or 'problem'. Keeps the timeline
            // readable without diffing consecutive payloads.
            $table->string('reason', 30)->nullable();

            $table->longText('payload');
            $table->timestamp('taken_at')->useCurrent();

            // Always read as a patient's timeline, newest first.
            $table->index(['patient_id', 'taken_at']);
            $table->index('meeting_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_history_snapshots');
    }
};
