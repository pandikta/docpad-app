<?php

namespace Database\Seeders;

use App\Models\DocumentExtension;
use Illuminate\Database\Seeder;

class ExtensionSeeder extends Seeder
{
    /**
     * Ekstensi dokumen default aplikasi (dokumen, spreadsheet, presentasi, teks, dan gambar).
     */
    public function run(): void
    {
        $extensions = [
            // Text & Data
            ['extension' => 'txt', 'label' => 'Plain Text', 'description' => 'Standard plain text document (.txt)', 'max_size_kb' => 1024, 'is_active' => true],
            ['extension' => 'md', 'label' => 'Markdown', 'description' => 'Markdown formatted text document (.md)', 'max_size_kb' => 1024, 'is_active' => true],
            ['extension' => 'csv', 'label' => 'CSV Spreadsheet', 'description' => 'Comma-separated values (.csv)', 'max_size_kb' => 2048, 'is_active' => true],

            // Documents, Spreadsheets & Presentations
            ['extension' => 'pdf', 'label' => 'PDF Document', 'description' => 'Portable Document Format (.pdf)', 'max_size_kb' => 10240, 'is_active' => true],
            ['extension' => 'docx', 'label' => 'Word Document', 'description' => 'Microsoft Word OpenXML (.docx)', 'max_size_kb' => 10240, 'is_active' => true],
            ['extension' => 'doc', 'label' => 'Word 97-2003', 'description' => 'Legacy Microsoft Word (.doc)', 'max_size_kb' => 10240, 'is_active' => true],
            ['extension' => 'xlsx', 'label' => 'Excel Workbook', 'description' => 'Microsoft Excel OpenXML (.xlsx)', 'max_size_kb' => 10240, 'is_active' => true],
            ['extension' => 'xls', 'label' => 'Excel 97-2003', 'description' => 'Legacy Microsoft Excel (.xls)', 'max_size_kb' => 10240, 'is_active' => true],
            ['extension' => 'pptx', 'label' => 'PowerPoint', 'description' => 'Microsoft PowerPoint OpenXML (.pptx)', 'max_size_kb' => 10240, 'is_active' => true],
            ['extension' => 'ppt', 'label' => 'PowerPoint 97-2003', 'description' => 'Legacy Microsoft PowerPoint (.ppt)', 'max_size_kb' => 10240, 'is_active' => true],

            // Images
            ['extension' => 'png', 'label' => 'PNG Image', 'description' => 'Portable Network Graphics (.png)', 'max_size_kb' => 5120, 'is_active' => true],
            ['extension' => 'jpg', 'label' => 'JPG Image', 'description' => 'JPEG image (.jpg)', 'max_size_kb' => 5120, 'is_active' => true],
            ['extension' => 'jpeg', 'label' => 'JPEG Image', 'description' => 'JPEG image (.jpeg)', 'max_size_kb' => 5120, 'is_active' => true],
            ['extension' => 'webp', 'label' => 'WebP Image', 'description' => 'WebP image (.webp)', 'max_size_kb' => 5120, 'is_active' => true],
            ['extension' => 'svg', 'label' => 'SVG Vector', 'description' => 'Scalable Vector Graphics (.svg)', 'max_size_kb' => 2048, 'is_active' => true],
        ];

        foreach ($extensions as $ext) {
            DocumentExtension::updateOrCreate(
                ['extension' => $ext['extension']],
                $ext,
            );
        }
    }
}
