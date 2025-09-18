<?php

namespace Database\Seeders;

use App\Models\CocDocuments;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Attendance;
use Illuminate\Support\Str;
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
<<<<<<< Updated upstream
        Attendance::create([
            'type' => 1,
            'name' => 'Attendance',
            'date_attend' => '2024-11-21',
            'token' => Str::random(20),
            'expired_at' => '2024-11-21 10:00:00',
        ]);
=======

        $this->call(LeaveDetailsSeeder::class);
        $this->call(CocDocumentSeeder::class);
>>>>>>> Stashed changes
    }
}
