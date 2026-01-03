<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class BookController extends Controller
{
    // GET /api/books?search=&page=&per_page=
    public function index(Request $request)
    {
        $search  = trim((string) $request->query('search', ''));
        $perPage = (int) $request->query('per_page', 10);
        $perPage = max(1, min($perPage, 50));

        $q = Book::with('category')->orderByDesc('id');

        if ($search !== '') {
            $q->where(function ($w) use ($search) {
                $w->where('title', 'like', "%{$search}%")
                    ->orWhere('author', 'like', "%{$search}%")
                    ->orWhere('isbn', 'like', "%{$search}%");
            });
        }

        $books = $q->paginate($perPage);

        return ApiResponse::success($books, 'List books');
    }

    // GET /api/books/{id}
    public function show($id)
    {
        $book = Book::with('category')->find($id);
        if (!$book) return ApiResponse::error('Book not found', 404);

        return ApiResponse::success($book, 'Detail book');
    }

    // POST /api/books (admin)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id'     => ['required', 'exists:categories,id'],
            'title'           => ['required', 'string', 'max:200'],
            'author'          => ['required', 'string', 'max:120'],
            'isbn'            => ['nullable', 'string', 'max:30', 'unique:books,isbn'],
            'stock_total'     => ['required', 'integer', 'min:0'],
            'stock_available' => ['required', 'integer', 'min:0', 'lte:stock_total'],
        ]);

        $book = Book::create($validated);

        return ApiResponse::success($book->load('category'), 'Book created', 201);
    }

    // PUT /api/books/{id} (admin)
    public function update(Request $request, $id)
    {
        $book = Book::find($id);
        if (!$book) return ApiResponse::error('Book not found', 404);

        $validated = $request->validate([
            'category_id'     => ['sometimes', 'required', 'exists:categories,id'],
            'title'           => ['sometimes', 'required', 'string', 'max:200'],
            'author'          => ['sometimes', 'required', 'string', 'max:120'],
            'isbn'            => ['nullable', 'string', 'max:30', 'unique:books,isbn,' . $book->id],
            'stock_total'     => ['sometimes', 'required', 'integer', 'min:0'],
            'stock_available' => ['sometimes', 'required', 'integer', 'min:0'],
        ]);

        // kalau stock_total berubah, pastikan available <= total
        $newTotal = $validated['stock_total'] ?? $book->stock_total;
        $newAvail = $validated['stock_available'] ?? $book->stock_available;
        if ($newAvail > $newTotal) {
            return ApiResponse::error('stock_available tidak boleh lebih besar dari stock_total', 422);
        }

        $book->update($validated);

        return ApiResponse::success($book->load('category'), 'Book updated');
    }

    // DELETE /api/books/{id} (admin)
    public function destroy($id)
    {
        $book = Book::find($id);
        if (!$book) return ApiResponse::error('Book not found', 404);

        $book->delete();
        return ApiResponse::success(null, 'Book deleted');
    }

    public function recommendations(Book $book)
    {
        $limit = (int) request('limit', 5);
        $limit = max(1, min(20, $limit));

        $ids = [];

        // 1) rekomendasi dari kategori sama
        $recs = Book::query()
            ->whereKeyNot($book->id)
            ->when($book->category_id, fn($q) => $q->where('category_id', $book->category_id))
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        $ids = $recs->pluck('id')->all();

        // 2) kalau kurang, tambahkan buku terbaru lainnya
        if ($recs->count() < $limit) {
            $need = $limit - $recs->count();
            $extra = Book::query()
                ->whereKeyNot(array_merge([$book->id], $ids))
                ->orderByDesc('id')
                ->limit($need)
                ->get();

            $recs = $recs->concat($extra);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'book_id' => $book->id,
                'limit' => $limit,
                'recommendations' => $recs
            ]
        ], 200);
    }
}
