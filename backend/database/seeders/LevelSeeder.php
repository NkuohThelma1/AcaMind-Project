<?php

namespace Database\Seeders;

use App\Models\Level;
use Illuminate\Database\Seeder;

class LevelSeeder extends Seeder
{
    public function run(): void
    {
        Level::updateOrCreate(['code' => 'o_level'], ['name' => 'GCE Ordinary Level']);
        Level::updateOrCreate(['code' => 'a_level'], ['name' => 'GCE Advanced Level']);
    }
}
