<?php

namespace App\GraphQL\Builders;

use App\Models\Book;

class BookBuilder
{
    public function books($_, array $args)
    {
        return Book::with('category')
            ->search($args['search'] ?? null)
            ->orderByDesc('id');
    }
}
