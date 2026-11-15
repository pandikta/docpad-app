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
        abort_unless(can_use_function('documents.upload', $request->user()), 403);

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
     * Buat file dokumen baru langsung dari form (pilih ekstensi dari daftar yang diizinkan).
     */
    public function create(Request $request): RedirectResponse
    {
        abort_unless(can_use_function('documents.create', $request->user()), 403);

        $allowed = implode(',', allowed_document_extensions());

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'extension' => ['required', 'string', "in:{$allowed}"],
            'visibility' => ['nullable', 'string', 'in:publish,private'],
            'content' => ['nullable', 'string', 'max:2000000'],
        ]);

        $document = $this->documents->createBlank(
            $request->user(),
            $validated['name'],
            $validated['extension'],
            $validated['content'] ?? '',
            $validated['visibility'] ?? 'publish',
        );

        return back()->with('success', "{$document->name} created successfully.");
    }

    /**
     * Simpan hasil edit dari text editor.
     */
    public function update(Request $request, Document $document): RedirectResponse
    {
        abort_unless(can_use_function('documents.edit', $request->user()), 403);
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
        abort_unless(can_use_function('documents.rename', $request->user()), 403);
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
     * Assign dokumen ke satu atau banyak tim (khusus superadmin).
     */
    public function assignTeams(Request $request, Document $document): RedirectResponse
    {
        abort_unless(is_superadmin($request->user()), 403);
        $this->documents->authorizeAccess($request->user(), $document);
        abort_if($document->visibility === 'private', 403, 'Private documents cannot be assigned to teams.');

        $validated = $request->validate([
            'team_ids' => ['present', 'array'],
            'team_ids.*' => ['integer', 'exists:mst_teams,id'],
        ]);

        $this->documents->assignTeams($document, $validated['team_ids'], $request->user());

        return back()->with('success', "Team assignment updated for {$document->name}.");
    }

    /**
     * Ubah status visibility dokumen (publish / private) oleh pemilik dokumen.
     */
    public function updateVisibility(Request $request, Document $document): RedirectResponse
    {
        $this->documents->authorizeAccess($request->user(), $document);
        abort_unless((int) $document->user_id === (int) $request->user()->id, 403, 'Only the document owner can change visibility.');

        $validated = $request->validate([
            'visibility' => ['required', 'string', 'in:publish,private'],
        ]);

        $this->documents->updateVisibility($document, $validated['visibility'], $request->user());

        return back()->with('success', "{$document->name} is now {$validated['visibility']}.");
    }

    /**
     * Hapus dokumen.
     */
    public function destroy(Request $request, Document $document): RedirectResponse
    {
        abort_unless(can_use_function('documents.delete', $request->user()), 403);
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
        abort_unless(can_use_function('documents.download', $request->user()), 403);
        $this->documents->authorizeAccess($request->user(), $document);

        $text = $this->documents->toPlainText($document);

        return response()->streamDownload(
            fn () => print($text),
            $document->name,
            ['Content-Type' => 'text/plain; charset=UTF-8'],
        );
    }
}
