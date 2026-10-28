@php($cats = (array) old('licence_categories', $staff->licence_categories ?? []))
<x-layouts.admin :title="$staff->exists ? 'Edit '.$staff->name : 'Add a person'">
    <x-slot:actions><a href="{{ $staff->exists ? route('admin.staff.show', $staff) : route('admin.staff.index') }}" class="a-btn a-btn-ghost"><x-icon name="arrow-left" class="h-4 w-4" />Back</a></x-slot:actions>
    <form method="POST" action="{{ $staff->exists ? route('admin.staff.update', $staff) : route('admin.staff.store') }}" class="a-card max-w-3xl space-y-5 p-5 md:p-6">
        @csrf
        @if ($staff->exists) @method('PUT') @endif
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label for="s-name" class="mb-1 block font-semibold">Name</label><input id="s-name" name="name" value="{{ input_string(old('name', $staff->name)) }}" class="a-input w-full" required maxlength="120"></div>
            <div><label for="s-num" class="mb-1 block font-semibold">Employee number <span class="font-normal text-ink-3">(optional)</span></label><input id="s-num" name="employee_number" value="{{ input_string(old('employee_number', $staff->employee_number)) }}" class="a-input w-full" maxlength="30"></div>
            <div><label for="s-role" class="mb-1 block font-semibold">Role</label><select id="s-role" name="role" class="a-input w-full">@foreach (\App\Enums\JobRole::cases() as $r)<option value="{{ $r->value }}" @selected(old('role', $staff->role?->value) === $r->value)>{{ $r->label() }}</option>@endforeach</select></div>
            <div><label for="s-depot" class="mb-1 block font-semibold">Depot</label><select id="s-depot" name="depot" class="a-input w-full">@foreach (\App\Support\Depots::options() as $k => $n)<option value="{{ $k }}" @selected(old('depot', $staff->depot) === $k)>{{ $n }}</option>@endforeach</select></div>
            <div><label for="s-phone" class="mb-1 block font-semibold">Phone <span class="font-normal text-ink-3">(optional)</span></label><input id="s-phone" name="phone" type="tel" value="{{ input_string(old('phone', $staff->phone)) }}" class="a-input w-full" maxlength="30"></div>
            <div><label for="s-email" class="mb-1 block font-semibold">Email <span class="font-normal text-ink-3">(optional)</span></label><input id="s-email" name="email" type="email" value="{{ input_string(old('email', $staff->email)) }}" class="a-input w-full" maxlength="255"></div>
        </div>
        <fieldset><legend class="mb-1 block font-semibold">Licence categories</legend>
            <div class="flex flex-wrap gap-3">@foreach (ga('careers.licence_categories') as $k => $l)<label class="flex min-h-11 items-center gap-2"><input type="checkbox" name="licence_categories[]" value="{{ $k }}" class="h-5 w-5 accent-[var(--primary)]" @checked(in_array($k, $cats, true))>{{ $l }}</label>@endforeach</div>
        </fieldset>
        <div class="grid gap-4 sm:grid-cols-3">
            <div><label for="s-cpc" class="mb-1 block font-semibold">Driver CPC expires</label><input id="s-cpc" type="date" name="cpc_expires_on" value="{{ input_string(old('cpc_expires_on', $staff->cpc_expires_on?->format('Y-m-d'))) }}" class="a-input w-full"></div>
            <div><label for="s-tacho" class="mb-1 block font-semibold">Tacho card expires</label><input id="s-tacho" type="date" name="tacho_card_expires_on" value="{{ input_string(old('tacho_card_expires_on', $staff->tacho_card_expires_on?->format('Y-m-d'))) }}" class="a-input w-full"></div>
            <div><label for="s-lic" class="mb-1 block font-semibold">Licence photocard expires</label><input id="s-lic" type="date" name="licence_expires_on" value="{{ input_string(old('licence_expires_on', $staff->licence_expires_on?->format('Y-m-d'))) }}" class="a-input w-full"></div>
            <div><label for="s-checked" class="mb-1 block font-semibold">Cards checked on</label><input id="s-checked" type="date" name="cards_checked_on" max="{{ today()->format('Y-m-d') }}" value="{{ input_string(old('cards_checked_on', $staff->cards_checked_on?->format('Y-m-d'))) }}" class="a-input w-full"></div>
        </div>
        <p class="text-sm text-ink-3">Changing a date restarts its reminders.</p>
        <label class="flex min-h-11 items-center gap-2"><input type="checkbox" name="active" value="1" class="h-5 w-5 accent-[var(--primary)]" @checked(old('active', $staff->active))>Currently employed</label>
        <button class="a-btn a-btn-primary">Save</button>
    </form>
</x-layouts.admin>
