@php
    $a = $application;
    $outstanding = $a->outstanding();
    $canHire = auth()->user()->can('hire', $a);
@endphp
<x-layouts.admin :title="$a->full_name">
    <x-slot:actions>
        <a href="{{ route('admin.applications.index') }}" class="a-btn a-btn-ghost"><x-icon name="arrow-left" class="h-4 w-4" />Applications</a>
        @if ($a->email)<a href="mailto:{{ $a->email }}" class="a-btn a-btn-ghost"><x-icon name="mail" class="h-4 w-4" />Email</a>@endif
        <a href="{{ tel_href($a->phone) }}" class="a-btn a-btn-primary"><x-icon name="phone" class="h-4 w-4" />Call</a>
    </x-slot:actions>

    {{-- Pipeline: what is done and what is still outstanding before an offer. --}}
    <ol class="a-card mb-6 grid min-w-0 sm:grid-cols-4">
        @foreach ([
            ['Right to work', (bool) $a->rtw_checked_at, $a->rtw_checked_at ? $a->rtw_checked_at->format('j M').' · '.(\App\Models\JobApplication::RTW_METHODS[$a->rtw_method] ?? '') : 'Not checked'],
            ['References', $a->references_status === \App\Enums\ReferenceStatus::Received, $a->references_status->label()],
            ['Assessment drive', ! $a->drives() || $a->assessment_result === \App\Enums\AssessmentResult::Passed, $a->drives() ? ($a->assessment_result?->label() ?? ($a->assessment_at ? 'Booked '.$a->assessment_at->format('D j M, H:i') : 'Not booked')) : 'Not needed'],
            ['Documents', $a->currentDocuments->isNotEmpty(), $a->currentDocuments->isNotEmpty() ? $a->currentDocuments->count().' uploaded' : ($a->documents_requested_at ? 'Link sent '.$a->documents_requested_at->format('j M') : 'Not requested')],
        ] as [$label, $done, $note])
            <li class="flex items-start gap-3 border-b border-line p-4 sm:border-b-0 sm:border-r last:border-0">
                <x-icon :name="$done ? 'check-circle' : 'clock'" @class(['mt-0.5 h-5 w-5 flex-none', 'text-[color:var(--good)]' => $done, 'text-ink-3' => ! $done]) />
                <div class="min-w-0"><p class="font-semibold">{{ $label }}</p><p class="truncate text-sm text-ink-3">{{ $note }}</p></div>
            </li>
        @endforeach
    </ol>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]">
        <div class="min-w-0 space-y-6">
            <section class="a-card p-5 md:p-6">
                <div class="flex flex-wrap items-center gap-2"><x-status :status="$a->status" />@if($a->staffMember)<a href="{{ route('admin.staff.show', $a->staffMember) }}" class="chip" data-tone="good"><x-icon name="id-card" />In staff compliance</a>@endif</div>
                <h2 class="mt-3 text-xl font-bold">{{ $a->position() }}</h2>
                <dl class="dl-grid mt-5">
                    <dt>Reference</dt><dd class="num">{{ $a->reference }}</dd>
                    <dt>Role</dt><dd>{{ $a->role->label() }}</dd>
                    <dt>Depot</dt><dd>{{ $a->depotName() }}</dd>
                    <dt>Experience</dt><dd>{{ \App\Models\JobApplication::EXPERIENCE[$a->experience] ?? $a->experience }}</dd>
                    <dt>Phone</dt><dd><a class="text-link underline" href="{{ tel_href($a->phone) }}">{{ format_phone($a->phone) }}</a></dd>
                    <dt>Email</dt><dd>{{ $a->email ?? '—' }}</dd>
                    <dt>Home postcode</dt><dd>{{ $a->postcode ?? '—' }}</dd>
                    <dt>Available from</dt><dd>{{ $a->available_from?->format('j F Y') ?? 'Not given' }}</dd>
                    <dt>Right to work</dt><dd>{{ $a->right_to_work ? 'Declared' : 'Not declared' }}</dd>
                    <dt>Received</dt><dd>{{ $a->created_at->format('j M Y, H:i') }}</dd>
                </dl>
            </section>

            @if ($a->drives())
                <section class="a-card p-5 md:p-6" aria-labelledby="licence-title">
                    <h2 id="licence-title" class="font-semibold">Licence and cards <span class="text-sm font-normal text-ink-3">(self-declared; check the cards)</span></h2>
                    <dl class="dl-grid mt-4">
                        <dt>Categories</dt><dd>{{ $a->licenceList() ?: '—' }}</dd>
                        <dt>Driver CPC (DQC)</dt><dd><x-card-date :date="$a->cpc_expires_on" /></dd>
                        <dt>Tacho driver card</dt><dd><x-card-date :date="$a->tacho_card_expires_on" /></dd>
                        <dt>Penalty points</dt><dd>{{ $a->licence_points ?? '—' }}{{ $a->endorsements ? ' · '.$a->endorsements : '' }}</dd>
                        @if ($a->hiab_qualification)<dt>Hiab qualification</dt><dd>{{ $a->hiab_qualification }}</dd>@endif
                    </dl>
                </section>
            @endif

            @if ($a->forklift || $a->message || $a->cv_path)
                <section class="a-card p-5 md:p-6">
                    @if ($a->forklift)<h3 class="font-semibold">Forklift and plant</h3><p class="mt-1 text-ink-2">{{ collect($a->forklift)->map(fn ($k) => ga('careers.forklift.'.$k, $k))->implode(', ') }}</p>@endif
                    @if ($a->message)<h3 class="mt-5 font-semibold first:mt-0">About them</h3><p class="measure mt-1 whitespace-pre-line text-ink-2">{{ $a->message }}</p>@endif
                    @if ($a->cv_path)
                        <div class="mt-5 border-t border-line pt-4">
                            <a href="{{ route('admin.applications.cv', $a) }}" class="a-btn a-btn-ghost"><x-icon name="download" class="h-4 w-4" />Download CV ({{ file_size($a->cv_size) }})</a>
                        </div>
                    @endif
                </section>
            @endif

            <section class="a-card p-5 md:p-6" aria-labelledby="docs-title">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 id="docs-title" class="font-semibold">Documents from the applicant</h2>
                    @if ($a->status->isActive() && $a->email)
                        <form method="POST" action="{{ route('admin.applications.request-documents', $a) }}">@csrf<button class="a-btn a-btn-ghost"><x-icon name="send" class="h-4 w-4" />{{ $a->documents_requested_at ? 'Send the link again' : 'Email an upload link' }}</button></form>
                    @endif
                </div>
                @if ($a->currentDocuments->isEmpty())
                    <p class="mt-3 text-ink-3">{{ $a->documents_requested_at ? 'Link sent '.$a->documents_requested_at->format('j M Y').'; nothing uploaded yet.' : 'Nothing requested yet. The link lets them upload licence, card and right-to-work copies from a phone.' }}</p>
                @else
                    <ul class="mt-3 divide-y divide-line">
                        @foreach ($a->currentDocuments as $d)
                            <li class="flex flex-wrap items-center justify-between gap-2 py-2"><span>{{ $d->kind->label() }} <span class="text-sm text-ink-3">{{ $d->created_at->format('j M') }} · {{ file_size($d->size) }}</span></span><a href="{{ route('admin.applications.document', [$a, $d]) }}" class="inline-flex min-h-11 items-center gap-1.5 text-link"><x-icon name="download" class="h-4 w-4" />Open</a></li>
                        @endforeach
                    </ul>
                @endif
                <p class="mt-3 text-xs text-ink-3"><x-icon name="lock" class="inline h-3.5 w-3.5" /> HR and administrators only; each download is logged.</p>
            </section>
        </div>

        <div class="min-w-0 space-y-6">
            <section class="a-card p-5">
                <h2 class="font-semibold">Status</h2>
                <form method="POST" action="{{ route('admin.applications.update', $a) }}" class="mt-3 flex flex-wrap gap-2">
                    @csrf @method('PATCH')
                    <label for="a-status" class="sr-only">Status</label>
                    <select id="a-status" name="status" class="a-input min-w-0 flex-1">@foreach (\App\Enums\ApplicationStatus::cases() as $s)@continue($s === \App\Enums\ApplicationStatus::Hired && $a->status !== $s)<option value="{{ $s->value }}" @selected($a->status === $s)>{{ $s->label() }}</option>@endforeach</select>
                    <button class="a-btn a-btn-primary">Save</button>
                </form>
            </section>

            <section class="a-card p-5" aria-labelledby="checks-title">
                <h2 id="checks-title" class="font-semibold">Checks</h2>
                <form method="POST" action="{{ route('admin.applications.checks', $a) }}" class="mt-3 space-y-3">
                    @csrf @method('PATCH')
                    <div><label for="c-rtw" class="mb-1 block text-sm text-ink-2">Right-to-work check</label>
                        @if ($a->rtw_checked_at)
                            <p id="c-rtw" class="text-sm"><x-icon name="check-circle" class="inline h-4 w-4 text-[color:var(--good)]" /> {{ \App\Models\JobApplication::RTW_METHODS[$a->rtw_method] ?? $a->rtw_method }}, {{ $a->rtw_checked_at->format('j M Y') }} by {{ $a->rtwChecker->name ?? 'staff' }}</p>
                        @else
                            <select id="c-rtw" name="rtw_method" class="a-input w-full"><option value="">Not checked yet</option>@foreach (\App\Models\JobApplication::RTW_METHODS as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
                        @endif
                    </div>
                    <div><label for="c-refs" class="mb-1 block text-sm text-ink-2">References</label><select id="c-refs" name="references_status" class="a-input w-full">@foreach (\App\Enums\ReferenceStatus::cases() as $r)<option value="{{ $r->value }}" @selected($a->references_status === $r)>{{ $r->label() }}</option>@endforeach</select></div>
                    @if ($a->drives())
                        <div><label for="c-at" class="mb-1 block text-sm text-ink-2">Assessment drive at {{ $a->depotName() }}</label><input id="c-at" name="assessment_at" type="datetime-local" class="a-input w-full" value="{{ $a->assessment_at?->format('Y-m-d\TH:i') }}"></div>
                        <div><label for="c-res" class="mb-1 block text-sm text-ink-2">Assessment result</label><select id="c-res" name="assessment_result" class="a-input w-full"><option value="">Not yet</option>@foreach (\App\Enums\AssessmentResult::cases() as $r)<option value="{{ $r->value }}" @selected($a->assessment_result === $r)>{{ $r->label() }}</option>@endforeach</select></div>
                    @endif
                    <button class="a-btn a-btn-primary">Save checks</button>
                </form>
            </section>

            @if ($canHire && $a->status !== \App\Enums\ApplicationStatus::Hired && $a->status->isActive())
                <section class="a-card p-5" aria-labelledby="hire-title">
                    <h2 id="hire-title" class="font-semibold">Hire</h2>
                    @if ($outstanding)
                        <p class="mt-2 text-sm text-ink-2">Still outstanding: {{ implode(', ', $outstanding) }}.</p>
                    @else
                        <form method="POST" action="{{ route('admin.applications.hire', $a) }}" class="mt-3 space-y-3">
                            @csrf
                            <div><label for="h-num" class="mb-1 block text-sm text-ink-2">Employee number <span class="text-ink-3">(optional)</span></label><input id="h-num" name="employee_number" class="a-input w-full" maxlength="30"></div>
                            @if ($a->drives())<div><label for="h-lic" class="mb-1 block text-sm text-ink-2">Licence photocard expires <span class="text-ink-3">(optional)</span></label><input id="h-lic" name="licence_expires_on" type="date" class="a-input w-full"></div>@endif
                            <button class="a-btn a-btn-primary"><x-icon name="id-card" class="h-4 w-4" />Hire and start compliance tracking</button>
                        </form>
                    @endif
                </section>
            @endif

            @include('admin.partials.timeline', ['notes' => $a->notes, 'action' => route('admin.applications.note', $a)])
        </div>
    </div>
</x-layouts.admin>
