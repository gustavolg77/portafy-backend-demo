<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexes('usuarios', [
            ['columns' => ['proveedor', 'proveedor_id'], 'name' => 'usuarios_proveedor_proveedor_id_index'],
            ['columns' => ['estado'], 'name' => 'usuarios_estado_index'],
            ['columns' => ['nombre', 'apellido'], 'name' => 'usuarios_nombre_apellido_index'],
        ]);

        $this->addIndexes('habilidades', [
            ['columns' => ['usuario_id', 'tipo'], 'name' => 'habilidades_usuario_tipo_index'],
            ['columns' => ['nombre'], 'name' => 'habilidades_nombre_index'],
        ]);

        $this->addIndexes('experiencias', [
            ['columns' => ['usuario_id'], 'name' => 'experiencias_usuario_id_index'],
            ['columns' => ['usuario_id', 'fecha_inicio', 'fecha_fin'], 'name' => 'experiencias_usuario_fechas_index'],
            ['columns' => ['empresa'], 'name' => 'experiencias_empresa_index'],
        ]);

        $this->addIndexes('proyectos', [
            ['columns' => ['usuario_id', 'estado'], 'name' => 'proyectos_usuario_estado_index'],
            ['columns' => ['usuario_id', 'created_at'], 'name' => 'proyectos_usuario_created_at_index'],
        ]);

        $this->addIndexes('redes_sociales', [
            ['columns' => ['usuario_id'], 'name' => 'redes_sociales_usuario_id_index'],
        ]);

        $this->addIndexes('formaciones_academicas', [
            ['columns' => ['usuario_id'], 'name' => 'formaciones_usuario_id_index'],
            ['columns' => ['usuario_id', 'fecha_inicio', 'fecha_fin'], 'name' => 'formaciones_usuario_fechas_index'],
            ['columns' => ['institucion'], 'name' => 'formaciones_institucion_index'],
        ]);

        $this->addIndexes('historial_cambios', [
            ['columns' => ['usuario_id'], 'name' => 'historial_usuario_index'],
            ['columns' => ['tabla_modificada', 'registro_id'], 'name' => 'historial_tabla_registro_index'],
            ['columns' => ['usuario_id', 'created_at'], 'name' => 'historial_usuario_fecha_index'],
        ]);
    }

    public function down(): void
    {
        $this->dropIndexes('usuarios', [
            'usuarios_proveedor_proveedor_id_index',
            'usuarios_estado_index',
            'usuarios_nombre_apellido_index',
        ]);

        $this->dropIndexes('habilidades', [
            'habilidades_usuario_tipo_index',
            'habilidades_nombre_index',
        ]);

        $this->dropIndexes('experiencias', [
            'experiencias_usuario_id_index',
            'experiencias_usuario_fechas_index',
            'experiencias_empresa_index',
        ]);

        $this->dropIndexes('proyectos', [
            'proyectos_usuario_estado_index',
            'proyectos_usuario_created_at_index',
        ]);

        $this->dropIndexes('redes_sociales', [
            'redes_sociales_usuario_id_index',
        ]);

        $this->dropIndexes('formaciones_academicas', [
            'formaciones_usuario_id_index',
            'formaciones_usuario_fechas_index',
            'formaciones_institucion_index',
        ]);

        $this->dropIndexes('historial_cambios', [
            'historial_usuario_index',
            'historial_tabla_registro_index',
            'historial_usuario_fecha_index',
        ]);
    }

    private function addIndexes(string $table, array $indexes): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        foreach ($indexes as $index) {
            if (! $this->tableHasColumns($table, $index['columns'])) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($index) {
                $blueprint->index($index['columns'], $index['name']);
            });
        }
    }

    private function dropIndexes(string $table, array $indexes): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        foreach ($indexes as $indexName) {
            Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
                $blueprint->dropIndex($indexName);
            });
        }
    }

    private function tableHasColumns(string $table, array $columns): bool
    {
        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }
};
