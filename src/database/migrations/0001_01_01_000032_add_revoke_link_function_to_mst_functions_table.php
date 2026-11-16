<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('mst_functions')->where('code', 'documents.revoke_link')->exists()) {
            DB::table('mst_functions')->insert([
                'code' => 'documents.revoke_link',
                'name' => 'Revoke Share Link',
                'description' => 'Allow revoking shareable links created by other team members.',
                'default_allowed' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('mst_functions')->where('code', 'documents.revoke_link')->delete();
    }
};
