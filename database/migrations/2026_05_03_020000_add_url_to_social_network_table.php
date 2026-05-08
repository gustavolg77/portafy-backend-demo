<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('SOCIAL_NETWORK') && ! Schema::hasColumn('SOCIAL_NETWORK', 'url')) {
            Schema::table('SOCIAL_NETWORK', function (Blueprint $table) {
                $table->string('url')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('SOCIAL_NETWORK') && Schema::hasColumn('SOCIAL_NETWORK', 'url')) {
            Schema::table('SOCIAL_NETWORK', function (Blueprint $table) {
                $table->dropColumn('url');
            });
        }
    }
};
