<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\LoanController;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me',     [AuthController::class, 'me']);
        Route::post('/logout',[AuthController::class, 'logout']);
    });
});

Route::middleware('auth:sanctum')->group(function () {

    // Categories (minimal list)
    Route::get('/categories', [CategoryController::class, 'index']);

    // Books (list & detail boleh member, CRUD admin)
    Route::get('/books', [BookController::class, 'index']);
    Route::get('/books/{id}', [BookController::class, 'show']);
    Route::get('/books/{book}/recommendations', [BookController::class, 'recommendations']);


    Route::middleware('isAdmin')->group(function () {
        Route::post('/books', [BookController::class, 'store']);
        Route::put('/books/{id}', [BookController::class, 'update']);
        Route::delete('/books/{id}', [BookController::class, 'destroy']);
    });

    // Loans
    Route::get('/loans', [LoanController::class, 'index']);        // member: own, admin: all
    Route::post('/loans/borrow', [LoanController::class, 'borrow']);
    Route::post('/loans/return', [LoanController::class, 'returnBook']);
    Route::get('/loans/{loan}/fine', [LoanController::class, 'fine']);
    Route::post('/loans/{loan}/pay-fine', [LoanController::class, 'payFine']);
});
