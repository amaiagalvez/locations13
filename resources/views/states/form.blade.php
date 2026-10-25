<x-basics13::forms.tracked-resource prefix="state" context-field="_state_form"
    id-field="_state_id">
    <flux:field>
        <x-basics13::forms.field-label :request="\Locations13\Http\Requests\StateRequest::class" field="name"
            test="state-name-info" for="state-name">
            {{ __('Name') }}
        </x-basics13::forms.field-label>
        <flux:input name="name" x-model="form.name" minlength="2" required
            maxlength="{{ \Basics13\Support\Validation\MaxLength::string() }}"
            autofocus id="state-name" data-test="state-name" />
        <flux:error name="name" />
    </flux:field>

    <flux:field>
        <x-basics13::forms.field-label :request="\Locations13\Http\Requests\StateRequest::class" field="ine_code"
            test="state-ine-code-info" for="state-ine-code">
            {{ __('INE code') }}
        </x-basics13::forms.field-label>
        <flux:input name="ine_code" x-model="form.ine_code"
            maxlength="{{ \Basics13\Support\Validation\MaxLength::string() }}"
            id="state-ine-code" data-test="state-ine-code" />
        <flux:error name="ine_code" />
    </flux:field>

    <flux:field>
        <x-basics13::forms.field-label :request="\Locations13\Http\Requests\StateRequest::class" field="notes"
            test="state-notes-info" for="state-notes">
            {{ __('Notes') }}
        </x-basics13::forms.field-label>
        <flux:textarea name="notes" x-model="form.notes" rows="3"
            maxlength="{{ \Basics13\Support\Validation\MaxLength::longText() }}"
            id="state-notes" data-test="state-notes" />
        <flux:error name="notes" />
    </flux:field>
</x-basics13::forms.tracked-resource>
