<x-basics13::forms.tracked-resource prefix="neighbourhood" context-field="_neighbourhood_form"
    id-field="_neighbourhood_id">
    <flux:field>
        <x-basics13::forms.field-label :request="\Locations13\Http\Requests\NeighbourhoodRequest::class" field="name"
            test="neighbourhood-name-info" for="neighbourhood-name">
            {{ __('Name') }}
        </x-basics13::forms.field-label>
        <flux:input name="name" x-model="form.name" minlength="2" required
            maxlength="{{ \Basics13\Support\Validation\MaxLength::string() }}"
            autofocus id="neighbourhood-name" data-test="neighbourhood-name" />
        <flux:error name="name" />
    </flux:field>

    <flux:field>
        <x-basics13::forms.field-label :request="\Locations13\Http\Requests\NeighbourhoodRequest::class" field="town_id"
            test="neighbourhood-town-info" for="neighbourhood-town-id">
            {{ __('Town') }}
        </x-basics13::forms.field-label>
        <flux:select id="neighbourhood-town-id" name="town_id" x-model="form.town_id" required
            x-on:focus="searchCountries()" x-bind:aria-busy="townOptionsLoading"
            data-test="neighbourhood-town">
            <option value="">{{ __('No town') }}</option>
            <template x-for="town in townOptions" :key="town.id">
                <option x-bind:value="String(town.id)"
                    x-text="town.active === false ? `${town.text} (${archivedLabel})` : town.text"></option>
            </template>
        </flux:select>
        <flux:text x-show="townOptionsLoading" role="status" data-test="neighbourhood-town-loading">
            {{ __('Loading records...') }}
        </flux:text>
        <flux:error name="town_id" />
    </flux:field>

    <flux:field>
        <x-basics13::forms.field-label :request="\Locations13\Http\Requests\NeighbourhoodRequest::class" field="ine_code"
            test="neighbourhood-ine-code-info" for="neighbourhood-ine-code">
            {{ __('INE code') }}
        </x-basics13::forms.field-label>
        <flux:input name="ine_code" x-model="form.ine_code"
            maxlength="{{ \Basics13\Support\Validation\MaxLength::string() }}"
            id="neighbourhood-ine-code" data-test="neighbourhood-ine-code" />
        <flux:error name="ine_code" />
    </flux:field>

    <flux:field>
        <x-basics13::forms.field-label :request="\Locations13\Http\Requests\NeighbourhoodRequest::class" field="notes"
            test="neighbourhood-notes-info" for="neighbourhood-notes">
            {{ __('Notes') }}
        </x-basics13::forms.field-label>
        <flux:textarea name="notes" x-model="form.notes" rows="3"
            maxlength="{{ \Basics13\Support\Validation\MaxLength::longText() }}"
            id="neighbourhood-notes" data-test="neighbourhood-notes" />
        <flux:error name="notes" />
    </flux:field>
</x-basics13::forms.tracked-resource>
