<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password. Sends email with optional location and device_id.
     */
    public function update(Request $request, NotificationService $notificationService): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
            'location' => ['nullable', 'string', 'max:500'],
            'device_id' => ['nullable', 'string', 'max:255'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $notificationService->sendPasswordChangedEmail(
            $request->user(),
            $validated['location'] ?? null,
            $validated['device_id'] ?? null
        );

        return back();
    }
}
