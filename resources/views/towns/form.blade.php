<x-basics13::forms.tracked-resource prefix="town" context-field="_town_form"
    id-field="_town_id">
    <flux:field>
        <x-basics13::forms.field-label :request="\Locations13\Http\Requests\TownRequest::class" field="name"
            test="town-name-info" for="town-name">
            {{ __('Name') }}
        </x-basics13::forms.field-label>
        <flux:input name="name" x-model="form.name" minlength="2" required
            maxlength="{{ \Basics13\Support\Validation\MaxLength::string() }}"
            autofocus id="town-name" data-test="town-name" />
        <flux:error name="name" />
    </flux:field>

    <flux:field>
        <x-basics13::forms.field-label :request="\Locations13\Http\Requests\TownRequest::class" field="district_id"
            test="town-district-info" for="town-district-id">
            {{ __('District') }}
        </x-basics13::forms.field-label>
        <flux:select id="town-district-id" name="district_id" x-model="form.district_id"
            x-on:focus="searchCountries()" x-bind:aria-busy="districtOptionsLoading"
            data-test="town-district">
            <option value="">{{ __('No district') }}</option>
            <template x-for="district in districtOptions" :key="district.id">
                <option x-bind:value="String(district.id)"
                    x-text="district.active === false ? `${district.text} (${archivedLabel})` : district.text"></option>
            </template>
        </flux:select>
        <flux:text x-show="districtOptionsLoading" role="status" data-test="town-district-loading">
            {{ __('Loading records...') }}
        </flux:text>
        <flux:error name="district_id" />
    </flux:field>

    <flux:field>
        <x-basics13::forms.field-label :request="\Locations13\Http\Requests\TownRequest::class" field="ine_code"
            test="town-ine-code-info" for="town-ine-code">
            {{ __('INE code') }}
        </x-basics13::forms.field-label>
        <flux:input name="ine_code" x-model="form.ine_code"
            maxlength="{{ \Basics13\Support\Validation\MaxLength::string() }}"
            id="town-ine-code" data-test="town-ine-code" />
        <flux:error name="ine_code" />
    </flux:field>

    <flux:field>
        <x-basics13::forms.field-label :request="\Locations13\Http\Requests\TownRequest::class" field="notes"
            test="town-notes-info" for="town-notes">
            {{ __('Notes') }}
        </x-basics13::forms.field-label>
        <flux:textarea name="notes" x-model="form.notes" rows="3"
            maxlength="{{ \Basics13\Support\Validation\MaxLength::longText() }}"
            id="town-notes" data-test="town-notes" />
        <flux:error name="notes" />
    </flux:field>

    <flux:field>
        <x-basics13::forms.field-label :request="\Locations13\Http\Requests\TownRequest::class" field="postal_codes"
            test="town-postal-codes-info" for="town-postal-codes">
            {{ __('Postal codes') }}
        </x-basics13::forms.field-label>
        <flux:textarea name="postal_codes" x-model="form.postal_codes" rows="3"
            id="town-postal-codes" data-test="town-postal-codes" />
        <x-basics13::forms.field-hint :request="\Locations13\Http\Requests\TownRequest::class" field="postal_codes"
            id="town-postal-codes-hint" />
        <flux:error name="postal_codes" />
    </flux:field>
</x-basics13::forms.tracked-resource>
