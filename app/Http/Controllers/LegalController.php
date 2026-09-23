<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LegalController extends Controller
{
    protected function getCompanyProps(): array
    {
        return [
            'company' => config('company'),
        ];
    }

    public function terms(): Response
    {
        return Inertia::render('Legal/Terms', $this->getCompanyProps());
    }

    public function privacy(): Response
    {
        return Inertia::render('Legal/Privacy', $this->getCompanyProps());
    }

    public function sweepsRules(): Response
    {
        return Inertia::render('Legal/SweepsRules', $this->getCompanyProps());
    }

    public function responsibleGaming(): Response
    {
        return Inertia::render('Legal/ResponsibleGaming', $this->getCompanyProps());
    }

    public function about(): Response
    {
        return Inertia::render('Legal/About', $this->getCompanyProps());
    }

    public function faq(): Response
    {
        return Inertia::render('Legal/Faq', $this->getCompanyProps());
    }

    public function contact(): Response
    {
        return Inertia::render('Legal/Contact', $this->getCompanyProps());
    }

    public function submitContact(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150',
            'category' => 'required|string|max:50',
            'subject' => 'required|string|max:150',
            'message' => 'required|string|max:2000',
        ]);

        return back()->with('success', 'Thank you! Your message has been received by our 24/7 VIP Support Team. A ticket confirmation has been sent to '.$validated['email']);
    }
}
