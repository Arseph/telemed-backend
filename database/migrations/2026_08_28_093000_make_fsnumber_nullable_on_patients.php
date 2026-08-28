<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Make tbl_master_patient.fsNumber optional.
 *
 * The Family Serial Number is not always known when a patient is first registered,
 * but the column was created NOT NULL with no default. The edit form omits empty
 * fields from its payload, so saving a patient without one fails on INSERT with
 * "Field 'fsNumber' doesn't have a default value".
 *
 * Nullable rather than defaulted: an empty string would be indistinguishable from a
 * serial number that was recorded as blank, whereas NULL says "not known yet".
 *
 * Raw MODIFY rather than the schema builder, for the same reason as the PhilHealth
 * migration: this column is latin1 / latin1_swedish_ci while the connection default
 * is utf8mb4, and Laravel's ->change() re-emits the whole column definition, so it
 * would silently convert the charset as a side effect of making it nullable.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE `tbl_master_patient`
                MODIFY `fsNumber` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL
        ");
    }

    public function down(): void
    {
        // Rows written while the column was nullable would block the revert.
        DB::table('tbl_master_patient')->whereNull('fsNumber')->update(['fsNumber' => '']);

        DB::statement("
            ALTER TABLE `tbl_master_patient`
                MODIFY `fsNumber` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL
        ");
    }
};
