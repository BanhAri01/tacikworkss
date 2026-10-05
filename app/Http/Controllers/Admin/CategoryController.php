<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        return view('admin.categories.index', [
            'options' => Category::options(),
            'categories' => Category::withCount('products')->get()->keyBy('id'),
        ]);
    }

    public function store(CategoryRequest $request)
    {
        Category::create($request->validated() + ['sort' => 0]);

        return back()->with('status', __('Category created.'));
    }

    public function edit(Category $category)
    {
        return view('admin.categories.edit', [
            'category' => $category,
            'options' => Category::options()->except($category->descendantAndSelfIds()),
        ]);
    }

    public function update(CategoryRequest $request, Category $category)
    {
        $category->update($request->validated());

        return redirect()->route('admin.categories.index')->with('status', __('Category updated.'));
    }

    public function destroy(Category $category)
    {
        if ($category->products()->withTrashed()->exists() || $category->children()->exists()) {
            return back()->withErrors(['category' => __('Move its products and sub-categories first.')]);
        }

        $category->delete();

        return back()->with('status', __('Category deleted.'));
    }
}
