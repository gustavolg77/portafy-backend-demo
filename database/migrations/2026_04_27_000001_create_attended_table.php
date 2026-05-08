<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ATTENDED', function (Blueprint $table) {
            $table->bigIncrements('id_attended');
            $table->unsignedBigInteger('id_suggestion')->nullable();
            $table->unsignedBigInteger('id_profile'); // Administrador que atiende
            $table->enum('state', ['accepted', 'rejected', 'in_discussion', 'higher', 'ignored'])->default('accepted');
            $table->text('note')->nullable(); // Nota del administrador
            $table->timestamp('attended_at')->useCurrent();
            $table->timestamps();

            // Foreign keys
            $table->foreign('id_suggestion')
                ->references('id_suggestion')
                ->on('SUGGESTION')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->foreign('id_profile')
                ->references('id_profile')
                ->on('PROFILE')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            // Indexes
            $table->index('id_suggestion');
            $table->index('id_profile');
            $table->index('state');
            $table->index('attended_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ATTENDED');
    }
};