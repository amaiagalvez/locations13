@php
    $editingNeighbourhood = $list['create'] && str_starts_with(old('_neighbourhood_form', ''), 'edit-');
    $deletedNeighbourhoodConflict = session('deleted_neighbourhood_conflict');

    $initialForm = $list['create']
        ? [
            'id' => $editingNeighbourhood ? old('_neighbourhood_id') : null,
            'name' => old('name', ''),
            'ine_code' => old('ine_code', ''),
            'town_id' => old('town_id', ''),
            'town_label' => '',
            'town_active' => null,
            'notes' => old('notes', ''),
            'context' => $editingNeighbourhood ? old('_neighbourhood_form') : 'create',
            'method' => $editingNeighbourhood ? 'PUT' : 'POST',
            'action' => $editingNeighbourhood
                ? route('neighbourhoods.update', old('_neighbourhood_id'))
                : route('neighbourhoods.store'),
            'title' => $editingNeighbourhood ? __('Edit neighbourhood') : __('New neighbourhood'),
            'submitLabel' => $editingNeighbourhood ? __('Update neighbourhood') : __('Save neighbourhood'),
        ]
        : null;
    $scope = $scope ?? 'active';
@endphp

<x-layouts::app :title="$list['resource']">
    <div x-data="listConfirmation({
        form: @js($initialForm),
        townOptions: @js(($list['create'] && $editingNeighbourhood && $initialForm && $initialForm['town_id']) ? [
            ['id' => $initialForm['town_id'], 'text' => $initialForm['town_label'] ?? '', 'active' => $initialForm['town_active'] ?? true],
        ] : []),
        archivedLabel: @js(__('basics13::messages.archived_label')),
        townOptionsUrl: @js(route('towns.options', [], false)),
        townOptionsRequest: null,
        townOptionsLoading: false,
        storeUrl: @js(route('neighbourhoods.store')),
        updateUrl: @js(route('neighbourhoods.update', '__NEIGHBOURHOOD__')),
        async searchCountries() {
            const selected = this.townOptions.find(
                (town) => String(town.id) === String(this.form.town_id),
            ) ?? (
                this.form.town_id
                    ? { id: String(this.form.town_id), text: this.form.town_label ?? '', active: this.form.town_active ?? true }
                    : null
            );

            this.townOptionsRequest?.abort();
            const controller = new AbortController();
            this.townOptionsRequest = controller;
            this.townOptionsLoading = true;

            const url = new URL(this.townOptionsUrl, window.location.href);
            url.searchParams.set('q', '');

            try {
                const response = await fetch(url, { headers: { Accept: 'application/json' }, signal: controller.signal });

                if (!response.ok) {
                    throw new Error();
                }

                const results = (await response.json()).results;

                this.townOptions = selected && !results.some((town) => String(town.id) === String(selected.id))
                    ? [selected, ...results]
                    : results;
            } catch {
                if (!controller.signal.aborted) {
                    this.townOptions = [];
                }
            } finally {
                if (this.townOptionsRequest === controller) {
                    this.townOptionsLoading = false;
                }
            }
        },
        createNeighbourhood() {
            this.townOptionsRequest?.abort();
            this.townOptions = [];
            this.townOptionsLoading = false;
            this.form = {
                id: null,
                name: '',
                ine_code: '',
                town_id: '',
                town_label: '',
                town_active: null,
                notes: '',
                context: 'create',
                method: 'POST',
                action: this.storeUrl,
                title: @js(__('New neighbourhood')),
                submitLabel: @js(__('Save neighbourhood')),
            };
        },
        editNeighbourhood(neighbourhood) {
            this.townOptionsRequest?.abort();
            this.townOptions = neighbourhood.town_id ? [{
                id: String(neighbourhood.town_id),
                text: neighbourhood.town_label ?? '',
                active: neighbourhood.town_active ?? true,
            }] : [];
            this.townOptionsLoading = false;
            this.form = {
                ...neighbourhood,
                ine_code: neighbourhood.ine_code ?? '',
                town_id: neighbourhood.town_id ? String(neighbourhood.town_id) : '',
                context: `edit-${neighbourhood.id}`,
                method: 'PUT',
                action: this.updateUrl.replace('__NEIGHBOURHOOD__', neighbourhood.id),
                title: @js(__('Edit neighbourhood')),
                submitLabel: @js(__('Update neighbourhood')),
            };
        },
    })" @if ($errors->any())
            x-init="$nextTick(() => $dispatch('modal-show', { name: 'neighbourhood-form' }))"
        @elseif ($deletedNeighbourhoodConflict)
            x-init="$nextTick(() => $dispatch('modal-show', { name: 'neighbourhood-name-conflict' }))"
        @elseif ($list['create'] && request()->boolean('create'))
            x-init="createNeighbourhood(); $nextTick(() => $dispatch('modal-show', { name: 'neighbourhood-form' }))" @endif
        class="flex flex-col gap-y-2 sm:gap-y-3">
        <x-basics13::list.page-header :list="$list" prefix="neighbourhood" :description="__('Neighbourhoods used to qualify the rest of the system.')">
            <x-slot:actions>
                @if ($list['create'])
                    <x-basics13::list.create-action prefix="neighbourhood" :label="__('New neighbourhood')"
                        click="createNeighbourhood()" />
                @endif
            </x-slot:actions>
        </x-basics13::list.page-header>

        <x-basics13::list.flash prefix="neighbourhood" />

        @fragment('list-results')
            <x-basics13::list.searchable-results :search="$list['search']" :total="$neighbourhoods->total()" :scope="$scope">
                <x-basics13::list.table prefix="neighbourhood" :paginator="$neighbourhoods">
                    <x-slot:toolbar>
                        <x-basics13::list.search :search="$list['search']" prefix="neighbourhood" :scope="$scope" />
                    </x-slot:toolbar>

                    <flux:table.columns>
                        <flux:table.column scope="col">{{ __('Name') }}</flux:table.column>
                        <flux:table.column scope="col">{{ __('Town') }}</flux:table.column>
                        <flux:table.column scope="col">{{ __('INE code') }}</flux:table.column>
                        <flux:table.column scope="col" align="end" sticky class="text-end">
                            <span class="sr-only">{{ __('Actions') }}</span>
                        </flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($list['rows'] as $row)
                            <flux:table.row :key="$row['id']"
                                class="transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <flux:table.cell class="max-w-[16rem]">
                                    <span class="block max-w-full truncate" title="{{ $row['name'] }}"
                                        data-test="neighbourhood-name-{{ $row['id'] }}">
                                        {{ $row['name'] }}
                                    </span>
                                </flux:table.cell>
                                <flux:table.cell class="max-w-[16rem] truncate" :title="$row['town'] ?: null">
                                    <span class="inline-flex items-center gap-1">
                                        {{ $row['town'] ?: '—' }}
                                        @if ($row['town_active'] === false)
                                            <span class="shrink-0 rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                                {{ __('basics13::messages.archived_label') }}
                                            </span>
                                        @endif
                                    </span>
                                </flux:table.cell>
                                <flux:table.cell class="max-w-[8rem]">
                                    {{ $row['ineCode'] !== '' ? $row['ineCode'] : '—' }}
                                </flux:table.cell>
                                <flux:table.cell align="end" sticky
                                    class="bg-white dark:bg-zinc-900">
                                    <x-basics13::list.row-actions :actions="$row['actions']" prefix="neighbourhood"
                                        edit-handler="editNeighbourhood" />
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <x-basics13::list.empty-state :narrowed="$list['narrowed']" :colspan="4" :message="$list['emptyMessage']">
                                @if ($list['create'] && ! $list['narrowed'])
                                    <flux:modal.trigger name="neighbourhood-form">
                                        <flux:button size="sm" variant="primary" icon="plus"
                                            x-on:click="createNeighbourhood()"
                                            data-test="neighbourhood-empty-create">
                                            {{ __('New neighbourhood') }}
                                        </flux:button>
                                    </flux:modal.trigger>
                                @endif
                            </x-basics13::list.empty-state>
                        @endforelse
                    </flux:table.rows>
                </x-basics13::list.table>
            </x-basics13::list.searchable-results>
        @endfragment

        @if ($list['create'])
            <flux:modal name="neighbourhood-form" aria-labelledby="neighbourhood-form-heading"
                flyout variant="floating" position="right"
                x-on:close="window.clearForm($el.querySelector('form')); window.resetTrackedForms($el)"
                x-on:cancel.prevent="window.requestTrackedModalClose($el)"
                class="form-drawer max-w-none">
                @include('locations13::neighbourhoods.form')
            </flux:modal>

            @if ($deletedNeighbourhoodConflict)
                <x-basics13::name-conflict-modal name="neighbourhood-name-conflict" :title="__('Record Name already in trash')"
                    :message="__('A deleted record already uses the name :name.', [
                        'name' => $deletedNeighbourhoodConflict['name'],
                    ])" :create-action="route('neighbourhoods.store')" :restore-action="route(
                        'neighbourhoods.trash.restore',
                        $deletedNeighbourhoodConflict['id'],
                    )" :create-fields="[
                        'name' => old('name', $deletedNeighbourhoodConflict['name']),
                        'ine_code' => old('ine_code'),
                        'town_id' => old('town_id'),
                        'notes' => old('notes'),
                    ]"
                    :create-label="__('Create a new neighbourhood')" :restore-label="__('Restore the deleted record instead')"
                    create-test="neighbourhood-conflict-create-new"
                    restore-test="neighbourhood-conflict-restore" />
            @endif
        @endif

        <x-basics13::list.confirm-modal prefix="neighbourhood" />
    </div>
</x-layouts::app>
