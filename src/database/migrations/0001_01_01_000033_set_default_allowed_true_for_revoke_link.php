<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('mst_functions')
            ->where('code', 'documents.revoke_link')
            ->update([
                'default_allowed' => true,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('mst_functions')
            ->where('code', 'documents.revoke_link')
            ->update([
                'default_allowed' => false,
                'updated_at' => now(),
            ]);
    }
};
