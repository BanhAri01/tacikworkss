<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TagRequest;
use App\Models\Tag;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index(Request $request)
    {
        $tags = Tag::withCount('products')
            ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->where('name', 'like', "%{$term}%"))
            ->orderBy('name')
            ->paginate(50)
            ->withQueryString();

        return view('admin.tags.index', compact('tags'));
    }

    public function store(TagRequest $request)
    {
        Tag::create($request->validated());

        return back()->with('status', __('Tag created.'));
    }

    public function update(TagRequest $request, Tag $tag)
    {
        $tag->update($request->validated());

        return back()->with('status', __('Tag renamed.'));
    }

    public function destroy(Tag $tag)
    {
        $tag->delete();

        return back()->with('status', __('Tag deleted.'));
    }
}
