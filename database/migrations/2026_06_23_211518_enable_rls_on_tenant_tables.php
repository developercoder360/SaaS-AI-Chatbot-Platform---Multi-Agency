<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The tables that should be isolated per tenant using Postgres RLS.
     */
    protected array $tenantTables = [
        'leads',
        'conversations',
        'chat_messages',
        'knowledge_documents',
        'usage_logs',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tenantTables as $table) {
            // Enable Row-Level Security
            DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
            
            // Force RLS even for the table owner
            DB::statement("ALTER TABLE {$table} FORCE ROW LEVEL SECURITY");

            // Create the isolation policy
            // Policy allows access IF:
            // 1. The row's tenant_id matches the current Postgres session's tenant_id OR
            // 2. The Postgres session explicitly has bypass_rls set to 'on'
            DB::statement("
                CREATE POLICY tenant_isolation_policy ON {$table}
                FOR ALL
                USING (
                    tenant_id::text = current_setting('app.current_tenant_id', true) 
                    OR current_setting('app.bypass_rls', true) = 'on'
                )
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tenantTables as $table) {
            DB::statement("DROP POLICY IF EXISTS tenant_isolation_policy ON {$table}");
            DB::statement("ALTER TABLE {$table} NO FORCE ROW LEVEL SECURITY");
            DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
        }
    }
};
