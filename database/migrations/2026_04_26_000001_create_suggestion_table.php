<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('SUGGESTION', function (Blueprint $table) {
            $table->bigIncrements('id_suggestion');
            $table->unsignedBigInteger('id_profile');
            $table->text('description');
            $table->enum('type', ['agregar', 'idea', 'mejora', 'eliminar'])->default('idea');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_profile')
                ->references('id_profile')
                ->on('PROFILE')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->index('id_profile');
            $table->index('type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('SUGGESTION');
    }
};