<x-basics13::forms.tracked-resource prefix="district" context-field="_district_form"
    id-field="_district_id">
    <flux:field>
        <x-basics13::forms.field-label :request="\Locations13\Http\Requests\DistrictRequest::class" field="name"
            test="district-name-info" for="district-name">
            {{ __('Name') }}
        </x-basics13::forms.field-label>
        <flux:input name="name" x-model="form.name" minlength="2" required
            maxlength="{{ \Basics13\Support\Validation\MaxLength::string() }}"
            autofocus id="district-name" data-test="district-name" />
        <flux:error name="name" />
    </flux:field>

    <flux:field>
        <x-basics13::forms.field-label :request="\Locations13\Http\Requests\DistrictRequest::class" field="country_id"
            test="district-country-info" for="district-country-id">
            {{ __('Country') }}
        </x-basics13::forms.field-label>
        <flux:select id="district-country-id" name="country_id" x-model="form.country_id"
            x-on:focus="searchCountries()" x-bind:aria-busy="countryOptionsLoading"
            data-test="district-country">
            <option value="">{{ __('No country') }}</option>
            <template x-for="country in countryOptions" :key="country.id">
                <option x-bind:value="String(country.id)"
                    x-text="country.active === false ? `${country.text} (${archivedLabel})` : country.text"></option>
            </template>
        </flux:select>
        <flux:text x-show="countryOptionsLoading" role="status" data-test="district-country-loading">
            {{ __('Loading records...') }}
        </flux:text>
        <flux:error name="country_id" />
    </flux:field>

    <flux:field>
        <x-basics13::forms.field-label :request="\Locations13\Http\Requests\DistrictRequest::class" field="ine_code"
            test="district-ine-code-info" for="district-ine-code">
            {{ __('INE code') }}
        </x-basics13::forms.field-label>
        <flux:input name="ine_code" x-model="form.ine_code"
            maxlength="{{ \Basics13\Support\Validation\MaxLength::string() }}"
            id="district-ine-code" data-test="district-ine-code" />
        <flux:error name="ine_code" />
    </flux:field>

    <flux:field>
        <x-basics13::forms.field-label :request="\Locations13\Http\Requests\DistrictRequest::class" field="notes"
            test="district-notes-info" for="district-notes">
            {{ __('Notes') }}
        </x-basics13::forms.field-label>
        <flux:textarea name="notes" x-model="form.notes" rows="3"
            maxlength="{{ \Basics13\Support\Validation\MaxLength::longText() }}"
            id="district-notes" data-test="district-notes" />
        <flux:error name="notes" />
    </flux:field>
</x-basics13::forms.tracked-resource>
