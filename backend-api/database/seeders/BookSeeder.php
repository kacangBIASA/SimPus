<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Book;
use App\Models\Category;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cat = Category::where('name', 'Teknologi')->first();

        Book::updateOrCreate(
            ['isbn' => '978-0001'],
            [
                'category_id' => $cat->id,
                'title' => 'Belajar Laravel Untuk Balita',
                'author' => 'SIMPUS Team',
                'isbn' => '978-0001',
                'stock_total' => 5,
                'stock_available' => 5,
            ]
        );
    }
}
