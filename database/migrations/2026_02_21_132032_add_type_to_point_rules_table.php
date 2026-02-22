<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('point_rules', function (Blueprint $table) {
            $table->string('type')->default('spend_based')->after('name');
            $table->unsignedInteger('points_per_item')->nullable()->after('points_per_unit');
            $table->decimal('spend_amount', 10, 2)->nullable()->change();
            $table->decimal('minimum_spend', 10, 2)->nullable()->default(0)->change();
            $table->unsignedInteger('points_per_unit')->nullable()->default(1)->change();
        });
    }

    public function down(): void
    {
        Schema::table('point_rules', function (Blueprint $table) {
            $table->dropColumn(['type', 'points_per_item']);
            $table->decimal('spend_amount', 10, 2)->nullable(false)->change();
            $table->decimal('minimum_spend', 10, 2)->nullable(false)->default(0)->change();
            $table->unsignedInteger('points_per_unit')->nullable(false)->default(1)->change();
        });
    }
};
