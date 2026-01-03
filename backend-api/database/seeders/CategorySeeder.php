<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run(): void
    {
        Category::updateOrCreate(['name' => 'Teknologi'], ['name' => 'Teknologi']);
        Category::updateOrCreate(['name' => 'Sains'], ['name' => 'Sains']);
        Category::updateOrCreate(['name' => 'Novel'], ['name' => 'Novel']);
    }
}
