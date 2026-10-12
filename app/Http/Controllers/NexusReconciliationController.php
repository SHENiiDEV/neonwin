<?php

namespace App\Http\Controllers;

use App\Models\AdminAudit;
use App\Models\NexusOperation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NexusReconciliationController extends Controller
{
    public function update(Request $request, NexusOperation $operation): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:failed,finished,canceled,active,waiting'], 'evidence' => ['required', 'string', 'min:20', 'max:2000']]);
        DB::transaction(function () use ($request, $operation, $data): void {
            $locked = NexusOperation::whereKey($operation->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($locked->status, ['unknown', 'needs_review'], true) || (in_array($locked->status, ['sending', 'canceling'], true) && $locked->updated_at->lt(now()->subMinutes(2))) || ($locked->method === 'tour_create' && $locked->expires_at?->isPast()), 409, 'This operation does not need reconciliation.');
            abort_if($locked->method === 'control_rtp' || $locked->method === 'control_users_rtp', 422, 'RTP must be confirmed by a new audited control request.');
            $before = $locked->only(['status', 'progress']);
            $locked->update(['status' => $data['status'], 'active_tour_key' => in_array($data['status'], ['failed', 'finished', 'canceled'], true) ? null : $locked->active_tour_key]);
            AdminAudit::create(['actor_id' => $request->user()->id, 'action' => 'nexus_reconciled', 'target' => $locked->external_id, 'before' => $before, 'after' => $data]);
        });

        return back()->with('success', 'External settlement recorded. No player funds were changed.');
    }
}
