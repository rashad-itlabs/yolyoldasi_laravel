<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Hər ikisi də idempotentdir — istismarda təkrar çağırmaq təhlükəsizdir.
     */
    public function run(): void
    {
        $this->call([
            CitySeeder::class,
            AdminSeeder::class,
            ContentSeeder::class,
        ]);
    }
}
