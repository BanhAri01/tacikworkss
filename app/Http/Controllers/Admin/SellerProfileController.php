<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Seller\ApproveSeller;
use App\Actions\Seller\SuspendSeller;
use App\Enums\SellerStatus;
use App\Http\Controllers\Controller;
use App\Models\SellerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SellerProfileController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', SellerProfile::class);

        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(SellerStatus::class)],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $status = SellerStatus::tryFrom($filters['status'] ?? '') ?? SellerStatus::Pending;

        $profiles = SellerProfile::query()
            ->with('user:id,name,email')
            ->where('status', $status)
            ->when($filters['q'] ?? null, fn ($query, $q) => $query->where(fn ($query) => $query
                ->where('username', 'like', "%{$q}%")
                ->orWhere('display_name', 'like', "%{$q}%")
                ->orWhereRelation('user', 'email', 'like', "%{$q}%")))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.sellers.index', compact('profiles', 'status'));
    }

    public function show(SellerProfile $sellerProfile)
    {
        Gate::authorize('view', $sellerProfile);

        return view('admin.sellers.show', ['profile' => $sellerProfile->load('user')]);
    }

    public function approve(SellerProfile $sellerProfile, ApproveSeller $approve)
    {
        Gate::authorize('approve', $sellerProfile);

        $approve->handle($sellerProfile);

        return back()->with('status', 'Seller @'.$sellerProfile->username.' approved.');
    }

    public function suspend(SellerProfile $sellerProfile, SuspendSeller $suspend)
    {
        Gate::authorize('suspend', $sellerProfile);

        $suspend->handle($sellerProfile);

        return back()->with('status', 'Seller @'.$sellerProfile->username.' suspended.');
    }
}
