<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('loyverse_receipt_id')->unique();
            $table->string('loyverse_customer_id')->nullable();
            $table->decimal('total_amount', 10, 2);
            $table->unsignedInteger('points_earned')->default(0);
            $table->string('status')->default('pending');
            $table->json('loyverse_payload');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
