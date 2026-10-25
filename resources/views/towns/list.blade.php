@php
    $editingTown = $list['create'] && str_starts_with(old('_town_form', ''), 'edit-');
    $deletedTownConflict = session('deleted_town_conflict');

    $initialForm = $list['create']
        ? [
            'id' => $editingTown ? old('_town_id') : null,
            'name' => old('name', ''),
            'ine_code' => old('ine_code', ''),
            'district_id' => old('district_id', ''),
            'district_label' => '',
            'district_active' => null,
            'postal_codes' => is_array(old('postal_codes')) ? implode("\n", old('postal_codes')) : old('postal_codes', ''),
            'notes' => old('notes', ''),
            'context' => $editingTown ? old('_town_form') : 'create',
            'method' => $editingTown ? 'PUT' : 'POST',
            'action' => $editingTown
                ? route('towns.update', old('_town_id'))
                : route('towns.store'),
            'title' => $editingTown ? __('Edit town') : __('New town'),
            'submitLabel' => $editingTown ? __('Update town') : __('Save town'),
        ]
        : null;
    $scope = $scope ?? 'active';
@endphp

<x-layouts::app :title="$list['resource']">
    <div x-data="listConfirmation({
        form: @js($initialForm),
        districtOptions: @js(($list['create'] && $editingTown && $initialForm && $initialForm['district_id']) ? [
            ['id' => $initialForm['district_id'], 'text' => $initialForm['district_label'] ?? '', 'active' => $initialForm['district_active'] ?? true],
        ] : []),
        archivedLabel: @js(__('basics13::messages.archived_label')),
        districtOptionsUrl: @js(route('districts.options', [], false)),
        districtOptionsRequest: null,
        districtOptionsLoading: false,
        storeUrl: @js(route('towns.store')),
        updateUrl: @js(route('towns.update', '__TOWN__')),
        async searchCountries() {
            const selected = this.districtOptions.find(
                (district) => String(district.id) === String(this.form.district_id),
            ) ?? (
                this.form.district_id
                    ? { id: String(this.form.district_id), text: this.form.district_label ?? '', active: this.form.district_active ?? true }
                    : null
            );

            this.districtOptionsRequest?.abort();
            const controller = new AbortController();
            this.districtOptionsRequest = controller;
            this.districtOptionsLoading = true;

            const url = new URL(this.districtOptionsUrl, window.location.href);
            url.searchParams.set('q', '');

            try {
                const response = await fetch(url, { headers: { Accept: 'application/json' }, signal: controller.signal });

                if (!response.ok) {
                    throw new Error();
                }

                const results = (await response.json()).results;

                this.districtOptions = selected && !results.some((district) => String(district.id) === String(selected.id))
                    ? [selected, ...results]
                    : results;
            } catch {
                if (!controller.signal.aborted) {
                    this.districtOptions = [];
                }
            } finally {
                if (this.districtOptionsRequest === controller) {
                    this.districtOptionsLoading = false;
                }
            }
        },
        createTown() {
            this.districtOptionsRequest?.abort();
            this.districtOptions = [];
            this.districtOptionsLoading = false;
            this.form = {
                id: null,
                name: '',
                ine_code: '',
                district_id: '',
                district_label: '',
                district_active: null,
                postal_codes: '',
                notes: '',
                context: 'create',
                method: 'POST',
                action: this.storeUrl,
                title: @js(__('New town')),
                submitLabel: @js(__('Save town')),
            };
        },
        editTown(town) {
            this.districtOptionsRequest?.abort();
            this.districtOptions = town.district_id ? [{
                id: String(town.district_id),
                text: town.district_label ?? '',
                active: town.district_active ?? true,
            }] : [];
            this.districtOptionsLoading = false;
            this.form = {
                ...town,
                ine_code: town.ine_code ?? '',
                postal_codes: (town.postal_codes ?? []).join('\n'),
                district_id: town.district_id ? String(town.district_id) : '',
                context: `edit-${town.id}`,
                method: 'PUT',
                action: this.updateUrl.replace('__TOWN__', town.id),
                title: @js(__('Edit town')),
                submitLabel: @js(__('Update town')),
            };
        },
    })" @if ($errors->any())
            x-init="$nextTick(() => $dispatch('modal-show', { name: 'town-form' }))"
        @elseif ($deletedTownConflict)
            x-init="$nextTick(() => $dispatch('modal-show', { name: 'town-name-conflict' }))"
        @elseif ($list['create'] && request()->boolean('create'))
            x-init="createTown(); $nextTick(() => $dispatch('modal-show', { name: 'town-form' }))" @endif
        class="flex flex-col gap-y-2 sm:gap-y-3">
        <x-basics13::list.page-header :list="$list" prefix="town" :description="__('Towns used to qualify the rest of the system.')">
            <x-slot:actions>
                @if ($list['create'])
                    <x-basics13::list.create-action prefix="town" :label="__('New town')"
                        click="createTown()" />
                @endif
            </x-slot:actions>
        </x-basics13::list.page-header>

        <x-basics13::list.flash prefix="town" />

        @fragment('list-results')
            <x-basics13::list.searchable-results :search="$list['search']" :total="$towns->total()" :scope="$scope">
                <x-basics13::list.table prefix="town" :paginator="$towns">
                    <x-slot:toolbar>
                        <x-basics13::list.search :search="$list['search']" prefix="town" :scope="$scope" />
                    </x-slot:toolbar>

                    <flux:table.columns>
                        <flux:table.column scope="col">{{ __('Name') }}</flux:table.column>
                        <flux:table.column scope="col">{{ __('District') }}</flux:table.column>
                        <flux:table.column scope="col">{{ __('Postal codes') }}</flux:table.column>
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
                                        data-test="town-name-{{ $row['id'] }}">
                                        {{ $row['name'] }}
                                    </span>
                                </flux:table.cell>
                                <flux:table.cell class="max-w-[16rem] truncate" :title="$row['district'] ?: null">
                                    <span class="inline-flex items-center gap-1">
                                        {{ $row['district'] ?: '—' }}
                                        @if ($row['district_active'] === false)
                                            <span class="shrink-0 rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                                {{ __('basics13::messages.archived_label') }}
                                            </span>
                                        @endif
                                    </span>
                                </flux:table.cell>
                                <flux:table.cell class="max-w-[8rem]">
                                    {{ $row['ineCode'] !== '' ? $row['ineCode'] : '—' }}
                                </flux:table.cell>
                                                                <flux:table.cell>
                                    {{ implode(', ', $row['postalCodes']) ?: '—' }}
                                </flux:table.cell>
<flux:table.cell align="end" sticky
                                    class="bg-white dark:bg-zinc-900">
                                    <x-basics13::list.row-actions :actions="$row['actions']" prefix="town"
                                        edit-handler="editTown" />
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <x-basics13::list.empty-state :narrowed="$list['narrowed']" :colspan="4" :message="$list['emptyMessage']">
                                @if ($list['create'] && ! $list['narrowed'])
                                    <flux:modal.trigger name="town-form">
                                        <flux:button size="sm" variant="primary" icon="plus"
                                            x-on:click="createTown()"
                                            data-test="town-empty-create">
                                            {{ __('New town') }}
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
            <flux:modal name="town-form" aria-labelledby="town-form-heading"
                flyout variant="floating" position="right"
                x-on:close="window.clearForm($el.querySelector('form')); window.resetTrackedForms($el)"
                x-on:cancel.prevent="window.requestTrackedModalClose($el)"
                class="form-drawer max-w-none">
                @include('locations13::towns.form')
            </flux:modal>

            @if ($deletedTownConflict)
                <x-basics13::name-conflict-modal name="town-name-conflict" :title="__('Record Name already in trash')"
                    :message="__('A deleted record already uses the name :name.', [
                        'name' => $deletedTownConflict['name'],
                    ])" :create-action="route('towns.store')" :restore-action="route(
                        'towns.trash.restore',
                        $deletedTownConflict['id'],
                    )" :create-fields="[
                        'name' => old('name', $deletedTownConflict['name']),
                        'ine_code' => old('ine_code'),
                        'district_id' => old('district_id'),
                        'notes' => old('notes'),
                    ]"
                    :create-label="__('Create a new town')" :restore-label="__('Restore the deleted record instead')"
                    create-test="town-conflict-create-new"
                    restore-test="town-conflict-restore" />
            @endif
        @endif

        <x-basics13::list.confirm-modal prefix="town" />
    </div>
</x-layouts::app>
