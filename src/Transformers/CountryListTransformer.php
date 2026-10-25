<?php

namespace Locations13\Transformers;

use Locations13\Models\Country;
use Illuminate\Database\Eloquent\Model;
use Basics13\Transformers\ListTransformer;

/**
 * @extends ListTransformer<Country>
 */
class CountryListTransformer extends ListTransformer
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
            'community' => $record->community->name ?? '',
            'community_active' => $record->community->active ?? null,
            'editPayload' => $this->editPayload($record),
            ...$extra,
        ];
    }

    /**
     * A herrialde the comarcas and towns point at is archived instead of deleted: it stays in the
     * table, which is where the child rows keep their name readable from. The counts come with
     * the deleted children in, because those keep the herrialde too.
     */
    protected function getDeleteBlockCondition(Model $record): bool
    {
        return $this->childrenCount($record) > 0;
    }

    protected function getForceDeleteBlockCondition(Model $record): bool
    {
        return $this->childrenCount($record) > 0;
    }

    protected function getForceDeleteBlockHint(Model $record): string
    {
        return __('basics13::messages.cannot_force_delete_related');
    }

    /**
     * @param  Country  $record
     * @return array{id: int, name: string, ine_code: string|null, notes: string|null, community_id: int|null, community_label: string|null, community_active: bool|null}
     */
    protected function editPayload(Model $record): array
    {
        return [
            ...$record->only(['id', 'name', 'ine_code', 'notes', 'community_id']),
            'community_label' => $record->community->name ?? null,
            'community_active' => $record->community->active ?? null,
        ];
    }

    protected function resourceLabel(): string
    {
        return __('Countries');
    }

    protected function resourceKey(): string
    {
        return 'country';
    }

    private function childrenCount(Model $record): int
    {
        return (int) $record->getAttribute('districts_count');
    }
}
