<?php

namespace Locations13\Transformers;

use Locations13\Models\Town;
use Illuminate\Database\Eloquent\Model;
use Basics13\Transformers\ListTransformer;

/**
 * @extends ListTransformer<Town>
 */
class TownListTransformer extends ListTransformer
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
            'district' => $record->district->name ?? '',
            'district_active' => $record->district->active ?? null,
            'postalCodes' => $record->postalCodes
                ->pluck('code')
                ->map(static fn (mixed $code): string => (string) $code)
                ->values()
                ->all(),
            'editPayload' => $this->editPayload($record),
            ...$extra,
        ];
    }

    /**
     * A town the neighbourhoods point at is archived instead of deleted. The count comes with the
     * deleted neighbourhoods in, because those keep the town too; the postal codes never block,
     * they vanish with the town.
     */
    protected function getDeleteBlockCondition(Model $record): bool
    {
        return (int) $record->getAttribute('neighbourhoods_count') > 0;
    }

    protected function getForceDeleteBlockCondition(Model $record): bool
    {
        return (int) $record->getAttribute('neighbourhoods_count') > 0;
    }

    protected function getForceDeleteBlockHint(Model $record): string
    {
        return __('basics13::messages.cannot_force_delete_related');
    }

    /**
     * @param  Town  $record
     * @return array{id: int, name: string, ine_code: string|null, notes: string|null, district_id: int|null, district_label: string|null, district_active: bool|null, postal_codes: list<string>}
     */
    protected function editPayload(Model $record): array
    {
        /** @var list<string> $postalCodes */
        $postalCodes = array_values($record->postalCodes
            ->pluck('code')
            ->map(static fn (mixed $code): string => (string) $code)
            ->all());

        return [
            ...$record->only(['id', 'name', 'ine_code', 'notes', 'district_id']),
            'district_label' => $record->district->name ?? null,
            'district_active' => $record->district->active ?? null,
            'postal_codes' => $postalCodes,
        ];
    }

    protected function resourceLabel(): string
    {
        return __('Towns');
    }

    protected function resourceKey(): string
    {
        return 'town';
    }
}
