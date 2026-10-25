@php
    $editingCountry = $list['create'] && str_starts_with(old('_country_form', ''), 'edit-');
    $deletedCountryConflict = session('deleted_country_conflict');

    $initialForm = $list['create']
        ? [
            'id' => $editingCountry ? old('_country_id') : null,
            'name' => old('name', ''),
            'ine_code' => old('ine_code', ''),
            'community_id' => old('community_id', ''),
            'community_label' => '',
            'community_active' => null,
            'notes' => old('notes', ''),
            'context' => $editingCountry ? old('_country_form') : 'create',
            'method' => $editingCountry ? 'PUT' : 'POST',
            'action' => $editingCountry
                ? route('countries.update', old('_country_id'))
                : route('countries.store'),
            'title' => $editingCountry ? __('Edit country') : __('New country'),
            'submitLabel' => $editingCountry ? __('Update country') : __('Save country'),
        ]
        : null;
    $scope = $scope ?? 'active';
@endphp

<x-layouts::app :title="$list['resource']">
    <div x-data="listConfirmation({
        form: @js($initialForm),
        communityOptions: @js(($list['create'] && $editingCountry && $initialForm && $initialForm['community_id']) ? [
            ['id' => $initialForm['community_id'], 'text' => $initialForm['community_label'] ?? '', 'active' => $initialForm['community_active'] ?? true],
        ] : []),
        archivedLabel: @js(__('basics13::messages.archived_label')),
        communityOptionsUrl: @js(route('communities.options', [], false)),
        communityOptionsRequest: null,
        communityOptionsLoading: false,
        storeUrl: @js(route('countries.store')),
        updateUrl: @js(route('countries.update', '__COUNTRY__')),
        async searchCountries() {
            const selected = this.communityOptions.find(
                (community) => String(community.id) === String(this.form.community_id),
            ) ?? (
                this.form.community_id
                    ? { id: String(this.form.community_id), text: this.form.community_label ?? '', active: this.form.community_active ?? true }
                    : null
            );

            this.communityOptionsRequest?.abort();
            const controller = new AbortController();
            this.communityOptionsRequest = controller;
            this.communityOptionsLoading = true;

            const url = new URL(this.communityOptionsUrl, window.location.href);
            url.searchParams.set('q', '');

            try {
                const response = await fetch(url, { headers: { Accept: 'application/json' }, signal: controller.signal });

                if (!response.ok) {
                    throw new Error();
                }

                const results = (await response.json()).results;

                this.communityOptions = selected && !results.some((community) => String(community.id) === String(selected.id))
                    ? [selected, ...results]
                    : results;
            } catch {
                if (!controller.signal.aborted) {
                    this.communityOptions = [];
                }
            } finally {
                if (this.communityOptionsRequest === controller) {
                    this.communityOptionsLoading = false;
                }
            }
        },
        createCountry() {
            this.communityOptionsRequest?.abort();
            this.communityOptions = [];
            this.communityOptionsLoading = false;
            this.form = {
                id: null,
                name: '',
                ine_code: '',
                community_id: '',
                community_label: '',
                community_active: null,
                notes: '',
                context: 'create',
                method: 'POST',
                action: this.storeUrl,
                title: @js(__('New country')),
                submitLabel: @js(__('Save country')),
            };
        },
        editCountry(country) {
            this.communityOptionsRequest?.abort();
            this.communityOptions = country.community_id ? [{
                id: String(country.community_id),
                text: country.community_label ?? '',
                active: country.community_active ?? true,
            }] : [];
            this.communityOptionsLoading = false;
            this.form = {
                ...country,
                ine_code: country.ine_code ?? '',
                community_id: country.community_id ? String(country.community_id) : '',
                context: `edit-${country.id}`,
                method: 'PUT',
                action: this.updateUrl.replace('__COUNTRY__', country.id),
                title: @js(__('Edit country')),
                submitLabel: @js(__('Update country')),
            };
        },
    })" @if ($errors->any())
            x-init="$nextTick(() => $dispatch('modal-show', { name: 'country-form' }))"
        @elseif ($deletedCountryConflict)
            x-init="$nextTick(() => $dispatch('modal-show', { name: 'country-name-conflict' }))"
        @elseif ($list['create'] && request()->boolean('create'))
            x-init="createCountry(); $nextTick(() => $dispatch('modal-show', { name: 'country-form' }))" @endif
        class="flex flex-col gap-y-2 sm:gap-y-3">
        <x-basics13::list.page-header :list="$list" prefix="country" :description="__('Countrys used to qualify the rest of the system.')">
            <x-slot:actions>
                @if ($list['create'])
                    <x-basics13::list.create-action prefix="country" :label="__('New country')"
                        click="createCountry()" />
                @endif
            </x-slot:actions>
        </x-basics13::list.page-header>

        <x-basics13::list.flash prefix="country" />

        @fragment('list-results')
            <x-basics13::list.searchable-results :search="$list['search']" :total="$countries->total()" :scope="$scope">
                <x-basics13::list.table prefix="country" :paginator="$countries">
                    <x-slot:toolbar>
                        <x-basics13::list.search :search="$list['search']" prefix="country" :scope="$scope" />
                    </x-slot:toolbar>

                    <flux:table.columns>
                        <flux:table.column scope="col">{{ __('Name') }}</flux:table.column>
                        <flux:table.column scope="col">{{ __('Community') }}</flux:table.column>
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
                                        data-test="country-name-{{ $row['id'] }}">
                                        {{ $row['name'] }}
                                    </span>
                                </flux:table.cell>
                                <flux:table.cell class="max-w-[16rem] truncate" :title="$row['community'] ?: null">
                                    <span class="inline-flex items-center gap-1">
                                        {{ $row['community'] ?: '—' }}
                                        @if ($row['community_active'] === false)
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
                                    <x-basics13::list.row-actions :actions="$row['actions']" prefix="country"
                                        edit-handler="editCountry" />
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <x-basics13::list.empty-state :narrowed="$list['narrowed']" :colspan="4" :message="$list['emptyMessage']">
                                @if ($list['create'] && ! $list['narrowed'])
                                    <flux:modal.trigger name="country-form">
                                        <flux:button size="sm" variant="primary" icon="plus"
                                            x-on:click="createCountry()"
                                            data-test="country-empty-create">
                                            {{ __('New country') }}
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
            <flux:modal name="country-form" aria-labelledby="country-form-heading"
                flyout variant="floating" position="right"
                x-on:close="window.clearForm($el.querySelector('form')); window.resetTrackedForms($el)"
                x-on:cancel.prevent="window.requestTrackedModalClose($el)"
                class="form-drawer max-w-none">
                @include('locations13::countries.form')
            </flux:modal>

            @if ($deletedCountryConflict)
                <x-basics13::name-conflict-modal name="country-name-conflict" :title="__('Record Name already in trash')"
                    :message="__('A deleted record already uses the name :name.', [
                        'name' => $deletedCountryConflict['name'],
                    ])" :create-action="route('countries.store')" :restore-action="route(
                        'countries.trash.restore',
                        $deletedCountryConflict['id'],
                    )" :create-fields="[
                        'name' => old('name', $deletedCountryConflict['name']),
                        'ine_code' => old('ine_code'),
                        'community_id' => old('community_id'),
                        'notes' => old('notes'),
                    ]"
                    :create-label="__('Create a new country')" :restore-label="__('Restore the deleted record instead')"
                    create-test="country-conflict-create-new"
                    restore-test="country-conflict-restore" />
            @endif
        @endif

        <x-basics13::list.confirm-modal prefix="country" />
    </div>
</x-layouts::app>
