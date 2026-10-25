<x-basics13::forms.tracked-resource prefix="community" context-field="_community_form"
    id-field="_community_id">
    <flux:field>
        <x-basics13::forms.field-label :request="\Locations13\Http\Requests\CommunityRequest::class" field="name"
            test="community-name-info" for="community-name">
            {{ __('Name') }}
        </x-basics13::forms.field-label>
        <flux:input name="name" x-model="form.name" minlength="2" required
            maxlength="{{ \Basics13\Support\Validation\MaxLength::string() }}"
            autofocus id="community-name" data-test="community-name" />
        <flux:error name="name" />
    </flux:field>

    <flux:field>
        <x-basics13::forms.field-label :request="\Locations13\Http\Requests\CommunityRequest::class" field="state_id"
            test="community-state-info" for="community-state-id">
            {{ __('State') }}
        </x-basics13::forms.field-label>
        <flux:select id="community-state-id" name="state_id" x-model="form.state_id"
            x-on:focus="searchCountries()" x-bind:aria-busy="stateOptionsLoading"
            data-test="community-state">
            <option value="">{{ __('No state') }}</option>
            <template x-for="state in stateOptions" :key="state.id">
                <option x-bind:value="String(state.id)"
                    x-text="state.active === false ? `${state.text} (${archivedLabel})` : state.text"></option>
            </template>
        </flux:select>
        <flux:text x-show="stateOptionsLoading" role="status" data-test="community-state-loading">
            {{ __('Loading records...') }}
        </flux:text>
        <flux:error name="state_id" />
    </flux:field>

    <flux:field>
        <x-basics13::forms.field-label :request="\Locations13\Http\Requests\CommunityRequest::class" field="ine_code"
            test="community-ine-code-info" for="community-ine-code">
            {{ __('INE code') }}
        </x-basics13::forms.field-label>
        <flux:input name="ine_code" x-model="form.ine_code"
            maxlength="{{ \Basics13\Support\Validation\MaxLength::string() }}"
            id="community-ine-code" data-test="community-ine-code" />
        <flux:error name="ine_code" />
    </flux:field>

    <flux:field>
        <x-basics13::forms.field-label :request="\Locations13\Http\Requests\CommunityRequest::class" field="notes"
            test="community-notes-info" for="community-notes">
            {{ __('Notes') }}
        </x-basics13::forms.field-label>
        <flux:textarea name="notes" x-model="form.notes" rows="3"
            maxlength="{{ \Basics13\Support\Validation\MaxLength::longText() }}"
            id="community-notes" data-test="community-notes" />
        <flux:error name="notes" />
    </flux:field>
</x-basics13::forms.tracked-resource>
