<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\FoodStall;
use App\Models\MenuItem;
use App\Models\Topping;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        // Crear usuario CLIENTE
        $cliente = User::create([
            'name' => 'Juan Cliente',
            'email' => 'cliente@test.com',
            'phone' => '987654321',
            'password' => Hash::make('password123'),
            'role' => 'client',
            'email_verified_at' => now(),
        ]);

        // Crear usuario VENDEDOR
        $vendedor = User::create([
            'name' => 'María Vendedor',
            'email' => 'vendedor@test.com',
            'phone' => '987654322',
            'password' => Hash::make('password123'),
            'role' => 'vendor',
            'email_verified_at' => now(),
        ]);

        // Crear PUESTO para el vendedor
        $puesto = FoodStall::create([
            'seller_id' => $vendedor->id,
            'name' => 'Cevichería "El Mar"',
            'slug' => 'cevicheria-el-mar',
            'address' => 'Jr. Manco Cápac 123, La Breña, Lima',
            'phone' => '987654322',
            'opening_time' => '11:00',
            'closing_time' => '22:00',
            'latitude' => -12.0931,
            'longitude' => -77.0406,
            'description' => 'Ceviches frescos y mariscos a diario',
            'active' => true,
        ]);

        // Crear categoría
        $categoria = Category::create([
            'name' => 'Ceviches',
        ]);

        // Crear PRODUCTOS
        $productos = [
            [
                'name' => 'Ceviche Mixto',
                'description' => 'Pescado y mariscos con limón y camote',
                'price' => 25.00,
                'image_path' => 'products/ceviche-mixto.jpg',
                'featured' => true,
            ],
            [
                'name' => 'Ceviche de Pulpo',
                'description' => 'Pulpo tierno marinado',
                'price' => 28.00,
                'image_path' => 'products/ceviche-pulpo.jpg',
                'featured' => false,
            ],
            [
                'name' => 'Tiradito Especial',
                'description' => 'Pescado fresco con salsa especial',
                'price' => 30.00,
                'image_path' => 'products/tiradito.jpg',
                'featured' => true,
            ],
        ];

        foreach ($productos as $producto) {
            MenuItem::create([
                'stall_id' => $puesto->id,
                'category_id' => $categoria->id,
                'name' => $producto['name'],
                'description' => $producto['description'],
                'price' => $producto['price'],
                'image_path' => $producto['image_path'],
                'active' => true,
                'featured' => $producto['featured'],
            ]);
        }

        // Crear CREMAS/ACOMPAÑAMIENTOS
        $cremas = [
            ['name' => 'Salsa picante', 'price' => 1.00],
            ['name' => 'Salsa roja', 'price' => 1.00],
            ['name' => 'Camote extra', 'price' => 2.00],
            ['name' => 'Choclo extra', 'price' => 1.50],
        ];

        foreach ($cremas as $crema) {
            Topping::create([
                'stall_id' => $puesto->id,
                'name' => $crema['name'],
                'price' => $crema['price'],
            ]);
        }

        echo "✓ Datos de prueba creados exitosamente\n";
        echo "Cliente: cliente@test.com / password123\n";
        echo "Vendedor: vendedor@test.com / password123\n";
    }
}
