<?php

namespace App\Http\Controllers;

use App\Enums\QuoteService;
use App\Enums\QuoteStatus;
use App\Http\Requests\StoreQuoteRequest;
use App\Models\Quote;
use App\Notifications\QuoteAnswered;
use App\Support\Intake;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** The public quote request, and the signed page where a customer accepts or declines a price. */
class QuoteController extends Controller
{
    public function create(Request $request): View
    {
        return view('pages.quote', ['service' => QuoteService::tryFrom(input_string($request->query('service')))?->value]);
    }

    public function store(StoreQuoteRequest $request): RedirectResponse
    {
        if (Intake::isBot($request, Quote::fakeReference(), 'Quote request')) {
            $request->releaseHeldUploads();

            return to_route('thanks');
        }

        $quote = Quote::create($request->safe()->except(['files']) + ['source_url' => Intake::source($request)]);
        foreach ((array) $request->file('files', []) as $file) {
            // Random name on the private disk; the original name is shown to staff only.
            $quote->files()->create([
                'path' => $file->store('quotes/'.$quote->id, 'local'),
                'original_name' => mb_substr($file->getClientOriginalName(), 0, 200),
                'mime' => $file->getMimeType(),
                'size' => (int) $file->getSize(),
            ]);
        }
        $request->releaseHeldUploads();
        $quote->refresh();
        $quote->log('Routed to the '.$quote->depotName().' depot by collection postcode '.$quote->collection_postcode.'.');
        Intake::complete($quote, $request);

        return to_route('thanks');
    }

    /** Signed link from the quote email. */
    public function response(string $quote): View
    {
        $quote = Quote::where('public_id', $quote)->firstOrFail();
        abort_if($quote->price === null, 404);

        return view('pages.quote-response', ['quote' => $quote]);
    }

    public function respond(Request $request, string $quote): RedirectResponse
    {
        $quote = Quote::where('public_id', $quote)->firstOrFail();
        $data = $request->validate([
            'decision' => ['required', 'in:accept,decline'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        if (! $quote->canBeAccepted()) {
            return back()->withErrors(['decision' => $quote->status === QuoteStatus::Quoted
                ? 'This quote has expired. Please call '.ga('phone').' and we will price it again.'
                : 'This quote has already been answered. Call '.ga('phone').' if anything has changed.']);
        }

        $accept = $data['decision'] === 'accept';
        $note = input_string($data['note'] ?? '') ?: null;
        if ($quote->claimStatus($accept ? QuoteStatus::Accepted : QuoteStatus::NotTaken, ['responded_at' => now(), 'customer_note' => $note])) {
            $quote->log(($accept ? 'Customer accepted the quote online.' : 'Customer declined the quote online.').($note ? ' Note: '.$note : ''), 'status');
            Notify::office(new QuoteAnswered($quote), $quote->area());
        }

        return back()->with('status', $accept
            ? 'Thank you. Your booking is accepted; our '.$quote->depotName().' traffic office will confirm the collection.'
            : 'Thank you for letting us know.');
    }
}
