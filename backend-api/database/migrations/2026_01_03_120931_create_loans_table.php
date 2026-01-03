<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('book_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->dateTime('borrowed_at');
            $table->dateTime('due_at');
            $table->dateTime('returned_at')->nullable();

            $table->unsignedInteger('fine_amount')->default(0); // rupiah
            $table->boolean('fine_paid')->default(false);
            $table->timestamp('fine_paid_at')->nullable();

            $table->string('status', 20)->default('BORROWED'); // BORROWED|RETURNED|LATE

            $table->timestamps();

            $table->index(['user_id', 'book_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
