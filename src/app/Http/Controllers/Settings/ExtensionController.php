<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\DocumentExtension;
use App\Services\ExtensionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ExtensionController extends Controller
{
    public function __construct(
        private readonly ExtensionService $extensions,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Settings/MasterExtensions', [
            'extensions' => $this->extensions->listAll(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $normalized = strtolower(ltrim(trim((string) $request->input('extension')), '.'));
        $request->merge(['extension' => $normalized]);

        $validated = $request->validate([
            'extension' => ['required', 'string', 'max:20', 'alpha_num', 'unique:mst_extensions,extension'],
            'label' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'max_size_kb' => ['required', 'integer', 'min:1', 'max:102400'],
            'is_active' => ['boolean'],
        ]);

        $ext = $this->extensions->create($validated);

        return back()->with('success', "Extension .{$ext->extension} added.");
    }

    public function update(Request $request, DocumentExtension $extension): RedirectResponse
    {
        $normalized = strtolower(ltrim(trim((string) $request->input('extension')), '.'));
        $request->merge(['extension' => $normalized]);

        $validated = $request->validate([
            'extension' => [
                'required',
                'string',
                'max:20',
                'alpha_num',
                Rule::unique('mst_extensions', 'extension')->ignore($extension->id),
            ],
            'label' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'max_size_kb' => ['required', 'integer', 'min:1', 'max:102400'],
            'is_active' => ['boolean'],
        ]);

        $this->extensions->update($extension, $validated);

        return back()->with('success', "Extension .{$extension->extension} updated.");
    }

    public function destroy(DocumentExtension $extension): RedirectResponse
    {
        $code = $extension->extension;
        $this->extensions->delete($extension);

        return back()->with('success', "Extension .{$code} deleted.");
    }
}
