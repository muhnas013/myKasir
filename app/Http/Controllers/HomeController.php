<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function index(): RedirectResponse
    {
        $user = Auth::user();

        if ($user->isOwner()) {
            return redirect()->route('settings.index');
        }

        return redirect()->route('pos.index');
    }
}
