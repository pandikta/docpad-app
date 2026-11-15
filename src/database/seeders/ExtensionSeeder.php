<?php

namespace Database\Seeders;

use App\Models\DocumentExtension;
use Illuminate\Database\Seeder;

class ExtensionSeeder extends Seeder
{
    /**
     * Ekstensi dokumen default aplikasi (.txt dan .md).
     */
    public function run(): void
    {
        $extensions = [
            [
                'extension' => 'txt',
                'label' => 'Plain Text',
                'description' => 'Standard plain text document (.txt)',
                'is_active' => true,
            ],
            [
                'extension' => 'md',
                'label' => 'Markdown',
                'description' => 'Markdown formatted text document (.md)',
                'is_active' => true,
            ],
        ];

        foreach ($extensions as $ext) {
            DocumentExtension::updateOrCreate(
                ['extension' => $ext['extension']],
                $ext,
            );
        }
    }
}
