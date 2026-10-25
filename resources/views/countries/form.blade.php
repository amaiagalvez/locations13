<x-basics13::forms.tracked-resource prefix="country" context-field="_country_form"
    id-field="_country_id">
    <flux:field>
        <x-basics13::forms.field-label :request="\Locations13\Http\Requests\CountryRequest::class" field="name"
            test="country-name-info" for="country-name">
            {{ __('Name') }}
        </x-basics13::forms.field-label>
        <flux:input name="name" x-model="form.name" minlength="2" required
            maxlength="{{ \Basics13\Support\Validation\MaxLength::string() }}"
            autofocus id="country-name" data-test="country-name" />
        <flux:error name="name" />
    </flux:field>

    <flux:field>
        <x-basics13::forms.field-label :request="\Locations13\Http\Requests\CountryRequest::class" field="community_id"
            test="country-community-info" for="country-community-id">
            {{ __('Community') }}
        </x-basics13::forms.field-label>
        <flux:select id="country-community-id" name="community_id" x-model="form.community_id"
            x-on:focus="searchCountries()" x-bind:aria-busy="communityOptionsLoading"
            data-test="country-community">
            <option value="">{{ __('No community') }}</option>
            <template x-for="community in communityOptions" :key="community.id">
                <option x-bind:value="String(community.id)"
                    x-text="community.active === false ? `${community.text} (${archivedLabel})` : community.text"></option>
            </template>
        </flux:select>
        <flux:text x-show="communityOptionsLoading" role="status" data-test="country-community-loading">
            {{ __('Loading records...') }}
        </flux:text>
        <flux:error name="community_id" />
    </flux:field>

    <flux:field>
        <x-basics13::forms.field-label :request="\Locations13\Http\Requests\CountryRequest::class" field="ine_code"
            test="country-ine-code-info" for="country-ine-code">
            {{ __('INE code') }}
        </x-basics13::forms.field-label>
        <flux:input name="ine_code" x-model="form.ine_code"
            maxlength="{{ \Basics13\Support\Validation\MaxLength::string() }}"
            id="country-ine-code" data-test="country-ine-code" />
        <flux:error name="ine_code" />
    </flux:field>

    <flux:field>
        <x-basics13::forms.field-label :request="\Locations13\Http\Requests\CountryRequest::class" field="notes"
            test="country-notes-info" for="country-notes">
            {{ __('Notes') }}
        </x-basics13::forms.field-label>
        <flux:textarea name="notes" x-model="form.notes" rows="3"
            maxlength="{{ \Basics13\Support\Validation\MaxLength::longText() }}"
            id="country-notes" data-test="country-notes" />
        <flux:error name="notes" />
    </flux:field>
</x-basics13::forms.tracked-resource>
