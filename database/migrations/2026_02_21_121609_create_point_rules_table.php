<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('point_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('spend_amount', 10, 2)->nullable()->comment('Amount to spend to earn points_per_unit points');
            $table->decimal('minimum_spend', 10, 2)->nullable()->default(0);
            $table->unsignedInteger('points_per_unit')->nullable()->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_rules');
    }
};
