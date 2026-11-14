<?php

namespace Database\Seeders;

use App\Models\AppFunction;
use Illuminate\Database\Seeder;

class FunctionAuthSeeder extends Seeder
{
    /**
     * Daftar fungsi/aksi bawaan aplikasi yang bisa diatur hak aksesnya oleh Superadmin.
     */
    public function run(): void
    {
        $functions = [
            [
                'code' => 'documents.create',
                'name' => 'Create File',
                'group' => 'Documents',
                'description' => 'Create a new blank document from the dashboard',
                'default_allowed' => true,
            ],
            [
                'code' => 'documents.upload',
                'name' => 'Upload File',
                'group' => 'Documents',
                'description' => 'Upload single or multiple documents',
                'default_allowed' => true,
            ],
            [
                'code' => 'documents.edit',
                'name' => 'Edit Document',
                'group' => 'Documents',
                'description' => 'Open and save changes in the document editor',
                'default_allowed' => true,
            ],
            [
                'code' => 'documents.rename',
                'name' => 'Rename Document',
                'group' => 'Documents',
                'description' => 'Rename existing documents',
                'default_allowed' => true,
            ],
            [
                'code' => 'documents.delete',
                'name' => 'Delete Document',
                'group' => 'Documents',
                'description' => 'Permanently delete documents',
                'default_allowed' => true,
            ],
            [
                'code' => 'documents.download',
                'name' => 'Download Document',
                'group' => 'Documents',
                'description' => 'Download documents as text files',
                'default_allowed' => true,
            ],
            [
                'code' => 'documents.logs',
                'name' => 'View Document Logs',
                'group' => 'Documents',
                'description' => 'Inspect document activity timeline logs',
                'default_allowed' => true,
            ],
        ];

        foreach ($functions as $fn) {
            AppFunction::updateOrCreate(
                ['code' => $fn['code']],
                $fn,
            );
        }
    }
}
