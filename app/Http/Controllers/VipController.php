<?php

namespace App\Http\Controllers;

use App\Services\VipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VipController extends Controller
{
    public function index(Request $request, VipService $service): Response
    {
        return Inertia::render('VipClub', $service->dashboard($request->user()));
    }

    public function checkin(Request $request, VipService $service): RedirectResponse
    {
        $service->checkin($request->user());

        return back()->with('success', 'Your daily check-in is recorded.');
    }

    public function claim(Request $request, int $reward, VipService $service): RedirectResponse
    {
        $service->claim($request->user(), $reward);

        return back()->with('success', 'Your VIP reward is in your EUR wallet.');
    }
}
