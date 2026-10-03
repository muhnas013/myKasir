<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\LoginWithPin;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PinLoginController extends Controller
{
    public function show(Request $request)
    {
        $locked = $request->session()->get('locked', false) && Auth::check();

        $users = $locked
            ? User::query()->whereKey(Auth::id())->get()
            : User::query()
                ->whereIn('role', [Role::Cashier, Role::Admin])
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

        return view('auth.login-pin', [
            'users' => $users,
            'locked' => $locked,
        ]);
    }

    public function store(Request $request, LoginWithPin $action): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer'],
            'pin' => ['required', 'digits:6'],
        ]);

        $locked = $request->session()->get('locked', false) && Auth::check();

        if ($locked && (int) $data['user_id'] !== Auth::id()) {
            throw ValidationException::withMessages([
                'pin' => 'Hanya pengguna yang mengunci layar ini yang bisa membukanya.',
            ]);
        }

        if ($locked) {
            $user = Auth::user();

            if (! Hash::check($data['pin'], $user->pin_hash)) {
                throw ValidationException::withMessages(['pin' => 'PIN salah.']);
            }

            $request->session()->forget('locked');

            return redirect()->intended(route('home'));
        }

        $action->handle((int) $data['user_id'], $data['pin']);
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function lock(Request $request): RedirectResponse
    {
        $request->session()->put('locked', true);

        return redirect()->route('login.pin');
    }
}
