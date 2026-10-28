<x-layouts.site title="Thank you" :mobile-bar="false">
    <x-page-header photo="own-depot-night" position="center 60%" title="Thank you. We have it." :kicker="$kind" :ref="$reference" lead="Your reference is below, and we have emailed it to you as well." :label="['Reference' => $reference, 'Received' => now()->format('d/m/y H:i'), '*Contents' => $kind]" />
    <section class="wrap mt-12 md:mt-16">
        <div class="relative max-w-xl">
            <span class="stamp" aria-hidden="true">Received</span>
            <x-consignment :flat="true" head="Received at Darlington" :ref="$reference" :fields="['Reference' => $reference, 'Received' => now()->format('j M Y, H:i'), '*Submission' => $kind, '*Received by' => ga('name').', '.ga('address.city').' '.ga('address.postcode')]" />
            <div class="relative mt-6 h-14 overflow-hidden border-b-[3px] border-dashed border-line-strong" aria-hidden="true"><x-lorry class="drive-off absolute bottom-0 left-[30%] h-12 w-[6.5rem]" /></div>
            <p class="mt-6 text-ink-2">Quote the reference if you call or email, and we will find your submission straight away.</p>
        </div>
        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ tel_href(ga('phone')) }}" class="btn btn-outline"><x-icon name="phone" class="h-4 w-4" />{{ format_phone(ga('phone')) }}</a>
            <a href="{{ route('services') }}" class="btn btn-outline">Our services</a>
            <a href="{{ route('home') }}" class="btn btn-primary">Back to the home page</a>
        </div>
    </section>
</x-layouts.site>
