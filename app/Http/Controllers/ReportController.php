<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function create(Product $product)
    {
        abort_unless($product->isPublished(), 404);

        return view('reports.create', compact('product'));
    }

    public function store(Request $request, Product $product)
    {
        abort_unless($product->isPublished(), 404);

        $data = $request->validate([
            'reason' => ['required', Rule::in(array_keys(Report::REASONS))],
            'details' => ['required', 'string', 'min:20', 'max:5000'],
            'original_url' => ['nullable', 'required_if:reason,copyright', 'url:http,https', 'max:500'],
        ]);

        // One open report per user and product; resubmitting just keeps the existing one.
        Report::firstOrCreate(
            ['product_id' => $product->id, 'reporter_id' => $request->user()->id, 'status' => 'open'],
            $data,
        );

        return redirect()->route('products.show', $product)->with('status', __('Thanks — our team will review your report.'));
    }
}
