<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProfilePreferencesController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'account_emails_enabled' => ['required', 'boolean'],
            'marketing_emails_enabled' => ['required', 'boolean'],
            'messenger_contact_enabled' => ['required', 'boolean'],
        ]);
        $user = $request->user();

        if ($validated['messenger_contact_enabled'] && (! $user->contact_channel || ! $user->contact_handle)) {
            throw ValidationException::withMessages(['messenger_contact_enabled' => 'Add a valid messenger contact before enabling this preference.']);
        }

        $user->forceFill([
            'account_emails_enabled' => $validated['account_emails_enabled'],
            'marketing_email_consent_at' => $validated['marketing_emails_enabled'] ? ($user->marketing_email_consent_at ?? now()) : null,
            'contact_consent_at' => $validated['messenger_contact_enabled'] ? ($user->contact_consent_at ?? now()) : null,
        ])->save();

        return back()->with('success', 'Communication preferences saved.');
    }
}
