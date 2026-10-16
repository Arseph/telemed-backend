<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Make the PhilHealth fields on tbl_master_patient optional.
 *
 * The system is not integrated with PhilHealth yet, so none of that section can be
 * filled in reliably — but three of its columns were created NOT NULL with no
 * default: phic_member, phic_stat and validated. The edit form omits empty fields
 * from its payload, so saving a patient without touching the PhilHealth section
 * fails on INSERT with "Field 'phic_member' doesn't have a default value".
 *
 * Nullable rather than defaulted, deliberately. These columns hold coded values, and
 * the data already in them is wider than the form's 0/1: phic_member also contains
 * 'N' (12 rows), phic_stat contains 'A' and 'S' (13 rows), validated contains 'N'
 * (14 rows). Defaulting to '0' would assert "not a member" / "inactive" /
 * "not validated" about patients nobody has asked. NULL says "not recorded", which
 * is the truth until PhilHealth integration exists.
 *
 * Raw MODIFY rather than the schema builder on purpose: these columns are
 * latin1 / latin1_swedish_ci while the connection default is utf8mb4, and Laravel's
 * ->change() re-emits the whole column definition — it would silently convert the
 * charset as a side effect of making them nullable.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE `tbl_master_patient`
                MODIFY `phic_member` char(1) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL,
                MODIFY `phic_stat`   char(1) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL,
                MODIFY `validated`   varchar(1) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL
        ");
    }

    public function down(): void
    {
        // Rows written while the columns were nullable would block the revert, so they
        // are backfilled first. '' is used rather than '0' because '0' is a meaningful
        // code here — this is still lossy: "not recorded" becomes indistinguishable
        // from a deliberate blank.
        DB::table('tbl_master_patient')->whereNull('phic_member')->update(['phic_member' => '']);
        DB::table('tbl_master_patient')->whereNull('phic_stat')->update(['phic_stat' => '']);
        DB::table('tbl_master_patient')->whereNull('validated')->update(['validated' => '']);

        DB::statement("
            ALTER TABLE `tbl_master_patient`
                MODIFY `phic_member` char(1) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
                MODIFY `phic_stat`   char(1) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
                MODIFY `validated`   varchar(1) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL
        ");
    }
};
