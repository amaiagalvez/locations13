<?php

namespace Locations13\Transformers;

use Locations13\Models\Neighbourhood;
use Illuminate\Database\Eloquent\Model;
use Basics13\Transformers\ListTransformer;

/**
 * @extends ListTransformer<Neighbourhood>
 */
class NeighbourhoodListTransformer extends ListTransformer
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
            'town' => $record->town->name,
            'town_active' => $record->town->active,
            'editPayload' => $this->editPayload($record),
            ...$extra,
        ];
    }

    protected function getDeleteBlockCondition(Model $record): bool
    {
        return false;
    }

    protected function getForceDeleteBlockCondition(Model $record): bool
    {
        return false;
    }

    protected function getForceDeleteBlockHint(Model $record): string
    {
        return __('basics13::messages.cannot_force_delete_related');
    }

    /**
     * @param  Neighbourhood  $record
     * @return array{id: int, name: string, ine_code: string|null, notes: string|null, town_id: int, town_label: string|null, town_active: bool|null}
     */
    protected function editPayload(Model $record): array
    {
        return [
            ...$record->only(['id', 'name', 'ine_code', 'notes', 'town_id']),
            'town_label' => $record->town->name,
            'town_active' => $record->town->active,
        ];
    }

    protected function resourceLabel(): string
    {
        return __('Neighbourhoods');
    }

    protected function resourceKey(): string
    {
        return 'neighbourhood';
    }
}
