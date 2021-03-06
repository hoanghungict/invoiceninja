<?php

namespace App\Http\Controllers;

use PragmaRX\Google2FA\Google2FA;
use Crypt;

class TwoFactorController extends Controller
{
    public function setupTwoFactor()
    {
        $user = auth()->user();

        if ($user->google_2fa_secret || ! $user->phone || ! $user->confirmed) {
            return redirect('/settings/user_details');
        }

        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();

        session(['2fa:secret' => $secret]);

        $qrCode = $google2fa->getQRCodeGoogleUrl(
            APP_NAME,
            $user->email,
            $secret
        );

        $data = [
            'secret' => $secret,
            'qrCode' => $qrCode,
        ];

        return view('users.two_factor', $data);
    }

    public function enableTwoFactor()
    {
        $user = auth()->user();
        $secret = session()->pull('2fa:secret');

        if ($secret && ! $user->google_2fa_secret && $user->phone && $user->confirmed) {
            $user->google_2fa_secret = Crypt::encrypt($secret);
            $user->save();

            session()->flash('message', trans('texts.enabled_two_factor'));
        }

        return redirect('settings/user_details');
    }
}
