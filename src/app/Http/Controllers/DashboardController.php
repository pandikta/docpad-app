<?php

namespace App\Http\Controllers;

use App\Services\DocumentService;
use App\Services\ExtensionService;
use App\Services\TeamService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the dashboard (document list milik team user).
     */
    public function index(Request $request, DocumentService $documents, TeamService $teams, ExtensionService $extensions): Response
    {
        $user = $request->user();
        $search = $request->string('search')->toString();

        return Inertia::render('Dashboard', [
            'documents' => $documents->listForUser($user, $search),
            'folders' => $documents->listFolders(),
            'allowedExtensions' => allowed_document_extensions(),
            'extensionMaxSizes' => $extensions->listActiveWithLimits(),
            'teams' => is_superadmin($user) ? $teams->listActiveTeams() : [],
            'filters' => [
                'search' => $search,
            ],
        ]);
    }
}
