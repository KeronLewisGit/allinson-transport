@php
    $roles = \App\Enums\JobRole::cases();
    $drives = collect($roles)->filter->drives()->map->value->values()->all();
    $role = input_string(old('role', $selected?->role->value ?? request('role'))) ?: \App\Enums\JobRole::CeDriver->value;
@endphp
<x-layouts.site :title="$selected ? 'Apply: '.$selected->title : 'Apply for a job'" description="Apply online to George Allinson (Transport) Ltd for driving, warehouse and transport office jobs. No Word form to print or post.">
    <x-page-header photo="tacho" kicker="Careers" :title="$selected ? 'Apply: '.$selected->title : 'Apply for a job'" lead="About five minutes. Have your driving licence, Driver CPC card and tacho card to hand: we ask for the expiry dates, not copies." :label="['Form' => 'Online', 'Post' => 'Not needed', '*Contents' => 'Job application']" />

    <section class="wrap mt-12 grid gap-12 md:mt-16 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <form method="POST" action="{{ route('careers.store') }}" enctype="multipart/form-data" class="min-w-0 space-y-10" x-data="applyForm(@js($role), @js($drives), @js((int) old('licence_points', 0)))" @submit="submit">
            @csrf
            <x-form-errors />
            <x-honeypot />

            <x-fieldset n="1" title="The job">
                @if ($selected)
                    <input type="hidden" name="job_posting_id" value="{{ $selected->id }}">
                    <div class="panel flex flex-wrap items-center justify-between gap-3 p-5">
                        <div><p class="font-bold">{{ $selected->title }}</p><p class="text-ink-2">{{ $selected->role->label() }} · {{ $selected->depotName() }}</p></div>
                        <a href="{{ route('careers.apply') }}" class="inline-flex min-h-11 items-center font-semibold text-link">Apply for something else</a>
                    </div>
                    <x-error for="job_posting_id" />
                @else
                    <fieldset>
                        <legend class="label">Role</legend>
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach ($roles as $r)
                                <label class="choice"><input type="radio" name="role" value="{{ $r->value }}" x-model="role" @checked($role === $r->value) required><span>{{ $r->label() }}<small>{{ $r->blurb() }}</small></span></label>
                            @endforeach
                        </div>
                        <x-error for="role" />
                    </fieldset>
                    <x-select name="depot" label="Depot you would like to work from" :options="\App\Support\Depots::options()" :required="true" :value="request('depot')" />
                @endif
                <x-radios name="experience" label="Experience in this kind of role" :options="\App\Models\JobApplication::EXPERIENCE" :required="true" cols="sm:grid-cols-3" />
            </x-fieldset>

            <x-fieldset n="2" title="About you">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field name="first_name" label="First name" :required="true" maxlength="80" autocomplete="given-name" />
                    <x-field name="last_name" label="Last name" :required="true" maxlength="80" autocomplete="family-name" />
                    <x-field name="phone" label="Mobile" type="tel" :required="true" maxlength="30" autocomplete="tel" inputmode="tel" />
                    <x-field name="email" label="Email" type="email" maxlength="255" autocomplete="email" hint="Needed for transport office roles, and so we can send you an upload link." />
                    <x-field name="postcode" label="Home postcode" :optional="true" maxlength="10" autocomplete="postal-code" class="input uppercase" />
                    <x-field name="available_from" label="Could start from" type="date" :optional="true" :min="today()->format('Y-m-d')" />
                </div>
            </x-fieldset>

            <div x-show="driving" x-cloak>
                <x-fieldset n="3" title="Licence and cards" intro="Copy the dates from your cards. We check the cards themselves before any offer.">
                    <fieldset>
                        <legend class="label">Categories on your licence</legend>
                        @php($chosen = (array) old('licence_categories', []))
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-5">
                            @foreach (ga('careers.licence_categories') as $v => $t)
                                <label class="choice"><input type="checkbox" name="licence_categories[]" value="{{ $v }}" @checked(in_array($v, $chosen, true))><span>{{ $t }}</span></label>
                            @endforeach
                        </div>
                        <x-error for="licence_categories" />
                    </fieldset>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-field name="cpc_expires_on" label="Driver CPC card (DQC) expires" type="date" hint="On the front of your DQC card." />
                        <x-field name="tacho_card_expires_on" label="Digital tacho driver card expires" type="date" hint="Field 4b on the card." />
                        <x-field name="licence_points" label="Current penalty points" type="number" min="0" max="12" inputmode="numeric" x-model.number="points" hint="0 if none." />
                        <div x-show="points > 0" x-cloak><x-field name="endorsements" label="Endorsement codes" maxlength="200" hint="For example: SP30 (2024)." /></div>
                    </div>
                    <div x-show="role === 'hiab_driver'" x-cloak><x-field name="hiab_qualification" label="Lorry loader (Hiab) qualification" maxlength="160" hint="Scheme and expiry, for example a lorry loader card valid to 2028." /></div>
                </x-fieldset>
            </div>

            <x-fieldset n="4" title="Anything else">
                <x-checks name="forklift" label="Forklift and plant licences" :options="ga('careers.forklift')" :optional="true" cols="sm:grid-cols-3" />
                <x-field name="message" label="Tell us about yourself" type="textarea" :optional="true" maxlength="2000" rows="4" hint="Previous employers, types of work, routes you know." />
                <div>
                    <label for="f-cv" class="label">CV <span class="opt" x-text="role === 'transport_office' ? '(required, PDF or Word, 5 MB max)' : '(optional, PDF or Word, 5 MB max)'">(PDF or Word, 5 MB max)</span></label>
                    <input id="f-cv" name="cv" type="file" class="input" accept=".pdf,.doc,.docx">
                    <x-held-upload form="application" field="cv" />
                    <x-error for="cv" />
                </div>
                <x-flag name="right_to_work" label="I have the right to work in the UK" hint="We check this before any offer, as the law requires." />
                <x-flag name="consent" label="Keep my application on file" :hint="'We hold it for '.ga('careers.retention_months').' months after the vacancy closes, then delete it. See the privacy notice.'" />
            </x-fieldset>

            <div class="border-t-[1.5px] border-ink pt-6">
                <x-submit label="Send application" />
                <p class="hint mt-3"><a class="text-link" href="{{ route('privacy') }}">Privacy notice</a>, including how we use driving licence and endorsement information.</p>
            </div>
        </form>
        <aside class="space-y-6 lg:sticky lg:top-28 lg:self-start">
            <x-consignment head="What happens next" :barcode="false" :cols="1" :fields="[
                '1 · Call' => 'HR phones you back',
                '2 · Documents' => 'A secure upload link for your cards',
                '3 · Drive' => 'Assessment drive at the depot',
            ]" />
            <p class="text-ink-2">Prefer to talk first? Call {{ format_phone(ga('phone')) }}.</p>
        </aside>
    </section>
</x-layouts.site>
