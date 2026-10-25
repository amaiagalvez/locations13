<?php

namespace Locations13\Http\Requests;

use Locations13\Models\State;
use Illuminate\Validation\Rule;
use Basics13\Support\Validation\MaxLength;
use Illuminate\Foundation\Http\FormRequest;

class StateRequest extends FormRequest
{
    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['ine_code' => __('INE code')];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->trimString('name'),
            'ine_code' => $this->blankToNull($this->trimString('ine_code')),
        ]);
    }

    public function authorize(): bool
    {
        $state = $this->route('state');

        return $state
            ? ($this->user()?->can('update', $state) ?? false)
            : ($this->user()?->can('create', State::class) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:'.MaxLength::string(),
                Rule::unique(State::class)
                    ->ignore($this->route('state'))
                    ->whereNull('deleted_at'),
            ],
            'ine_code' => ['nullable', 'string', 'max:'.MaxLength::string()],
            'notes' => ['nullable', 'string', 'max:'.MaxLength::longText()],
            'reuse_deleted_name' => ['sometimes', 'boolean'],
        ];
    }

    private function trimString(string $field): mixed
    {
        $value = $this->input($field);

        return is_string($value) ? trim($value) : $value;
    }

    private function blankToNull(mixed $value): mixed
    {
        return $value === '' ? null : $value;
    }
}
