<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('PROJECT_PUBLICATION');

        if (Schema::hasTable('PUBLICATION_DETAIL') && ! Schema::hasColumn('PUBLICATION_DETAIL', 'id_experience')) {
            Schema::table('PUBLICATION_DETAIL', function (Blueprint $table) {
                $table->unsignedBigInteger('id_experience')->nullable();
                $table->foreign('id_experience')->references('id_experience')->on('EXPERIENCE')->nullOnDelete();
            });
        }

        DB::statement('CREATE INDEX IF NOT EXISTS idx_publication_state_created ON "PUBLICATION" (state, created_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_publication_profile_created ON "PUBLICATION" (id_profile, created_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_publication_detail_publication ON "PUBLICATION_DETAIL" (id_publication)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_publication_detail_project ON "PUBLICATION_DETAIL" (id_project)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_publication_detail_experience ON "PUBLICATION_DETAIL" (id_experience)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_comment_publication_created ON "COMMENT" (id_publication, created_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_reaction_publication_reactor ON "REACTION" (id_publication, id_reactor)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_saved_publication_profile ON "SAVED" (id_publication, id_profile)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_saved_publication_profile');
        DB::statement('DROP INDEX IF EXISTS idx_reaction_publication_reactor');
        DB::statement('DROP INDEX IF EXISTS idx_comment_publication_created');
        DB::statement('DROP INDEX IF EXISTS idx_publication_detail_experience');
        DB::statement('DROP INDEX IF EXISTS idx_publication_detail_project');
        DB::statement('DROP INDEX IF EXISTS idx_publication_detail_publication');
        DB::statement('DROP INDEX IF EXISTS idx_publication_profile_created');
        DB::statement('DROP INDEX IF EXISTS idx_publication_state_created');

        if (Schema::hasTable('PUBLICATION_DETAIL') && Schema::hasColumn('PUBLICATION_DETAIL', 'id_experience')) {
            Schema::table('PUBLICATION_DETAIL', function (Blueprint $table) {
                $table->dropForeign(['id_experience']);
                $table->dropColumn('id_experience');
            });
        }
    }
};
