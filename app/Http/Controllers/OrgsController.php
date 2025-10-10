<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Org;

class OrgsController extends Controller
{
    public function index(Request $request)
{
    $orgs = Org::with('owner')
        ->orderBy('created_at', 'desc')
        ->paginate(6);

    if ($request->ajax()) {
        return response()->json([
            'orgs' => view('orgs.partials.orgs-list', compact('orgs'))->render(),
            'pagination' => view('components.pagination', [
                'paginator' => $orgs,
                'elements' => $orgs->links()->elements ?? []
            ])->render(),
        ]);
    }

    return view('orgs.index', compact('orgs'));
}

}
