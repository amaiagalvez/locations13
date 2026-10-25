<?php

namespace Locations13\Transformers;

use Locations13\Models\District;
use Illuminate\Database\Eloquent\Model;
use Basics13\Transformers\ListTransformer;

/**
 * @extends ListTransformer<District>
 */
class DistrictListTransformer extends ListTransformer
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
            'country' => $record->country->name ?? '',
            'country_active' => $record->country->active ?? null,
            'editPayload' => $this->editPayload($record),
            ...$extra,
        ];
    }

    /**
     * A comarca the towns point at is archived instead of deleted. The count comes with the
     * deleted towns in, because those keep the comarca too.
     */
    protected function getDeleteBlockCondition(Model $record): bool
    {
        return (int) $record->getAttribute('towns_count') > 0;
    }

    protected function getForceDeleteBlockCondition(Model $record): bool
    {
        return (int) $record->getAttribute('towns_count') > 0;
    }

    protected function getForceDeleteBlockHint(Model $record): string
    {
        return __('basics13::messages.cannot_force_delete_related');
    }

    /**
     * @param  District  $record
     * @return array{id: int, name: string, ine_code: string|null, notes: string|null, country_id: int|null, country_label: string|null, country_active: bool|null}
     */
    protected function editPayload(Model $record): array
    {
        return [
            ...$record->only(['id', 'name', 'ine_code', 'notes', 'country_id']),
            'country_label' => $record->country->name ?? null,
            'country_active' => $record->country->active ?? null,
        ];
    }

    protected function resourceLabel(): string
    {
        return __('Districts');
    }

    protected function resourceKey(): string
    {
        return 'district';
    }
}
