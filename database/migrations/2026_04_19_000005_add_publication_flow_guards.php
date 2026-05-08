<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS publication_detail_project_unique ON "PUBLICATION_DETAIL" (id_project) WHERE id_project IS NOT NULL');
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS publication_detail_experience_unique ON "PUBLICATION_DETAIL" (id_experience) WHERE id_experience IS NOT NULL');
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS reaction_publication_reactor_unique ON "REACTION" (id_publication, id_reactor)');
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS saved_publication_profile_unique ON "SAVED" (id_publication, id_profile)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS saved_publication_profile_unique');
        DB::statement('DROP INDEX IF EXISTS reaction_publication_reactor_unique');
        DB::statement('DROP INDEX IF EXISTS publication_detail_experience_unique');
        DB::statement('DROP INDEX IF EXISTS publication_detail_project_unique');
    }
};
