<?php

namespace Locations13\Http\Requests;

use Locations13\Models\Town;
use Illuminate\Validation\Rule;
use Locations13\Models\Neighbourhood;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;
use Basics13\Support\Validation\MaxLength;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Database\Query\Builder as QueryBuilder;

class NeighbourhoodRequest extends FormRequest
{
    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['town_id' => __('Town'), 'ine_code' => __('INE code')];
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
        $neighbourhood = $this->route('neighbourhood');

        return $neighbourhood
            ? ($this->user()?->can('update', $neighbourhood) ?? false)
            : ($this->user()?->can('create', Neighbourhood::class) ?? false);
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
                $this->uniqueNamePerTown(),
            ],
            'ine_code' => ['nullable', 'string', 'max:'.MaxLength::string()],
            'town_id' => [
                'required',
                'integer',
                $this->selectableTownRule(),
            ],
            'notes' => ['nullable', 'string', 'max:'.MaxLength::longText()],
            'reuse_deleted_name' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Neighbourhood names are unique inside their town, not globally: two towns may both keep a
     * neighbourhood called the same. The scope is only applied when the town id is usable; when
     * it is not, the rule falls back to global uniqueness, which never lets a duplicate through.
     */
    private function uniqueNamePerTown(): Unique
    {
        $rule = Rule::unique(Neighbourhood::class)
            ->ignore($this->route('neighbourhood'))
            ->whereNull('deleted_at');

        $townId = $this->integer('town_id');

        return $townId > 0 ? $rule->where('town_id', $townId) : $rule;
    }

    /**
     * The town a neighbourhood hangs from must exist and be active; the one already assigned
     * stays valid after it has been archived.
     */
    private function selectableTownRule(): Exists
    {
        $neighbourhood = $this->route('neighbourhood');
        $assigned = $neighbourhood instanceof Neighbourhood ? $neighbourhood->town_id : null;

        return Rule::exists(Town::class, 'id')
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
