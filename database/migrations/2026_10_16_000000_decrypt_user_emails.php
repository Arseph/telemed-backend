<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

return new class extends Migration
{
    /**
     * Email encryption (random-IV Crypt accessor/mutator) was removed from the
     * User model. Older rows may still hold Crypt-encrypted ciphertext, which
     * would now display as gibberish and break unique/lookup queries. Decrypt
     * any such rows in place so all emails are stored as plaintext.
     */
    public function up(): void
    {
        DB::table('users')
            ->whereNotNull('email')
            ->orderBy('id')
            ->chunkById(200, function ($users) {
                foreach ($users as $user) {
                    try {
                        $plain = Crypt::decrypt($user->email);
                    } catch (DecryptException $e) {
                        // Already plaintext — leave as-is.
                        continue;
                    }

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['email' => $plain]);
                }
            });
    }

    /**
     * No reliable, safe down path: re-encrypting would regenerate random-IV
     * ciphertext and we've intentionally moved away from it.
     */
    public function down(): void
    {
        // intentionally irreversible
    }
};
