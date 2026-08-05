<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\McpOauthToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        $mcpConnectionsCount = McpOauthToken::where('user_id', $user->id)
            ->where('revoked', false)
            ->where('refresh_expires_at', '>', now())
            ->count();

        return view('profile.edit', [
            'user' => $user,
            'mcpConnectionsCount' => $mcpConnectionsCount,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    public function generateApiToken(Request $request): RedirectResponse
    {
        $user = $request->user();
        $plainToken = Str::random(64);
        $user->api_token_hash = hash('sha256', $plainToken);
        $user->save();

        return Redirect::route('profile.edit')->with('api_token_plain', $plainToken);
    }

    public function revokeApiToken(Request $request): RedirectResponse
    {
        $user = $request->user();
        $user->api_token_hash = null;
        $user->save();

        return Redirect::route('profile.edit')->with('api_token_revoked', true);
    }
}
