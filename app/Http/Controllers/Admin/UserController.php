<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SellerStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $term = $request->string('q')->trim()->limit(100, '')->value();

        $users = User::query()
            ->with(['roles:id,name', 'sellerProfile:id,user_id,username,status'])
            ->when($term, fn ($q) => $q->where(fn ($q) => $q->where('email', 'like', "%{$term}%")->orWhere('name', 'like', "%{$term}%")))
            ->when($request->boolean('suspended'), fn ($q) => $q->whereNotNull('suspended_at'))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function suspend(Request $request, User $user)
    {
        abort_if($user->id === $request->user()->id || $user->isAdmin(), 403, 'Admins cannot be suspended here.');

        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);

        DB::transaction(function () use ($user, $data) {
            $user->forceFill(['suspended_at' => now(), 'suspension_reason' => $data['reason']])->save();

            // A suspended seller's shop and products disappear from the marketplace.
            if ($user->sellerProfile?->status === SellerStatus::Approved) {
                $user->sellerProfile->update(['status' => SellerStatus::Suspended]);
            }

            // Kill active sessions (database session driver).
            DB::table('sessions')->where('user_id', $user->id)->delete();
        });

        return back()->with('status', __(':name suspended.', ['name' => $user->name]));
    }

    public function unsuspend(User $user)
    {
        $user->forceFill(['suspended_at' => null, 'suspension_reason' => null])->save();

        return back()->with('status', __(':name reactivated. Re-approve their seller profile separately if needed.', ['name' => $user->name]));
    }
}
