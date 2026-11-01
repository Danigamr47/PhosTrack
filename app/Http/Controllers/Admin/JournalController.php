<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JournalActivite;

class JournalController extends Controller
{
    public function index()
    {
        $entrees = JournalActivite::with('user')->latest('date_heure')->paginate(25);

        return view('admin.journal.index', compact('entrees'));
    }
}