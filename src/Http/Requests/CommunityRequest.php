<?php

namespace Locations13\Http\Requests;

use Locations13\Models\State;
use Illuminate\Validation\Rule;
use Locations13\Models\Community;
use Illuminate\Validation\Rules\Exists;
use Basics13\Support\Validation\MaxLength;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Database\Query\Builder as QueryBuilder;

class CommunityRequest extends FormRequest
{
    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['state_id' => __('State'), 'ine_code' => __('INE code')];
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
        $community = $this->route('community');

        return $community
            ? ($this->user()?->can('update', $community) ?? false)
            : ($this->user()?->can('create', Community::class) ?? false);
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
                Rule::unique(Community::class)
                    ->ignore($this->route('community'))
                    ->whereNull('deleted_at'),
            ],
            'ine_code' => ['nullable', 'string', 'max:'.MaxLength::string()],
            'state_id' => ['nullable', 'integer', $this->selectableStateRule()],
            'notes' => ['nullable', 'string', 'max:'.MaxLength::longText()],
            'reuse_deleted_name' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * A community may point at an active state, and may exist without any. The state it already
     * belongs to stays valid after it has been archived, so a form that does not change it can
     * still be saved.
     */
    private function selectableStateRule(): Exists
    {
        $community = $this->route('community');
        $assigned = $community instanceof Community ? $community->state_id : null;

        return Rule::exists(State::class, 'id')
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
