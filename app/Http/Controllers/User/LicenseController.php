<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class LicenseController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        return view('user.licenses.index', [
            'licenses' => $user->userLicenses()->with('plan')->latest()->paginate(10),
            'activeLicense' => $user->userLicenses()->with('plan')->where('status', 'active')->latest('expiry_date')->first(),
        ]);
    }
}
