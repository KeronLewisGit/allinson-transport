{{--
    Quote route: collection postcode on the left, delivery on the right, and a lorry in the
    company's livery that moves a third of the way as each section of the form is finished.
    Needs the quoteForm Alpine scope. Without JavaScript it shows the empty route and steps.
--}}
<div {{ $attributes->merge(['class' => 'route-strip']) }} :style="`--route: ${progress}`" aria-hidden="true">
    <div class="route-ends">
        <span><small>Collect</small><b x-text="from || 'Your postcode'">Your postcode</b></span>
        <span class="text-right"><small>Deliver</small><b x-text="to || 'Any UK postcode'">Any UK postcode</b></span>
    </div>
    <div class="route-road">
        <span class="end from"></span><span class="end to"></span>
        <span class="route-lorry-lane absolute inset-y-0 left-0 right-[5.75rem]"><x-lorry class="route-lorry" /></span>
    </div>
    <ol class="steps">
        <li data-n="1" :class="done[0] && 'is-done'">The load</li>
        <li data-n="2" :class="done[1] && 'is-done'">Route and date</li>
        <li data-n="3" :class="done[2] && 'is-done'">Your details</li>
    </ol>
</div>
