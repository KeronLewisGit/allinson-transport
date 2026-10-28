<x-layouts.admin :title="$staff->name">
    <x-slot:actions>
        <a href="{{ route('admin.staff.index') }}" class="a-btn a-btn-ghost"><x-icon name="arrow-left" class="h-4 w-4" />Staff compliance</a>
        <a href="{{ route('admin.staff.edit', $staff) }}" class="a-btn a-btn-primary"><x-icon name="settings" class="h-4 w-4" />Edit dates</a>
    </x-slot:actions>
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]">
        <div class="min-w-0 space-y-6">
            @if ($staff->drives())
                <section class="a-card grid overflow-hidden sm:grid-cols-3" aria-label="Card expiry dates">
                    @foreach (\App\Models\StaffMember::CARDS as $card => [, , $label])
                        @php($days = $staff->daysLeft($card))
                        <div class="border-b border-line p-5 sm:border-b-0 sm:border-r last:border-0">
                            <p class="text-xs font-bold uppercase tracking-wider text-ink-3">{{ $label }}</p>
                            <p @class(['font-display mt-1 text-4xl font-extrabold num', 'text-[color:var(--critical-text)]' => $days !== null && $days < 0])>{{ $days === null ? '—' : ($days < 0 ? 'Expired' : $days.' days') }}</p>
                            <p class="mt-1"><x-card-date :date="$staff->expiry($card)" :state="$staff->state($card)" /></p>
                        </div>
                    @endforeach
                </section>
            @endif
            <section class="a-card p-5 md:p-6">
                <dl class="dl-grid">
                    <dt>Role</dt><dd>{{ $staff->role->label() }}</dd>
                    <dt>Depot</dt><dd>{{ $staff->depotName() }}</dd>
                    <dt>Employee number</dt><dd>{{ $staff->employee_number ?? '—' }}</dd>
                    <dt>Licence categories</dt><dd>{{ collect($staff->licence_categories ?? [])->map(fn ($c) => ga('careers.licence_categories.'.$c, $c))->implode(', ') ?: '—' }}</dd>
                    <dt>Cards last checked</dt><dd>{{ $staff->cards_checked_on?->format('j M Y') ?? 'Not recorded' }}</dd>
                    <dt>Phone</dt><dd>@if($staff->phone)<a class="text-link" href="{{ tel_href($staff->phone) }}">{{ format_phone($staff->phone) }}</a>@else — @endif</dd>
                    <dt>Email</dt><dd>{{ $staff->email ?? '—' }}</dd>
                    <dt>Status</dt><dd>{{ $staff->active ? 'Active' : 'Left' }}</dd>
                    @if ($staff->application)<dt>Hired from</dt><dd><a class="text-link" href="{{ route('admin.applications.show', $staff->application) }}">{{ $staff->application->reference }}</a></dd>@endif
                </dl>
            </section>
        </div>
        @include('admin.partials.timeline', ['notes' => $staff->notes, 'action' => route('admin.staff.note', $staff)])
    </div>
</x-layouts.admin>
