<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LicenseRequest;
use App\Models\License;
use App\Models\ProductLicensePrice;

class LicenseController extends Controller
{
    public function index()
    {
        return view('admin.licenses.index', ['licenses' => License::orderBy('sort')->get()]);
    }

    public function store(LicenseRequest $request)
    {
        License::create($request->validated() + ['sort' => 0]);

        return back()->with('status', __('License created.'));
    }

    public function edit(License $license)
    {
        return view('admin.licenses.edit', compact('license'));
    }

    public function update(LicenseRequest $request, License $license)
    {
        $license->update($request->validated());

        return redirect()->route('admin.licenses.index')->with('status', __('License updated.'));
    }

    public function destroy(License $license)
    {
        if (ProductLicensePrice::where('license_id', $license->id)->exists()) {
            return back()->withErrors(['license' => __('This license is used by products and cannot be deleted.')]);
        }

        $license->delete();

        return back()->with('status', __('License deleted.'));
    }
}
