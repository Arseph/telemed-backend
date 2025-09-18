<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CocDocuments;

class CocDocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CocDocuments::create(['documents_name' => 'DTR']);
        CocDocuments::create(['documents_name' => 'RPO']);
        CocDocuments::create(['documents_name' => 'COC']);
        CocDocuments::create(['documents_name' => 'OTHER']);
    }
}
