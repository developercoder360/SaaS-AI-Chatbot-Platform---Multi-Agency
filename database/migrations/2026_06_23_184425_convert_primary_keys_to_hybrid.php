<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The tables that have UUID primary keys and need to be converted to BIGINT.
     */
    protected array $tables = [
        'tenants',
        'users',
        'conversations',
        'chat_messages',
        'leads',
        'knowledge_documents',
        'usage_logs',
    ];

    /**
     * The foreign keys that need to be migrated from UUID to BIGINT.
     * Format: 'table_name' => ['foreign_key_column' => 'referenced_table']
     */
    protected array $foreignKeys = [
        'users' => ['tenant_id' => 'tenants'],
        'conversations' => ['tenant_id' => 'tenants'],
        'chat_messages' => ['tenant_id' => 'tenants', 'conversation_id' => 'conversations'],
        'leads' => ['tenant_id' => 'tenants', 'conversation_id' => 'conversations'],
        'knowledge_documents' => ['tenant_id' => 'tenants'],
        'usage_logs' => ['tenant_id' => 'tenants'],
    ];

    public function up(): void
    {
        DB::transaction(function () {
            // Step 1: Drop foreign key constraints
            foreach ($this->foreignKeys as $table => $keys) {
                foreach ($keys as $fkColumn => $referencedTable) {
                    $constraintName = "{$table}_{$fkColumn}_foreign";
                    DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$constraintName}");
                }
            }

            // Step 2: For each table, add a new BIGINT auto-incrementing ID and rename old id to uuid
            foreach ($this->tables as $table) {
                // Drop primary key constraint
                $pkConstraint = "{$table}_pkey";
                DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$pkConstraint}");
                
                // Rename id to uuid
                Schema::table($table, function (Blueprint $t) {
                    $t->renameColumn('id', 'uuid');
                });

                // Make uuid unique
                Schema::table($table, function (Blueprint $t) {
                    $t->unique('uuid');
                });

                // Add new BIGINT id
                DB::statement("ALTER TABLE {$table} ADD COLUMN id BIGSERIAL PRIMARY KEY");
            }

            // Step 3: Handle foreign keys
            foreach ($this->foreignKeys as $table => $keys) {
                foreach ($keys as $fkColumn => $referencedTable) {
                    // Rename old fk to uuid
                    $fkUuidCol = "{$fkColumn}_uuid";
                    Schema::table($table, function (Blueprint $t) use ($fkColumn, $fkUuidCol) {
                        $t->renameColumn($fkColumn, $fkUuidCol);
                    });

                    // Add new bigint fk
                    Schema::table($table, function (Blueprint $t) use ($fkColumn) {
                        $t->unsignedBigInteger($fkColumn)->nullable();
                    });

                    // Backfill data
                    DB::statement("
                        UPDATE {$table} t
                        SET {$fkColumn} = r.id
                        FROM {$referencedTable} r
                        WHERE t.{$fkUuidCol} = r.uuid
                    ");

                    // Drop uuid fk and add constraints
                    Schema::table($table, function (Blueprint $t) use ($fkUuidCol, $fkColumn, $referencedTable) {
                        $t->dropColumn($fkUuidCol);
                        $t->foreign($fkColumn)->references('id')->on($referencedTable)->onDelete('cascade');
                    });
                }
            }
        });
    }

    public function down(): void
    {
        // Reverting this is extremely complex and involves the exact reverse process.
        // For the scope of this migration, we leave down() empty or throw an exception.
        throw new \Exception("Rolling back from Hybrid primary keys is not supported automatically.");
    }
};
