<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\Area;
use App\Enums\EnquiryStatus;
use App\Enums\QuoteStatus;
use App\Enums\WarehousingStatus;
use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\Quote;
use App\Models\StaffMember;
use App\Models\User;
use App\Models\WarehousingEnquiry;
use App\Support\Notify;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Leads with the one number the signed-in role must act on: quote requests waiting for a
 * price (traffic, admin), warehousing enquiries nobody has called back (warehouse), or
 * drivers whose CPC, tacho card or licence has expired or expires within 60 days (HR).
 * Tiles for the role's other areas follow.
 */
class DashboardController extends Controller
{
    use Concerns;

    public function __invoke(Request $request): View
    {
        $user = $this->user($request);
        $headline = match (true) {
            $user->works(Area::Quotes) => $this->quoteHeadline($user),
            $user->works(Area::Warehousing) => $this->warehousingHeadline(),
            default => $this->complianceHeadline(),
        };

        $tiles = [];
        if ($user->works(Area::Quotes)) {
            $tiles[] = ['label' => 'Requests, last 7 days', 'value' => Quote::where('created_at', '>=', now()->subDays(7))->count(),
                'previous' => Quote::whereBetween('created_at', [now()->subDays(14), now()->subDays(7)])->count(), 'period' => 'vs previous 7 days', 'href' => route('admin.quotes.index', ['depot' => ''])];
            $sent = Quote::whereNotNull('quoted_at')->where('quoted_at', '>=', now()->subDays(30))->pluck('status');
            $tiles[] = ['label' => 'Quotes sent, last 30 days', 'value' => $sent->count(), 'note' => $sent->filter(fn ($s) => $s === QuoteStatus::Accepted)->count().' accepted', 'href' => route('admin.quotes.index', ['status' => 'accepted', 'depot' => ''])];
            $tiles[] = ['label' => 'Awaiting the customer', 'value' => Quote::where('status', QuoteStatus::Quoted)->count(), 'note' => 'quote sent, no answer yet', 'href' => route('admin.quotes.index', ['status' => 'quoted', 'depot' => ''])];
        }
        if ($user->works(Area::Warehousing)) {
            $tiles[] = ['label' => 'Warehousing enquiries open', 'value' => WarehousingEnquiry::whereIn('status', [WarehousingStatus::New, WarehousingStatus::Contacted, WarehousingStatus::Proposal])->count(), 'note' => 'new, contacted or with a proposal', 'href' => route('admin.warehousing.index')];
            $tiles[] = ['label' => 'Pallet spaces asked for', 'value' => (int) WarehousingEnquiry::whereIn('status', [WarehousingStatus::New, WarehousingStatus::Contacted, WarehousingStatus::Proposal])->sum('pallet_spaces'), 'note' => 'across open enquiries', 'href' => route('admin.warehousing.index')];
        }
        if ($user->works(Area::Recruitment)) {
            $tiles[] = ['label' => 'Applications waiting', 'value' => JobApplication::waiting()->count(), 'note' => 'new for over '.JobApplication::REVIEW_DAYS.' days', 'href' => route('admin.applications.index', ['waiting' => 1])];
            $tiles[] = ['label' => 'Assessment drives, next 14 days', 'value' => JobApplication::active()->whereBetween('assessment_at', [now(), now()->addDays(14)])->count(), 'note' => 'booked', 'href' => route('admin.applications.index', ['status' => 'assessment'])];
            $tiles[] = ['label' => 'Open vacancies', 'value' => JobPosting::open()->count(), 'note' => 'on the careers page', 'href' => route('admin.jobs.index')];
        }
        if ($user->works(Area::Enquiries)) {
            $tiles[] = ['label' => 'New enquiries', 'value' => Enquiry::where('status', EnquiryStatus::New)->count(), 'note' => 'from the contact page', 'href' => route('admin.enquiries.index', ['status' => 'new'])];
        }

        return view('admin.dashboard', [
            'headline' => $headline,
            'tiles' => $tiles,
            'pipeline' => match (true) {
                $user->works(Area::Quotes) => $this->pipeline('Quote pipeline', 'admin.quotes.index', QuoteStatus::cases(), Quote::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'), ['depot' => '']),
                $user->works(Area::Recruitment) => $this->pipeline('Recruitment pipeline', 'admin.applications.index', ApplicationStatus::cases(), JobApplication::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')),
                default => $this->pipeline('Warehousing pipeline', 'admin.warehousing.index', WarehousingStatus::cases(), WarehousingEnquiry::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')),
            },
            'chart' => match (true) {
                $user->works(Area::Quotes) => $this->weekly('Quote requests by week', Quote::where('created_at', '>=', today()->startOfWeek()->subWeeks(7))->pluck('created_at')),
                $user->works(Area::Warehousing) => $this->weekly('Warehousing enquiries by week', WarehousingEnquiry::where('created_at', '>=', today()->startOfWeek()->subWeeks(7))->pluck('created_at')),
                default => $this->expiryChart(),
            },
            'notifyMissing' => $user->isAdmin() && Notify::recipients() === [],
            'notifyInvalid' => $user->isAdmin() ? Notify::invalid() : [],
        ]);
    }

    /** @return array<string, mixed> */
    private function quoteHeadline(User $user): array
    {
        $depot = $user->depot;
        $open = Quote::open()->when($depot, fn ($q) => $q->where('depot', $depot))->with('assignee')->orderBy('collection_date')->get();
        $overdue = $open->filter(fn (Quote $q) => $q->isOverdue())->count();
        $urgent = $open->filter(fn (Quote $q) => $q->collectsSoon())->count();

        return [
            'count' => $open->count(),
            'title' => ($open->count() === 1 ? 'quote request is' : 'quote requests are').' waiting for a price'.($depot ? ' at '.$user->depotName() : ''),
            'body' => $open->isEmpty()
                ? 'Every request has been priced.'
                : "{$urgent} collect within ".Quote::URGENT_DAYS." days; {$overdue} have waited over ".Quote::RESPONSE_HOURS.' hours.',
            'cta' => ['label' => $open->isEmpty() ? 'Open the quote register' : 'Price them', 'href' => route('admin.quotes.index', ['status' => 'open'])],
            'items' => $open->take(6)->map(fn (Quote $q) => [
                'href' => route('admin.quotes.show', $q), 'title' => $q->route().' · '.$q->service->label(),
                'meta' => trim(($q->company ?: $q->contact_name).' · '.$q->depotName().' · '.($q->assignee->name ?? 'Unassigned')),
                'chip' => $q->collectionLabel(), 'tone' => $q->collectionTone(), 'icon' => 'clock',
            ])->all(),
            'list' => 'Collecting soonest',
        ];
    }

    /** @return array<string, mixed> */
    private function warehousingHeadline(): array
    {
        $new = WarehousingEnquiry::where('status', WarehousingStatus::New)->oldest()->get();

        return [
            'count' => $new->count(),
            'title' => ($new->count() === 1 ? 'warehousing enquiry needs' : 'warehousing enquiries need').' a call back',
            'body' => $new->isEmpty() ? 'Everyone who asked about space has been contacted.' : 'The oldest arrived '.$new->first()->created_at->diffForHumans().'.',
            'cta' => ['label' => 'Open warehousing enquiries', 'href' => route('admin.warehousing.index', ['status' => 'new'])],
            'items' => $new->take(6)->map(fn (WarehousingEnquiry $e) => [
                'href' => route('admin.warehousing.show', $e), 'title' => $e->company.' · '.number_format($e->pallet_spaces).' pallet spaces',
                'meta' => $e->goods_type->label().' · '.$e->duration->label(),
                'chip' => $e->created_at->diffForHumans(['syntax' => CarbonInterface::DIFF_ABSOLUTE, 'short' => true]), 'tone' => $e->isWaiting() ? 'attention' : 'neutral', 'icon' => 'clock',
            ])->all(),
            'list' => 'Waiting longest',
        ];
    }

    /** @return array<string, mixed> */
    private function complianceHeadline(): array
    {
        $due = StaffMember::due()->get()->sortBy(fn (StaffMember $s) => $s->nextExpiry()[1] ?? now()->addYears(50))->values();
        $expired = $due->filter(fn (StaffMember $s) => $s->worstState() === 'expired')->count();

        return [
            'count' => $due->count(),
            'title' => ($due->count() === 1 ? 'driver has' : 'drivers have').' a CPC, tacho card or licence expired or expiring within '.StaffMember::WARNING_DAYS.' days',
            'body' => $due->isEmpty()
                ? 'Every active driver\'s cards are in date for the next '.StaffMember::WARNING_DAYS.' days.'
                : "{$expired} already expired: they must not drive until renewed. HR is emailed at 90, 60, 30 and 14 days, and on expiry.",
            'cta' => ['label' => 'Open staff compliance', 'href' => route('admin.staff.index', ['due' => 1])],
            'items' => $due->take(6)->map(function (StaffMember $s) {
                [$card, $date] = $s->nextExpiry() ?? ['cpc', today()];
                $days = (int) today()->diffInDays($date, false);

                return [
                    'href' => route('admin.staff.show', $s), 'title' => $s->name, 'meta' => StaffMember::CARDS[$card][2].' · '.$s->depotName(),
                    'chip' => $days < 0 ? 'Expired '.$date->format('j M') : ($days === 0 ? 'Expires today' : $days.' days left'),
                    'tone' => $days < 0 ? 'critical' : 'attention', 'icon' => 'id-card',
                ];
            })->all(),
            'list' => 'Expiring soonest',
        ];
    }

    /**
     * @param  list<\BackedEnum&\UnitEnum>  $cases
     * @param  Collection<array-key, mixed>  $counts
     * @param  array<string, string>  $extra
     * @return array{title: string, rows: list<array{status: mixed, count: int, href: string}>}
     */
    private function pipeline(string $title, string $route, array $cases, Collection $counts, array $extra = []): array
    {
        return ['title' => $title, 'rows' => array_map(fn ($s) => ['status' => $s, 'count' => (int) ($counts[$s->value] ?? 0), 'href' => route($route, ['status' => $s->value] + $extra)], $cases)];
    }

    /**
     * @param  Collection<int, mixed>  $dates
     * @return array{title: string, note: string, bars: list<array{label: string, value: int, hint: string}>}
     */
    private function weekly(string $title, Collection $dates): array
    {
        $start = today()->startOfWeek()->subWeeks(7);
        $bars = [];
        for ($i = 0; $i < 8; $i++) {
            $from = $start->copy()->addWeeks($i);
            $to = $from->copy()->addWeek();
            $bars[] = ['label' => $i === 7 ? 'This wk' : $from->format('j M'), 'value' => $dates->filter(fn ($d) => $d >= $from && $d < $to)->count(), 'hint' => 'Week of '.$from->format('j M')];
        }

        return ['title' => $title, 'note' => 'last 8 weeks', 'bars' => $bars];
    }

    /** @return array{title: string, note: string, bars: list<array{label: string, value: int, hint: string}>} */
    private function expiryChart(): array
    {
        $start = today()->startOfMonth();
        $end = $start->copy()->addMonths(12);
        $dates = collect();
        foreach (StaffMember::where('active', true)->get() as $s) {
            foreach ($s->trackedCards() as $card) {
                $d = $s->expiry($card);
                if ($d && $d->between($start, $end)) {
                    $dates->push($d);
                }
            }
        }
        $bars = [];
        for ($i = 0; $i < 12; $i++) {
            $m = $start->copy()->addMonths($i);
            $bars[] = ['label' => $m->format('M'), 'value' => $dates->filter(fn ($d) => $d->isSameMonth($m))->count(), 'hint' => $m->format('F Y')];
        }

        return ['title' => 'Driver card and CPC expiries by month', 'note' => 'active drivers, next 12 months', 'bars' => $bars];
    }
}
