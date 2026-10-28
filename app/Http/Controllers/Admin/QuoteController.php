<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Area;
use App\Enums\QuoteService;
use App\Enums\QuoteStatus;
use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Models\QuoteFile;
use App\Models\User;
use App\Notifications\QuoteSent;
use App\Support\Depots;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Traffic's quote register: requests by depot and collection date, priced and sent from here. */
class QuoteController extends Controller
{
    use Concerns;

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Quote::class);
        $filters = $this->filters($request);
        $counts = Quote::query()->filter(array_merge($filters, ['status' => null]))
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        // Waiting for a price first, soonest collection first; the rest newest first underneath.
        $quotes = Quote::with('assignee')->withCount('files')->filter($filters)
            ->orderByRaw("case when status in ('new', 'pricing') then 0 else 1 end")
            ->orderByRaw("case when status in ('new', 'pricing') then collection_date end asc")
            ->orderByDesc('id')
            ->paginate(25)->withQueryString();

        return view('admin.quotes.index', [
            'quotes' => $quotes,
            'filters' => $filters,
            'counts' => $counts,
            'openCount' => Quote::open()->when($filters['depot'], fn ($q, $d) => $q->where('depot', $d))->count(),
            'staff' => User::working(Area::Quotes)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Quote $quote): View
    {
        Gate::authorize('view', $quote);

        return view('admin.quotes.show', [
            'quote' => $quote->load(['assignee', 'files', 'notes.user']),
            'staff' => User::working(Area::Quotes)->orderBy('name')->get(['id', 'name', 'depot']),
        ]);
    }

    public function update(Request $request, Quote $quote): RedirectResponse
    {
        Gate::authorize('update', $quote);
        $user = $this->user($request);
        $data = $request->validate([
            'status' => ['sometimes', Rule::enum(QuoteStatus::class)],
            'depot' => ['sometimes', Rule::in(Depots::keys())],
            'assigned_to' => ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id')->where('active', true)],
        ]);

        $assignTo = isset($data['assigned_to']) ? (int) $data['assigned_to'] : null;
        $reassign = array_key_exists('assigned_to', $data) && $quote->assigned_to !== $assignTo;
        $assignee = $reassign && $assignTo ? User::find($assignTo) : null;
        if ($assignee && ! $assignee->works(Area::Quotes)) {
            return back()->withErrors(['assigned_to' => "{$assignee->name} works in {$assignee->role->label()} and can't see quotes."]);
        }

        if (isset($data['status']) && $quote->claimStatus($status = QuoteStatus::from($data['status']))) {
            $quote->log('Status changed to '.$status->label().'.', 'status', $user);
        }
        if (isset($data['depot']) && $data['depot'] !== $quote->depot) {
            $from = $quote->depotName();
            $quote->depot = $data['depot'];
            $quote->log("Moved from {$from} to the {$quote->depotName()} depot.", 'assign', $user);
        }
        if ($reassign) {
            $quote->assigned_to = $assignee?->id;
            $quote->log($assignee ? "Assigned to {$assignee->name}." : 'Unassigned.', 'assign', $user);
        }
        $quote->save();

        return back()->with('status', "{$quote->reference} saved.");
    }

    /** Save the price and email it to the customer with their signed accept / decline link. */
    public function price(Request $request, Quote $quote): RedirectResponse
    {
        Gate::authorize('update', $quote);
        $data = $request->validate([
            'price' => ['required', 'numeric', 'min:1', 'max:1000000'],
            'valid_until' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'price_notes' => ['nullable', 'string', 'max:2000'],
        ]);
        if (in_array($quote->status, [QuoteStatus::Accepted, QuoteStatus::NotTaken, QuoteStatus::Declined], true)) {
            return back()->withErrors(['price' => 'This request is closed ('.$quote->status->label().'). Reopen it before sending a new price.']);
        }
        $user = $this->user($request);
        $wasQuoted = $quote->status === QuoteStatus::Quoted;
        // Claim the status first (claimStatus syncs the model), then store the price.
        if (! $wasQuoted && ! $quote->claimStatus(QuoteStatus::Quoted, ['quoted_at' => now()])) {
            return back()->withErrors(['price' => 'Someone else changed this request a moment ago. Reload and check it.']);
        }
        $quote->forceFill(['price' => $data['price'], 'valid_until' => $data['valid_until'], 'price_notes' => $data['price_notes'] ?? null]);
        $quote->assigned_to ??= $user->id;
        $quote->save();

        Notification::route('mail', [$quote->email])->notify(new QuoteSent($quote, $quote->responseUrl()));
        $quote->log(($wasQuoted ? 'Revised quote' : 'Quote').' of '.gbp($quote->price).' + VAT emailed to '.$quote->email.', valid until '.$quote->valid_until?->format('j M Y').'.', 'status', $user);

        return back()->with('status', 'Quote emailed to '.$quote->email.'.');
    }

    public function note(Request $request, Quote $quote): RedirectResponse
    {
        Gate::authorize('update', $quote);
        $data = $request->validate(['body' => ['required', 'string', 'max:3000']]);
        $quote->log($data['body'], 'note', $this->user($request));

        return back()->with('status', 'Note added.')->withFragment('timeline');
    }

    public function file(Request $request, Quote $quote, QuoteFile $file): StreamedResponse
    {
        Gate::authorize('openFile', [$quote, $file]);
        abort_unless(Storage::disk('local')->exists($file->path), 404);
        $quote->log("Opened {$file->original_name}.", 'file', $this->user($request));
        $name = Str::slug($quote->reference.'-'.pathinfo($file->original_name, PATHINFO_FILENAME)).'.'.pathinfo($file->path, PATHINFO_EXTENSION);

        return Storage::disk('local')->download($file->path, $name, ['Cache-Control' => 'private, no-store']);
    }

    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('viewAny', Quote::class);
        $query = Quote::with('assignee')->filter($this->filters($request));

        return $this->csv('allinson-quotes', ['Reference', 'Received', 'Status', 'Depot', 'Service', 'Collection postcode', 'Collection date', 'Delivery postcode', 'Goods', 'Pallets', 'Weight kg', 'ADR', 'Price GBP ex VAT', 'Company', 'Contact', 'Email', 'Phone', 'Assigned'],
            $query->lazyByIdDesc(500)->map(fn (Quote $q) => [
                $q->reference, $q->created_at->format('Y-m-d H:i'), $q->status->label(), $q->depotName(), $q->service->label(), $q->collection_postcode,
                $q->collection_date->format('Y-m-d'), $q->delivery_postcode, $q->goods, $q->pallets, $q->weight_kg, $q->adr ? 'yes' : 'no',
                $q->price, $q->company, $q->contact_name, $q->email, format_phone($q->phone), $q->assignee->name ?? '',
            ]));
    }

    /** @return array<string, string|null> */
    private function filters(Request $request): array
    {
        $status = input_string($request->query('status'));
        $assigned = input_string($request->query('assigned'));
        $depot = input_string($request->query('depot'));
        if ($depot === '' && ! $request->has('depot')) {
            $depot = (string) $this->user($request)->depot; // traffic staff start on their own depot
        }

        return [
            'status' => $status === 'open' ? 'open' : QuoteStatus::tryFrom($status)?->value,
            'depot' => Depots::exists($depot) ? $depot : null,
            'service' => QuoteService::tryFrom(input_string($request->query('service')))?->value,
            'adr' => $request->boolean('adr') ? '1' : null,
            'assigned' => match (true) {
                $assigned === 'me' => (string) $request->user()?->getAuthIdentifier(),
                $assigned === 'none', ctype_digit($assigned) => $assigned,
                default => null,
            },
            'q' => input_string($request->query('q')) ?: null,
        ];
    }
}
