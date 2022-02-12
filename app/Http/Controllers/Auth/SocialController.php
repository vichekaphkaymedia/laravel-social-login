<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use Exception;
use Laravel\Socialite\Facades\Socialite;

class SocialController extends Controller
{
    public function redirect($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    public function callback($provider)
    {
        try {
            $social_user = Socialite::driver($provider)->user();
            // First Find Social Account
            $account = SocialAccount::with('user')->where([
                'provider_name' => $provider,
                'provider_id' => $social_user->getId()
            ])->first();

            // If Social Account Exist then Find User and Login
            if ($account) {
                auth()->login($account->user);
                return redirect()->route('home');
            }

            // check user exist or create if user is not exist
            $user = User::firstOrCreate([
                'email' => $social_user->email,
            ], [
                'email' => $social_user->getEmail(),
                'name' => $social_user->getName()
            ]);

            // Create Social Accounts
            $user->socialAccounts()->create([
                'provider_id' => $social_user->getId(),
                'provider_name' => $provider
            ]);

            // Login
            auth()->login($user);
            return redirect()->route('home');
        } catch (Exception $e) {
            return redirect()->route('login');
        }
    }
}
