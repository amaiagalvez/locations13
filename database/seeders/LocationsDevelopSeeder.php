<?php

namespace Locations13\Database\Seeders;

use Illuminate\Database\Seeder;

class LocationsDevelopSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DevelopLocationSeeder::class);
    }
}
