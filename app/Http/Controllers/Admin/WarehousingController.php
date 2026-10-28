<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Area;
use App\Enums\GoodsType;
use App\Enums\WarehousingStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WarehousingEnquiry;
use App\Support\Depots;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** The warehouse team's enquiry register. */
class WarehousingController extends Controller
{
    use Concerns;

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', WarehousingEnquiry::class);
        $filters = $this->filters($request);
        $counts = WarehousingEnquiry::query()->filter(array_merge($filters, ['status' => null]))
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.warehousing.index', [
            'enquiries' => WarehousingEnquiry::with('assignee')->filter($filters)
                ->orderByRaw("case when status = 'new' then 0 else 1 end")->orderByDesc('id')->paginate(25)->withQueryString(),
            'filters' => $filters,
            'counts' => $counts,
        ]);
    }

    public function show(WarehousingEnquiry $enquiry): View
    {
        Gate::authorize('view', $enquiry);

        return view('admin.warehousing.show', [
            'enquiry' => $enquiry->load(['assignee', 'notes.user']),
            'staff' => User::working(Area::Warehousing)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, WarehousingEnquiry $enquiry): RedirectResponse
    {
        Gate::authorize('update', $enquiry);
        $user = $this->user($request);
        $data = $request->validate([
            'status' => ['sometimes', Rule::enum(WarehousingStatus::class)],
            'depot' => ['sometimes', 'nullable', Rule::in(Depots::keys())],
            'assigned_to' => ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id')->where('active', true)],
        ]);
        $assignTo = isset($data['assigned_to']) ? (int) $data['assigned_to'] : null;
        $assignee = $assignTo ? User::find($assignTo) : null;
        if ($assignee && ! $assignee->works(Area::Warehousing)) {
            return back()->withErrors(['assigned_to' => "{$assignee->name} can't see warehousing enquiries."]);
        }
        if (isset($data['status']) && $enquiry->claimStatus($status = WarehousingStatus::from($data['status']))) {
            $enquiry->log('Status changed to '.$status->label().'.', 'status', $user);
        }
        if (array_key_exists('depot', $data) && $data['depot'] !== $enquiry->depot) {
            $enquiry->depot = $data['depot'];
            $enquiry->log('Depot set to '.$enquiry->depotName().'.', 'assign', $user);
        }
        if (array_key_exists('assigned_to', $data) && $enquiry->assigned_to !== $assignTo) {
            $enquiry->assigned_to = $assignee?->id;
            $enquiry->log($assignee ? "Assigned to {$assignee->name}." : 'Unassigned.', 'assign', $user);
        }
        $enquiry->save();

        return back()->with('status', "{$enquiry->reference} saved.");
    }

    public function note(Request $request, WarehousingEnquiry $enquiry): RedirectResponse
    {
        Gate::authorize('update', $enquiry);
        $data = $request->validate(['body' => ['required', 'string', 'max:3000']]);
        $enquiry->log($data['body'], 'note', $this->user($request));

        return back()->with('status', 'Note added.')->withFragment('timeline');
    }

    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('viewAny', WarehousingEnquiry::class);
        $query = WarehousingEnquiry::with('assignee')->filter($this->filters($request));

        return $this->csv('allinson-warehousing', ['Reference', 'Received', 'Status', 'Company', 'Pallet spaces', 'Duration', 'Start', 'Goods', 'Hazardous', 'Handling', 'Depot', 'Contact', 'Email', 'Phone', 'Assigned'],
            $query->lazyByIdDesc(500)->map(fn (WarehousingEnquiry $e) => [
                $e->reference, $e->created_at->format('Y-m-d'), $e->status->label(), $e->company, $e->pallet_spaces, $e->duration->label(), $e->start_on?->format('Y-m-d'),
                $e->goods_type->label(), $e->hazardous ? 'yes' : 'no', implode('; ', $e->handlingLabels()), $e->depotName(), $e->contact_name, $e->email, format_phone($e->phone), $e->assignee->name ?? '',
            ]));
    }

    /** @return array<string, string|null> */
    private function filters(Request $request): array
    {
        $depot = input_string($request->query('depot'));

        return [
            'status' => WarehousingStatus::tryFrom(input_string($request->query('status')))?->value,
            'goods' => GoodsType::tryFrom(input_string($request->query('goods')))?->value,
            'depot' => Depots::exists($depot) ? $depot : null,
            'waiting' => $request->boolean('waiting') ? '1' : null,
            'q' => input_string($request->query('q')) ?: null,
        ];
    }
}
