<?php

namespace Locations13\Transformers;

use Locations13\Models\Community;
use Illuminate\Database\Eloquent\Model;
use Basics13\Transformers\ListTransformer;

/**
 * @extends ListTransformer<Community>
 */
class CommunityListTransformer extends ListTransformer
{
    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    protected function columns(Model $record, array $extra): array
    {
        return [
            'id' => $record->id,
            'name' => $record->name,
            'ineCode' => $record->ine_code ?? '',
            'state' => $record->state->name ?? '',
            'state_active' => $record->state->active ?? null,
            'editPayload' => $this->editPayload($record),
            ...$extra,
        ];
    }

    /**
     * A community the herrialdeak hang from is archived instead of deleted. The count comes with
     * the deleted countries in, because those keep the community too.
     */
    protected function getDeleteBlockCondition(Model $record): bool
    {
        return (int) $record->getAttribute('countries_count') > 0;
    }

    protected function getForceDeleteBlockCondition(Model $record): bool
    {
        return (int) $record->getAttribute('countries_count') > 0;
    }

    protected function getForceDeleteBlockHint(Model $record): string
    {
        return __('basics13::messages.cannot_force_delete_related');
    }

    /**
     * @param  Community  $record
     * @return array{id: int, name: string, ine_code: string|null, notes: string|null, state_id: int|null, state_label: string|null, state_active: bool|null}
     */
    protected function editPayload(Model $record): array
    {
        return [
            ...$record->only(['id', 'name', 'ine_code', 'notes', 'state_id']),
            'state_label' => $record->state->name ?? null,
            'state_active' => $record->state->active ?? null,
        ];
    }

    protected function resourceLabel(): string
    {
        return __('Communities');
    }

    protected function resourceKey(): string
    {
        return 'community';
    }
}
