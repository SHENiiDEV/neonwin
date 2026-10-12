<?php

namespace App\Http\Controllers;

use App\Models\AdminAudit;
use App\Models\KycSubmission;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KycController extends Controller
{
    public function index(Request $request): Response
    {
        $submission = $request->user()->kyc()->first();

        return Inertia::render('Verification', ['verification' => $submission ? array_merge($submission->only(['id', 'status', 'version', 'reason', 'reviewed_at']), ['has_document' => (bool) $submission->document_path, 'has_selfie' => (bool) $submission->selfie_path]) : null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'selfie' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
        ]);
        if (! $request->hasFile('document') && ! $request->hasFile('selfie')) {
            return back()->withErrors(['document' => 'Choose a document or selfie to upload.']);
        }
        $paths = [];
        try {
            foreach (['document', 'selfie'] as $kind) {
                if ($request->hasFile($kind)) {
                    $paths[$kind.'_path'] = $request->file($kind)->store('kyc/'.$request->user()->id, 'kyc');
                }
            }
            $oldPaths = DB::transaction(function () use ($request, $paths): array {
                User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
                $submission = KycSubmission::firstOrCreate(['user_id' => $request->user()->id]);
                $oldPaths = [];
                foreach ($paths as $key => $value) {
                    if ($submission->$key) {
                        $oldPaths[] = $submission->$key;
                    }
                    $submission->$key = $value;
                }
                $submission->status = $submission->document_path && $submission->selfie_path ? 'pending' : 'incomplete';
                $submission->version++;
                $submission->reason = null;
                $submission->reviewer_id = null;
                $submission->reviewed_at = null;
                $submission->save();

                return $oldPaths;
            });
        } catch (\Throwable $exception) {
            Storage::disk('kyc')->delete(array_values($paths));
            throw $exception;
        }
        Storage::disk('kyc')->delete($oldPaths);

        return back()->with('success', 'Files uploaded securely. Both files must be approved before the withdrawal entry appears.');
    }

    public function download(Request $request, KycSubmission $submission, string $kind): StreamedResponse
    {
        abort_unless($request->user()->is_admin || $request->user()->id === $submission->user_id, 404);
        abort_unless(in_array($kind, ['document', 'selfie'], true), 404);
        $path = $submission->{$kind.'_path'};
        abort_unless($path && Storage::disk('kyc')->exists($path), 404);
        if ($request->user()->is_admin) {
            AdminAudit::create(['actor_id' => $request->user()->id, 'action' => 'kyc_file_access', 'target' => (string) $submission->id, 'after' => ['kind' => $kind]]);
        }

        return Storage::disk('kyc')->download($path, $kind.'.'.pathinfo($path, PATHINFO_EXTENSION), ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function review(Request $request, KycSubmission $submission): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:approved,rejected'], 'reason' => ['required_if:status,rejected', 'nullable', 'string', 'max:1000'], 'version' => ['required', 'integer']]);
        DB::transaction(function () use ($request, $submission, $data): void {
            User::whereKey($submission->user_id)->lockForUpdate()->firstOrFail();
            $locked = KycSubmission::whereKey($submission->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->document_path && $locked->selfie_path, 422, 'Both files are required before review.');
            abort_unless($locked->version === $data['version'] && $locked->status === 'pending', 409, 'The submission changed. Refresh before reviewing.');
            $before = $locked->only(['status', 'version']);
            $locked->update(['status' => $data['status'], 'reason' => $data['reason'] ?? null, 'reviewer_id' => $request->user()->id, 'reviewed_at' => now()]);
            AdminAudit::create(['actor_id' => $request->user()->id, 'action' => 'kyc_review', 'target' => (string) $locked->id, 'before' => $before, 'after' => $locked->only(['status', 'version', 'reason', 'reviewer_id', 'reviewed_at'])]);
        });

        return back()->with('success', 'Verification review saved.');
    }

    public function withdrawal(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasApprovedKyc(), 403, 'Complete identity verification first.');

        return response()->json(['status' => 'unavailable', 'message' => 'Withdrawals are coming soon. No funds have been reserved or debited.'], 503);
    }
}
