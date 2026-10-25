<?php

namespace Locations13\Http\Requests;

use Illuminate\Validation\Rule;
use Locations13\Models\Country;
use Locations13\Models\Community;
use Illuminate\Validation\Rules\Exists;
use Basics13\Support\Validation\MaxLength;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Database\Query\Builder as QueryBuilder;

class CountryRequest extends FormRequest
{
    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['community_id' => __('Community'), 'ine_code' => __('INE code')];
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
        $country = $this->route('country');

        return $country
            ? ($this->user()?->can('update', $country) ?? false)
            : ($this->user()?->can('create', Country::class) ?? false);
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
                Rule::unique(Country::class)
                    ->ignore($this->route('country'))
                    ->whereNull('deleted_at'),
            ],
            'ine_code' => [
                'nullable',
                'string',
                'max:'.MaxLength::string(),
                Rule::unique(Country::class)
                    ->ignore($this->route('country'))
                    ->whereNull('deleted_at'),
            ],
            'community_id' => ['nullable', 'integer', $this->selectableCommunityRule()],
            'notes' => ['nullable', 'string', 'max:'.MaxLength::longText()],
            'reuse_deleted_name' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * A herrialde may point at an active community, and may exist without any. The community it
     * already belongs to stays valid after it has been archived, so a form that does not change
     * it can still be saved.
     */
    private function selectableCommunityRule(): Exists
    {
        $country = $this->route('country');
        $assigned = $country instanceof Country ? $country->community_id : null;

        return Rule::exists(Community::class, 'id')
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
