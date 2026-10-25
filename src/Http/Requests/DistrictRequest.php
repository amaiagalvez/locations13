<?php

namespace Locations13\Http\Requests;

use Illuminate\Validation\Rule;
use Locations13\Models\Country;
use Locations13\Models\District;
use Illuminate\Validation\Rules\Exists;
use Basics13\Support\Validation\MaxLength;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Database\Query\Builder as QueryBuilder;

class DistrictRequest extends FormRequest
{
    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['country_id' => __('Country'), 'ine_code' => __('INE code')];
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
        $district = $this->route('district');

        return $district
            ? ($this->user()?->can('update', $district) ?? false)
            : ($this->user()?->can('create', District::class) ?? false);
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
                Rule::unique(District::class)
                    ->ignore($this->route('district'))
                    ->whereNull('deleted_at'),
            ],
            'ine_code' => ['nullable', 'string', 'max:'.MaxLength::string()],
            'country_id' => ['nullable', 'integer', $this->selectableCountryRule()],
            'notes' => ['nullable', 'string', 'max:'.MaxLength::longText()],
            'reuse_deleted_name' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * A comarca may point at an active herrialde, and may exist without any. The herrialde it
     * already belongs to stays valid after it has been archived, so a form that does not change
     * it can still be saved.
     */
    private function selectableCountryRule(): Exists
    {
        $district = $this->route('district');
        $assigned = $district instanceof District ? $district->country_id : null;

        return Rule::exists(Country::class, 'id')
            ->whereNull('deleted_at')
            ->where(static function (QueryBuilder $query) use ($assigned): void {
                $query->where('active', true);

                if (is_int($assigned)) {
                    $query->orWhere('id', $assigned);
                }
            });
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
