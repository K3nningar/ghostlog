<?php

namespace App\Http\Controllers;

use App\Models\GrimoireEntry;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GrimoireController extends Controller
{
    public function index(Request $request)
    {
        $locale = $request->session()->get('locale', 'fr');
        $search = trim((string) $request->query('search', ''));

        $entries = GrimoireEntry::query()
            ->where('locale', $locale)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%')
                        ->orWhere('content', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('category')
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        return Inertia::render('Grimoire/Index', [
            'entries' => $entries,
            'search' => $search,
        ]);
    }
}
