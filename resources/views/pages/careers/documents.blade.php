<x-layouts.site title="Upload your documents" :mobile-bar="false">
    <x-page-header photo="tacho" kicker="Careers" title="Upload your documents" :ref="$application->reference" lead="Photos or scans of each side are fine. This page is private to you; the link stops working after {{ \App\Models\JobApplication::DOCUMENTS_LINK_DAYS }} days." :label="['*Applicant' => $application->full_name, '*Depot' => $application->depotName()]" />

    <section class="wrap mt-12 grid gap-12 md:mt-16 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <form method="POST" action="{{ request()->fullUrl() }}" enctype="multipart/form-data" class="min-w-0 space-y-8" x-data="sendForm" @submit="submit">
            @csrf
            @if (session('status'))
                <div class="panel flex items-start gap-3 border-l-[6px] border-l-[color:var(--good)] p-5" role="status"><x-icon name="check-circle" class="h-5 w-5 flex-none text-[color:var(--good)]" /><p>{{ session('status') }}</p></div>
            @endif
            <x-form-errors />
            <x-fieldset n="1" title="Your documents" intro="Upload what you have now; you can come back to this link to add the rest.">
                @foreach ($kinds as $kind)
                    @php($current = $application->currentDocuments->firstWhere('kind', $kind))
                    <div>
                        <label for="f-docs-{{ $kind->value }}" class="label">{{ $kind->label() }} <span class="opt">(PDF, JPG, PNG or HEIC, 8 MB max)</span></label>
                        <input id="f-docs-{{ $kind->value }}" name="docs[{{ $kind->value }}]" type="file" class="input" accept=".pdf,.jpg,.jpeg,.png,.heic">
                        @if ($current)<p class="hint flex items-center gap-1.5"><x-icon name="check-circle" class="h-4 w-4 text-[color:var(--good)]" />Received {{ $current->created_at->format('j M Y') }}. Upload again to replace it.</p>@endif
                        <x-held-upload form="applicant-documents" :field="'docs.'.$kind->value" />
                        <x-error :for="'docs.'.$kind->value" />
                    </div>
                @endforeach
                <x-error for="docs" />
            </x-fieldset>
            <div class="border-t-[1.5px] border-ink pt-6"><x-submit label="Upload" /></div>
        </form>
        <aside class="space-y-4 text-ink-2">
            <p>For right to work, a UK or Irish passport, or your Home Office share code, is usually enough. HR will tell you if anything else is needed.</p>
            <p>Questions? Call {{ format_phone(ga('phone')) }} and quote {{ $application->reference }}.</p>
        </aside>
    </section>
</x-layouts.site>
