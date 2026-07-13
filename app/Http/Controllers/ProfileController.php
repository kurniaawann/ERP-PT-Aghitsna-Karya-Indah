<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function showChangePassword()
    {
        return view('pages.profile.change-password');
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $user = $request->user();

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('profile.change-password')
            ->with('success', 'Password berhasil diubah!');
    }
}
