<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Loan;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LoanController extends Controller
{
    // GET /api/loans?status=...
    public function index(Request $request)
    {
        $user = $request->user();
        $status = $request->query('status'); // optional

        $q = Loan::with(['book.category', 'user'])
            ->orderByDesc('id');

        // member hanya lihat miliknya
        if ($user->role !== 'admin') {
            $q->where('user_id', $user->id);
        }

        if ($status) {
            $q->where('status', $status);
        }

        $loans = $q->paginate(10);

        return ApiResponse::success($loans, 'List loans');
    }

    // POST /api/loans/borrow { book_id }
    public function borrow(Request $request)
    {
        $validated = $request->validate([
            'book_id' => ['required', 'exists:books,id'],
        ]);

        $user = $request->user();

        // (opsional) admin boleh/ tidak boleh pinjam? kalau kamu mau admin tidak pinjam:
        // if ($user->role === 'admin') return ApiResponse::error('Admin tidak boleh meminjam buku', 403);

        return DB::transaction(function () use ($validated, $user) {
            $book = Book::query()
                ->lockForUpdate()
                ->find($validated['book_id']);

            if (!$book) return ApiResponse::error('Book not found', 404);

            if ($book->stock_available < 1) {
                return ApiResponse::error('Stok buku habis', 400);
            }

            // cegah pinjam buku yang sama jika belum dikembalikan
            $existing = Loan::query()
                ->where('user_id', $user->id)
                ->where('book_id', $book->id)
                ->whereNull('returned_at')
                ->first();

            if ($existing) {
                return ApiResponse::error('Kamu masih meminjam buku ini (belum dikembalikan)', 400);
            }

            $now = Carbon::now();
            $due = $now->copy()->addDays(1); // aturan contoh: 7 hari

            $loan = Loan::create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'borrowed_at' => $now,
                'due_at' => $due,
                'returned_at' => null,
                'status' => 'BORROWED',

                // ✅ denda default (pastikan kolom ada di DB)
                'fine_amount' => 0,
                'fine_paid' => false,
                'fine_paid_at' => null,
            ]);

            $book->decrement('stock_available', 1);

            return ApiResponse::success(
                $loan->load(['book.category', 'user']),
                'Berhasil meminjam buku',
                201
            );
        });
    }

    // POST /api/loans/return { loan_id }
    public function returnBook(Request $request)
    {
        $user = $request->user();

        // ✅ Admin tidak boleh return (sesuai aturanmu)
        if ($user->role === 'admin') {
            return ApiResponse::error('Admin tidak diizinkan mengembalikan buku.', 403);
        }

        $validated = $request->validate([
            'loan_id' => ['required', 'exists:loans,id'],
        ]);

        $finePerDay = 2000; // bebas kamu atur

        return DB::transaction(function () use ($validated, $user, $finePerDay) {

            // ✅ lock row untuk aman
            $loan = Loan::query()
                ->lockForUpdate()
                ->with('book.category', 'user')
                ->where('id', $validated['loan_id'])
                ->first();

            if (!$loan) return ApiResponse::error('Loan not found', 404);

            // member hanya boleh return miliknya
            if ($loan->user_id !== $user->id) {
                return ApiResponse::error('Forbidden', 403);
            }

            if ($loan->returned_at !== null) {
                return ApiResponse::error('Buku sudah dikembalikan', 400);
            }

            $now = Carbon::now();

            // ✅ hitung denda
            $lateDays = 0;
            if ($loan->due_at && $now->greaterThan($loan->due_at)) {
                $lateDays = (int) ceil($loan->due_at->diffInHours($now) / 24);
            }
            $fineAmount = max(0, $lateDays * $finePerDay);

            $loan->returned_at = $now;
            $loan->status = 'RETURNED';

            // ✅ simpan denda
            $loan->fine_amount = $fineAmount;
            $loan->fine_paid = false;
            $loan->fine_paid_at = null;

            $loan->save();

            // update stok buku
            $loan->book->increment('stock_available', 1);

            // response + info denda
            $payload = $loan->fresh()->load(['book.category', 'user']);
            $payload->fine_meta = [
                'late_days' => $lateDays,
                'fine_per_day' => $finePerDay,
                'fine_amount' => $fineAmount,
            ];

            return ApiResponse::success($payload, 'Berhasil mengembalikan buku');
        });
    }

    // GET /api/loans/{loan}/fine
    public function fine(Request $request, Loan $loan)
    {
        $user = $request->user();

        // admin boleh lihat semua, member hanya miliknya
        if ($user->role !== 'admin' && $loan->user_id !== $user->id) {
            return ApiResponse::error('Forbidden', 403);
        }

        $finePerDay = 2000;
        $now = Carbon::now();

        $lateDays = 0;
        if (!$loan->returned_at && $loan->due_at && $now->greaterThan($loan->due_at)) {
            $lateDays = (int) ceil($loan->due_at->diffInHours($now) / 24);
        }

        $estimated = $loan->returned_at ? null : max(0, $lateDays * $finePerDay);

        return ApiResponse::success([
            'loan_id' => $loan->id,
            'status' => $loan->status,
            'due_at' => $loan->due_at,
            'returned_at' => $loan->returned_at,
            'fine_amount' => $loan->fine_amount ?? 0,
            'fine_paid' => (bool) ($loan->fine_paid ?? false),
            'fine_paid_at' => $loan->fine_paid_at,
            'estimated_fine_if_return_now' => $estimated,
        ], 'Fine info');
    }

    // POST /api/loans/{loan}/pay-fine
    public function payFine(Request $request, Loan $loan)
    {
        $user = $request->user();

        // admin tidak boleh bayar
        if ($user->role === 'admin') {
            return ApiResponse::error('Admin tidak diizinkan melakukan pembayaran denda.', 403);
        }

        // member hanya boleh bayar miliknya sendiri
        if ($loan->user_id !== $user->id) {
            return ApiResponse::error('Forbidden', 403);
        }

        $fineAmount = (int) ($loan->fine_amount ?? 0);

        if ($fineAmount <= 0) {
            return ApiResponse::error('Tidak ada denda yang harus dibayar.', 400);
        }

        if ((bool) $loan->fine_paid === true) {
            return ApiResponse::error('Denda sudah dibayar.', 400);
        }

        $loan->fine_paid = true;
        $loan->fine_paid_at = now();
        $loan->save();

        return ApiResponse::success([
            'loan_id' => $loan->id,
            'fine_amount' => $fineAmount,
            'fine_paid' => true,
            'fine_paid_at' => $loan->fine_paid_at,
        ], 'Denda berhasil dibayar');
    }
}
