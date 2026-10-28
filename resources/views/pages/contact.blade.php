@php($topic = \App\Enums\EnquiryTopic::tryFrom(input_string(request('topic')))?->value)
<x-layouts.site title="Contact" description="Contact George Allinson (Transport) Ltd, Faverdale North, Darlington DL3 0PH: telephone 01325 461241, email and an enquiry form.">
    <x-page-header photo="own-depot-night" position="center 60%" kicker="Contact" title="Talk to the traffic office" lead="For a price, the quote form reaches the right depot fastest. For anything else, call, email or send a message below." :label="['Telephone' => format_phone(ga('phone')), 'Postcode' => ga('address.postcode'), '*Head office' => 'Faverdale North, Darlington']" />

    <section class="wrap mt-12 grid gap-12 md:mt-16 lg:grid-cols-[20rem_minmax(0,1fr)]">
        <aside class="space-y-8">
            <div>
                <p class="kicker text-ink-3">Telephone</p>
                <a href="{{ tel_href(ga('phone')) }}" class="font-display mt-1 inline-flex min-h-12 items-center text-4xl font-extrabold text-link">{{ format_phone(ga('phone')) }}</a>
            </div>
            <div>
                <p class="kicker text-ink-3">Email</p>
                <a href="mailto:{{ ga('email') }}" class="mt-1 inline-flex min-h-11 items-center break-all text-lg font-semibold text-link">{{ ga('email') }}</a>
            </div>
            <div>
                <p class="kicker text-ink-3">Head office</p>
                <p class="mt-1 text-lg">{{ ga('name') }}<br>{{ ga('address.street') }}<br>{{ ga('address.city') }}, {{ ga('address.county') }}<br>{{ ga('address.postcode') }}</p>
                <a href="{{ ga('map_url') }}" target="_blank" rel="noopener" class="mt-1 inline-flex min-h-11 items-center gap-1.5 font-semibold text-link">Get directions<x-icon name="external" class="h-4 w-4" /><span class="sr-only">(opens Google Maps)</span></a>
            </div>
            <div class="space-y-3">
                <a href="{{ route('quotes.create') }}" class="btn btn-primary w-full">Request a haulage quote</a>
                <a href="{{ route('warehousing.create') }}" class="btn btn-outline w-full">Warehousing enquiry</a>
                <a href="{{ route('careers.apply') }}" class="btn btn-outline w-full">Apply for a job</a>
            </div>
        </aside>

        <form method="POST" action="{{ route('contact.store') }}" class="min-w-0 space-y-8" x-data="sendForm" @submit="submit">
            @csrf
            <x-form-errors />
            <x-honeypot />
            <x-fieldset n="1" title="Send a message">
                <x-radios name="topic" label="What is it about?" :value="$topic" :options="collect(\App\Enums\EnquiryTopic::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])->all()" :required="true" cols="sm:grid-cols-2" />
                <p class="hint -mt-2">For a free benchmark, tell us roughly what you send, how often and where. Our managers will ask for a six-month order profile, which we treat in confidence.</p>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field name="name" label="Your name" :required="true" maxlength="120" autocomplete="name" />
                    <x-field name="organisation" label="Company" :optional="true" maxlength="160" autocomplete="organization" />
                    <x-field name="email" label="Email" type="email" :required="true" maxlength="255" autocomplete="email" />
                    <x-field name="phone" label="Phone" type="tel" :optional="true" maxlength="30" autocomplete="tel" inputmode="tel" />
                </div>
                <x-field name="message" label="Message" type="textarea" :required="true" maxlength="3000" rows="6" />
            </x-fieldset>
            <div class="border-t-[1.5px] border-ink pt-6">
                <x-submit label="Send message" />
                <p class="hint mt-3">We keep these details only to reply. <a class="text-link" href="{{ route('privacy') }}">Privacy notice</a>.</p>
            </div>
        </form>
    </section>
</x-layouts.site>
