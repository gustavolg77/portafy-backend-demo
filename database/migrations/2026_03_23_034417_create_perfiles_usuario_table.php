<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function usingPostgreSql(): bool
    {
        return config('database.default') === 'pgsql';
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if ($this->usingPostgreSql()) {
            return;
        }

        if (Schema::hasTable('perfiles_usuarios')) {
            return;
        }

        Schema::create('perfiles_usuarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Relacion con login
            $table->string('nombre');
            $table->string('apellido');
            $table->string('profesion')->nullable();
            $table->string('universidad')->nullable();
            $table->string('ubicacion')->nullable();
            $table->date('fecha_nacimiento')->nullable(); // Tu campo especial
            $table->string('foto_perfil')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if ($this->usingPostgreSql()) {
            return;
        }

        Schema::dropIfExists('perfiles_usuarios');
    }
};
