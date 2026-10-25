@php
    $editingDistrict = $list['create'] && str_starts_with(old('_district_form', ''), 'edit-');
    $deletedDistrictConflict = session('deleted_district_conflict');

    $initialForm = $list['create']
        ? [
            'id' => $editingDistrict ? old('_district_id') : null,
            'name' => old('name', ''),
            'ine_code' => old('ine_code', ''),
            'country_id' => old('country_id', ''),
            'country_label' => '',
            'country_active' => null,
            'notes' => old('notes', ''),
            'context' => $editingDistrict ? old('_district_form') : 'create',
            'method' => $editingDistrict ? 'PUT' : 'POST',
            'action' => $editingDistrict
                ? route('districts.update', old('_district_id'))
                : route('districts.store'),
            'title' => $editingDistrict ? __('Edit district') : __('New district'),
            'submitLabel' => $editingDistrict ? __('Update district') : __('Save district'),
        ]
        : null;
    $scope = $scope ?? 'active';
@endphp

<x-layouts::app :title="$list['resource']">
    <div x-data="listConfirmation({
        form: @js($initialForm),
        countryOptions: @js(($list['create'] && $editingDistrict && $initialForm && $initialForm['country_id']) ? [
            ['id' => $initialForm['country_id'], 'text' => $initialForm['country_label'] ?? '', 'active' => $initialForm['country_active'] ?? true],
        ] : []),
        archivedLabel: @js(__('basics13::messages.archived_label')),
        countryOptionsUrl: @js(route('countries.options', [], false)),
        countryOptionsRequest: null,
        countryOptionsLoading: false,
        storeUrl: @js(route('districts.store')),
        updateUrl: @js(route('districts.update', '__DISTRICT__')),
        async searchCountries() {
            const selected = this.countryOptions.find(
                (country) => String(country.id) === String(this.form.country_id),
            ) ?? (
                this.form.country_id
                    ? { id: String(this.form.country_id), text: this.form.country_label ?? '', active: this.form.country_active ?? true }
                    : null
            );

            this.countryOptionsRequest?.abort();
            const controller = new AbortController();
            this.countryOptionsRequest = controller;
            this.countryOptionsLoading = true;

            const url = new URL(this.countryOptionsUrl, window.location.href);
            url.searchParams.set('q', '');

            try {
                const response = await fetch(url, { headers: { Accept: 'application/json' }, signal: controller.signal });

                if (!response.ok) {
                    throw new Error();
                }

                const results = (await response.json()).results;

                this.countryOptions = selected && !results.some((country) => String(country.id) === String(selected.id))
                    ? [selected, ...results]
                    : results;
            } catch {
                if (!controller.signal.aborted) {
                    this.countryOptions = [];
                }
            } finally {
                if (this.countryOptionsRequest === controller) {
                    this.countryOptionsLoading = false;
                }
            }
        },
        createDistrict() {
            this.countryOptionsRequest?.abort();
            this.countryOptions = [];
            this.countryOptionsLoading = false;
            this.form = {
                id: null,
                name: '',
                ine_code: '',
                country_id: '',
                country_label: '',
                country_active: null,
                notes: '',
                context: 'create',
                method: 'POST',
                action: this.storeUrl,
                title: @js(__('New district')),
                submitLabel: @js(__('Save district')),
            };
        },
        editDistrict(district) {
            this.countryOptionsRequest?.abort();
            this.countryOptions = district.country_id ? [{
                id: String(district.country_id),
                text: district.country_label ?? '',
                active: district.country_active ?? true,
            }] : [];
            this.countryOptionsLoading = false;
            this.form = {
                ...district,
                ine_code: district.ine_code ?? '',
                country_id: district.country_id ? String(district.country_id) : '',
                context: `edit-${district.id}`,
                method: 'PUT',
                action: this.updateUrl.replace('__DISTRICT__', district.id),
                title: @js(__('Edit district')),
                submitLabel: @js(__('Update district')),
            };
        },
    })" @if ($errors->any())
            x-init="$nextTick(() => $dispatch('modal-show', { name: 'district-form' }))"
        @elseif ($deletedDistrictConflict)
            x-init="$nextTick(() => $dispatch('modal-show', { name: 'district-name-conflict' }))"
        @elseif ($list['create'] && request()->boolean('create'))
            x-init="createDistrict(); $nextTick(() => $dispatch('modal-show', { name: 'district-form' }))" @endif
        class="flex flex-col gap-y-2 sm:gap-y-3">
        <x-basics13::list.page-header :list="$list" prefix="district" :description="__('Districts used to qualify the rest of the system.')">
            <x-slot:actions>
                @if ($list['create'])
                    <x-basics13::list.create-action prefix="district" :label="__('New district')"
                        click="createDistrict()" />
                @endif
            </x-slot:actions>
        </x-basics13::list.page-header>

        <x-basics13::list.flash prefix="district" />

        @fragment('list-results')
            <x-basics13::list.searchable-results :search="$list['search']" :total="$districts->total()" :scope="$scope">
                <x-basics13::list.table prefix="district" :paginator="$districts">
                    <x-slot:toolbar>
                        <x-basics13::list.search :search="$list['search']" prefix="district" :scope="$scope" />
                    </x-slot:toolbar>

                    <flux:table.columns>
                        <flux:table.column scope="col">{{ __('Name') }}</flux:table.column>
                        <flux:table.column scope="col">{{ __('Country') }}</flux:table.column>
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
                                        data-test="district-name-{{ $row['id'] }}">
                                        {{ $row['name'] }}
                                    </span>
                                </flux:table.cell>
                                <flux:table.cell class="max-w-[16rem] truncate" :title="$row['country'] ?: null">
                                    <span class="inline-flex items-center gap-1">
                                        {{ $row['country'] ?: '—' }}
                                        @if ($row['country_active'] === false)
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
                                    <x-basics13::list.row-actions :actions="$row['actions']" prefix="district"
                                        edit-handler="editDistrict" />
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <x-basics13::list.empty-state :narrowed="$list['narrowed']" :colspan="4" :message="$list['emptyMessage']">
                                @if ($list['create'] && ! $list['narrowed'])
                                    <flux:modal.trigger name="district-form">
                                        <flux:button size="sm" variant="primary" icon="plus"
                                            x-on:click="createDistrict()"
                                            data-test="district-empty-create">
                                            {{ __('New district') }}
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
            <flux:modal name="district-form" aria-labelledby="district-form-heading"
                flyout variant="floating" position="right"
                x-on:close="window.clearForm($el.querySelector('form')); window.resetTrackedForms($el)"
                x-on:cancel.prevent="window.requestTrackedModalClose($el)"
                class="form-drawer max-w-none">
                @include('locations13::districts.form')
            </flux:modal>

            @if ($deletedDistrictConflict)
                <x-basics13::name-conflict-modal name="district-name-conflict" :title="__('Record Name already in trash')"
                    :message="__('A deleted record already uses the name :name.', [
                        'name' => $deletedDistrictConflict['name'],
                    ])" :create-action="route('districts.store')" :restore-action="route(
                        'districts.trash.restore',
                        $deletedDistrictConflict['id'],
                    )" :create-fields="[
                        'name' => old('name', $deletedDistrictConflict['name']),
                        'ine_code' => old('ine_code'),
                        'country_id' => old('country_id'),
                        'notes' => old('notes'),
                    ]"
                    :create-label="__('Create a new district')" :restore-label="__('Restore the deleted record instead')"
                    create-test="district-conflict-create-new"
                    restore-test="district-conflict-restore" />
            @endif
        @endif

        <x-basics13::list.confirm-modal prefix="district" />
    </div>
</x-layouts::app>
