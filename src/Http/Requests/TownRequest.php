<?php

namespace Locations13\Http\Requests;

use Locations13\Models\Town;
use Illuminate\Validation\Rule;
use Locations13\Models\District;
use Illuminate\Validation\Rules\Exists;
use Basics13\Support\Validation\MaxLength;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Database\Query\Builder as QueryBuilder;

class TownRequest extends FormRequest
{
    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['district_id' => __('District'), 'ine_code' => __('INE code')];
    }

    protected function prepareForValidation(): void
    {
        $codes = $this->input('postal_codes');

        // The form binds the textarea as one string, one code per line; a caller may also send the
        // list already split. Both end up as the same list: trimmed, blank lines dropped and
        // repeated codes collapsed, so a line repeated by hand never writes twice.
        if (is_string($codes)) {
            $codes = explode("\n", $codes);
        }

        $this->merge([
            'name' => $this->trimString('name'),
            'ine_code' => $this->blankToNull($this->trimString('ine_code')),
            'postal_codes' => is_array($codes)
                ? array_values(array_unique(array_filter(
                    array_map(
                        static function (mixed $code): string {
                            return is_string($code) ? trim($code) : (string) $code;
                        },
                        $codes,
                    ),
                    static fn (string $code): bool => $code !== '',
                )))
                : null,
        ]);
    }

    public function authorize(): bool
    {
        $town = $this->route('town');

        return $town
            ? ($this->user()?->can('update', $town) ?? false)
            : ($this->user()?->can('create', Town::class) ?? false);
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
                Rule::unique(Town::class)
                    ->ignore($this->route('town'))
                    ->whereNull('deleted_at'),
            ],
            'ine_code' => [
                'nullable',
                'string',
                'max:'.MaxLength::string(),
                Rule::unique(Town::class)
                    ->ignore($this->route('town'))
                    ->whereNull('deleted_at'),
            ],
            'district_id' => ['nullable', 'integer', $this->selectableDistrictRule()],
            'postal_codes' => ['nullable', 'array'],
            'postal_codes.*' => ['string', 'max:'.MaxLength::string()],
            'notes' => ['nullable', 'string', 'max:'.MaxLength::longText()],
            'reuse_deleted_name' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * A town may point at an active comarca, and may exist without any. The comarca it already
     * belongs to stays valid after it has been archived.
     */
    private function selectableDistrictRule(): Exists
    {
        $town = $this->route('town');
        $assigned = $town instanceof Town ? $town->district_id : null;

        return Rule::exists(District::class, 'id')
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
