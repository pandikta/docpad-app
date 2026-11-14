<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\DocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(private DocumentService $documents)
    {
    }

    /**
     * Upload satu atau banyak file teks (.txt / .md) sekaligus milik team user.
     */
    public function store(Request $request): RedirectResponse
    {
        $allowed = implode(',', allowed_document_extensions());

        $request->validate([
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'file', 'max:1024', "extensions:{$allowed}"],
        ]);

        $uploaded = $this->documents->createManyFromUpload($request->user(), $request->file('files'));
        $count = $uploaded->count();

        $message = $count === 1
            ? "{$uploaded->first()->name} uploaded successfully."
            : "{$count} documents uploaded successfully.";

        return back()->with('success', $message);
    }

    /**
     * Simpan hasil edit dari text editor.
     */
    public function update(Request $request, Document $document): RedirectResponse
    {
        $this->documents->authorizeAccess($request->user(), $document);

        $validated = $request->validate([
            'content' => ['nullable', 'string', 'max:2000000'],
        ]);

        $this->documents->updateContent($document, $validated['content'] ?? '', $request->user());

        return back()->with('success', "{$document->name} saved.");
    }

    /**
     * Ubah nama dokumen.
     */
    public function rename(Request $request, Document $document): RedirectResponse
    {
        $this->documents->authorizeAccess($request->user(), $document);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! has_allowed_document_extension((string) $value)) {
                        $exts = '.'.implode(', .', allowed_document_extensions());
                        $fail("Document name must end with an allowed extension ({$exts}).");
                    }
                },
            ],
        ]);

        $this->documents->rename($document, $validated['name'], $request->user());

        return back()->with('success', "Document renamed to {$document->name}.");
    }

    /**
     * Hapus dokumen.
     */
    public function destroy(Request $request, Document $document): RedirectResponse
    {
        $this->documents->authorizeAccess($request->user(), $document);

        $name = $document->name;
        $this->documents->delete($document);

        return back()->with('success', "{$name} deleted.");
    }

    /**
     * Download dokumen sebagai file teks.
     */
    public function download(Request $request, Document $document): StreamedResponse
    {
        $this->documents->authorizeAccess($request->user(), $document);

        $text = $this->documents->toPlainText($document);

        return response()->streamDownload(
            fn () => print($text),
            $document->name,
            ['Content-Type' => 'text/plain; charset=UTF-8'],
        );
    }
}
