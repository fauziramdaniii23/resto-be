<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = ['Makanan', 'Minuman', 'Dessert'];
        $menuNames = [
            'Nasi Goreng Spesial', 'Ayam Bakar Madu', 'Es Teh Manis', 'Kopi Susu', 'Brownies Coklat',
            'Mie Ayam', 'Sate Ayam', 'Jus Alpukat', 'Pisang Goreng', 'Ice Cream Vanilla'
        ];

        foreach (range(1, 10) as $index) {
            $name = $menuNames[$index - 1];
            DB::table('menus')->insert([
                'name' => $name,
                'description' => Str::limit('Ini adalah deskripsi dari menu ' . $name, 100),
                'price' => mt_rand(10000, 50000),
                'category' => $categories[array_rand($categories)],
                'image_url' => 'https://picsum.photos/800/450?random=' . $index,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
