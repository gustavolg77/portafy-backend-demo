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
        if (!Schema::hasTable('profile')) {
            return;
        }

        Schema::table('profile', function (Blueprint $table) {
            // NUEVOS CAMPOS PARA HU4
            if (!Schema::hasColumn('profile', 'titulo')) {
                $table->string('titulo')->nullable()->after('usuario_id');
            }
            if (!Schema::hasColumn('profile', 'skills')) {
                $table->text('skills')->nullable()->after('biografia');
            }
            if (!Schema::hasColumn('profile', 'github')) {
                $table->string('github')->nullable()->after('skills');
            }
            if (!Schema::hasColumn('profile', 'linkedin')) {
                $table->string('linkedin')->nullable()->after('github');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('profile')) {
            return;
        }

        Schema::table('profile', function (Blueprint $table) {
            $columns = ['titulo', 'skills', 'github', 'linkedin'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('profile', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
