<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    public function show(Request $request, Collection $collection)
    {
        abort_unless($collection->is_public || $request->user()?->id === $collection->user_id, 404);

        return view('collections.show', [
            'collection' => $collection->load('user:id,name'),
            'products' => $collection->products()->published()->with(['cover', 'seller.sellerProfile:id,user_id,username,display_name'])->paginate(24),
        ]);
    }
}
