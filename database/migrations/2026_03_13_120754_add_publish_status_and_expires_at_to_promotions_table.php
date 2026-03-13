<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->string('publish_status')->default('draft')->after('type');
            $table->timestamp('expires_at')->nullable()->after('published_at');
        });

        DB::table('promotions')
            ->where('is_published', true)
            ->update(['publish_status' => 'published']);
    }

    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn(['publish_status', 'expires_at']);
        });
    }
};
