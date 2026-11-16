<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();
        $images = [
            ['extension' => 'png', 'label' => 'PNG Image', 'description' => 'Portable Network Graphics image'],
            ['extension' => 'jpg', 'label' => 'JPG Image', 'description' => 'JPEG image'],
            ['extension' => 'jpeg', 'label' => 'JPEG Image', 'description' => 'JPEG image'],
            ['extension' => 'webp', 'label' => 'WebP Image', 'description' => 'WebP image'],
            ['extension' => 'gif', 'label' => 'GIF Image', 'description' => 'Graphics Interchange Format image'],
        ];

        foreach ($images as $img) {
            DB::table('mst_extensions')->updateOrInsert(
                ['extension' => $img['extension']],
                [
                    'label' => $img['label'],
                    'description' => $img['description'],
                    'max_size_kb' => 2120,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('mst_extensions')->whereIn('extension', ['png', 'jpg', 'jpeg', 'webp', 'gif'])->delete();
    }
};
