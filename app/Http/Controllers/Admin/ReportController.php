<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $status = in_array($request->query('status'), ['open', 'resolved', 'dismissed'], true) ? $request->query('status') : 'open';

        $reports = Report::query()
            ->with(['product.seller.sellerProfile:id,user_id,username', 'reporter:id,name,email'])
            ->where('status', $status)
            ->oldest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.reports.index', compact('reports', 'status'));
    }

    /** Resolve = take the product down (rejected + reason); dismiss = keep it. */
    public function update(Request $request, Report $report)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['takedown', 'dismiss'])],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($report, $data) {
            if ($data['action'] === 'takedown' && $report->product) {
                $report->product->update([
                    'status' => ProductStatus::Rejected,
                    'rejection_reason' => 'Removed after a '.$report->reason.' report. '.($data['admin_note'] ?? ''),
                ]);
                $report->product->unsearchable();

                // Close other open reports for the same product too.
                Report::where('product_id', $report->product_id)->where('status', 'open')->whereKeyNot($report->id)
                    ->update(['status' => 'resolved', 'resolved_at' => now(), 'admin_note' => 'Closed with report #'.$report->id]);
            }

            $report->update([
                'status' => $data['action'] === 'takedown' ? 'resolved' : 'dismissed',
                'admin_note' => $data['admin_note'] ?? null,
                'resolved_at' => now(),
            ]);
        });

        return back()->with('status', $data['action'] === 'takedown' ? __('Product taken down.') : __('Report dismissed.'));
    }
}
