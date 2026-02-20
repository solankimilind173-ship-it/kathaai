<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HelpController extends Controller
{
    /**
     * Show help / FAQ page.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('Help/Index');
    }
}
