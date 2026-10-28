<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWarehousingRequest;
use App\Models\WarehousingEnquiry;
use App\Support\Intake;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WarehousingController extends Controller
{
    public function create(): View
    {
        return view('pages.warehousing');
    }

    public function store(StoreWarehousingRequest $request): RedirectResponse
    {
        if (Intake::isBot($request, WarehousingEnquiry::fakeReference(), 'Warehousing enquiry')) {
            return to_route('thanks');
        }

        $enquiry = WarehousingEnquiry::create($request->validated() + ['source_url' => Intake::source($request)]);
        Intake::complete($enquiry->refresh(), $request);

        return to_route('thanks');
    }
}
