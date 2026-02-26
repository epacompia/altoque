<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category; // <-- importa el modelo

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = ['Platos', 'Bebidas', 'Promociones', 'Cremas'];
        foreach ($categories as $name) {
            Category::firstOrCreate(['name' => $name]);
        }
    }
}