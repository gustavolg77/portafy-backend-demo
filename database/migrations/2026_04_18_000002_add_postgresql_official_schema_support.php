<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('PROVIDER') && ! Schema::hasColumn('PROVIDER', 'provider_user_id')) {
            Schema::table('PROVIDER', function (Blueprint $table) {
                $table->string('provider_user_id')->nullable()->after('provider');
                $table->index(['provider', 'provider_user_id'], 'provider_provider_user_id_index');
            });
        }

        if (Schema::hasTable('SOCIAL_NETWORK') && ! Schema::hasColumn('SOCIAL_NETWORK', 'url')) {
            Schema::table('SOCIAL_NETWORK', function (Blueprint $table) {
                $table->string('url')->nullable()->after('id_platform');
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

        if (Schema::hasTable('PROVIDER') && Schema::hasColumn('PROVIDER', 'provider_user_id')) {
            Schema::table('PROVIDER', function (Blueprint $table) {
                $table->dropIndex('provider_provider_user_id_index');
                $table->dropColumn('provider_user_id');
            });
        }
    }
};
