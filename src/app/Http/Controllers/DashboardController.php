<?php

namespace App\Http\Controllers;

use App\Services\DocumentService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the dashboard (document list milik team user).
     */
    public function index(Request $request, DocumentService $documents): Response
    {
        $search = $request->string('search')->toString();

        return Inertia::render('Dashboard', [
            'documents' => $documents->listForUser($request->user(), $search),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }
}
