<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'title',
        'author',
        'isbn',
        'stock_total',
        'stock_available'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    public function scopeSearch($query, $search)
    {
        if (!$search) return $query;

        return $query->where(function ($w) use ($search) {
            $w->where('title', 'like', "%{$search}%")
                ->orWhere('author', 'like', "%{$search}%")
                ->orWhere('isbn', 'like', "%{$search}%");
        });
    }
}
