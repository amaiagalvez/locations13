<?php

namespace Locations13\Transformers;

use Locations13\Models\State;
use Illuminate\Database\Eloquent\Model;
use Basics13\Transformers\ListTransformer;

/**
 * @extends ListTransformer<State>
 */
class StateListTransformer extends ListTransformer
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
            'editPayload' => $this->editPayload($record),
            ...$extra,
        ];
    }

    /**
     * A state the communities hang from is archived instead of deleted. The count comes with the
     * deleted communities in, because those keep the state too.
     */
    protected function getDeleteBlockCondition(Model $record): bool
    {
        return (int) $record->getAttribute('communities_count') > 0;
    }

    protected function getForceDeleteBlockCondition(Model $record): bool
    {
        return (int) $record->getAttribute('communities_count') > 0;
    }

    protected function getForceDeleteBlockHint(Model $record): string
    {
        return __('basics13::messages.cannot_force_delete_related');
    }

    /**
     * @param  State  $record
     * @return array{id: int, name: string, ine_code: string|null, notes: string|null}
     */
    protected function editPayload(Model $record): array
    {
        return $record->only(['id', 'name', 'ine_code', 'notes']);
    }

    protected function resourceLabel(): string
    {
        return __('States');
    }

    protected function resourceKey(): string
    {
        return 'state';
    }
}
