@php
    $editingCommunity = $list['create'] && str_starts_with(old('_community_form', ''), 'edit-');
    $deletedCommunityConflict = session('deleted_community_conflict');

    $initialForm = $list['create']
        ? [
            'id' => $editingCommunity ? old('_community_id') : null,
            'name' => old('name', ''),
            'ine_code' => old('ine_code', ''),
            'state_id' => old('state_id', ''),
            'state_label' => '',
            'state_active' => null,
            'notes' => old('notes', ''),
            'context' => $editingCommunity ? old('_community_form') : 'create',
            'method' => $editingCommunity ? 'PUT' : 'POST',
            'action' => $editingCommunity
                ? route('communities.update', old('_community_id'))
                : route('communities.store'),
            'title' => $editingCommunity ? __('Edit community') : __('New community'),
            'submitLabel' => $editingCommunity ? __('Update community') : __('Save community'),
        ]
        : null;
    $scope = $scope ?? 'active';
@endphp

<x-layouts::app :title="$list['resource']">
    <div x-data="listConfirmation({
        form: @js($initialForm),
        stateOptions: @js(($list['create'] && $editingCommunity && $initialForm && $initialForm['state_id']) ? [
            ['id' => $initialForm['state_id'], 'text' => $initialForm['state_label'] ?? '', 'active' => $initialForm['state_active'] ?? true],
        ] : []),
        archivedLabel: @js(__('basics13::messages.archived_label')),
        stateOptionsUrl: @js(route('states.options', [], false)),
        stateOptionsRequest: null,
        stateOptionsLoading: false,
        storeUrl: @js(route('communities.store')),
        updateUrl: @js(route('communities.update', '__COMMUNITY__')),
        async searchCountries() {
            const selected = this.stateOptions.find(
                (state) => String(state.id) === String(this.form.state_id),
            ) ?? (
                this.form.state_id
                    ? { id: String(this.form.state_id), text: this.form.state_label ?? '', active: this.form.state_active ?? true }
                    : null
            );

            this.stateOptionsRequest?.abort();
            const controller = new AbortController();
            this.stateOptionsRequest = controller;
            this.stateOptionsLoading = true;

            const url = new URL(this.stateOptionsUrl, window.location.href);
            url.searchParams.set('q', '');

            try {
                const response = await fetch(url, { headers: { Accept: 'application/json' }, signal: controller.signal });

                if (!response.ok) {
                    throw new Error();
                }

                const results = (await response.json()).results;

                this.stateOptions = selected && !results.some((state) => String(state.id) === String(selected.id))
                    ? [selected, ...results]
                    : results;
            } catch {
                if (!controller.signal.aborted) {
                    this.stateOptions = [];
                }
            } finally {
                if (this.stateOptionsRequest === controller) {
                    this.stateOptionsLoading = false;
                }
            }
        },
        createCommunity() {
            this.stateOptionsRequest?.abort();
            this.stateOptions = [];
            this.stateOptionsLoading = false;
            this.form = {
                id: null,
                name: '',
                ine_code: '',
                state_id: '',
                state_label: '',
                state_active: null,
                notes: '',
                context: 'create',
                method: 'POST',
                action: this.storeUrl,
                title: @js(__('New community')),
                submitLabel: @js(__('Save community')),
            };
        },
        editCommunity(community) {
            this.stateOptionsRequest?.abort();
            this.stateOptions = community.state_id ? [{
                id: String(community.state_id),
                text: community.state_label ?? '',
                active: community.state_active ?? true,
            }] : [];
            this.stateOptionsLoading = false;
            this.form = {
                ...community,
                ine_code: community.ine_code ?? '',
                state_id: community.state_id ? String(community.state_id) : '',
                context: `edit-${community.id}`,
                method: 'PUT',
                action: this.updateUrl.replace('__COMMUNITY__', community.id),
                title: @js(__('Edit community')),
                submitLabel: @js(__('Update community')),
            };
        },
    })" @if ($errors->any())
            x-init="$nextTick(() => $dispatch('modal-show', { name: 'community-form' }))"
        @elseif ($deletedCommunityConflict)
            x-init="$nextTick(() => $dispatch('modal-show', { name: 'community-name-conflict' }))"
        @elseif ($list['create'] && request()->boolean('create'))
            x-init="createCommunity(); $nextTick(() => $dispatch('modal-show', { name: 'community-form' }))" @endif
        class="flex flex-col gap-y-2 sm:gap-y-3">
        <x-basics13::list.page-header :list="$list" prefix="community" :description="__('Communitys used to qualify the rest of the system.')">
            <x-slot:actions>
                @if ($list['create'])
                    <x-basics13::list.create-action prefix="community" :label="__('New community')"
                        click="createCommunity()" />
                @endif
            </x-slot:actions>
        </x-basics13::list.page-header>

        <x-basics13::list.flash prefix="community" />

        @fragment('list-results')
            <x-basics13::list.searchable-results :search="$list['search']" :total="$communities->total()" :scope="$scope">
                <x-basics13::list.table prefix="community" :paginator="$communities">
                    <x-slot:toolbar>
                        <x-basics13::list.search :search="$list['search']" prefix="community" :scope="$scope" />
                    </x-slot:toolbar>

                    <flux:table.columns>
                        <flux:table.column scope="col">{{ __('Name') }}</flux:table.column>
                        <flux:table.column scope="col">{{ __('State') }}</flux:table.column>
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
                                        data-test="community-name-{{ $row['id'] }}">
                                        {{ $row['name'] }}
                                    </span>
                                </flux:table.cell>
                                <flux:table.cell class="max-w-[16rem] truncate" :title="$row['state'] ?: null">
                                    <span class="inline-flex items-center gap-1">
                                        {{ $row['state'] ?: '—' }}
                                        @if ($row['state_active'] === false)
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
                                    <x-basics13::list.row-actions :actions="$row['actions']" prefix="community"
                                        edit-handler="editCommunity" />
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <x-basics13::list.empty-state :narrowed="$list['narrowed']" :colspan="4" :message="$list['emptyMessage']">
                                @if ($list['create'] && ! $list['narrowed'])
                                    <flux:modal.trigger name="community-form">
                                        <flux:button size="sm" variant="primary" icon="plus"
                                            x-on:click="createCommunity()"
                                            data-test="community-empty-create">
                                            {{ __('New community') }}
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
            <flux:modal name="community-form" aria-labelledby="community-form-heading"
                flyout variant="floating" position="right"
                x-on:close="window.clearForm($el.querySelector('form')); window.resetTrackedForms($el)"
                x-on:cancel.prevent="window.requestTrackedModalClose($el)"
                class="form-drawer max-w-none">
                @include('locations13::communities.form')
            </flux:modal>

            @if ($deletedCommunityConflict)
                <x-basics13::name-conflict-modal name="community-name-conflict" :title="__('Record Name already in trash')"
                    :message="__('A deleted record already uses the name :name.', [
                        'name' => $deletedCommunityConflict['name'],
                    ])" :create-action="route('communities.store')" :restore-action="route(
                        'communities.trash.restore',
                        $deletedCommunityConflict['id'],
                    )" :create-fields="[
                        'name' => old('name', $deletedCommunityConflict['name']),
                        'ine_code' => old('ine_code'),
                        'state_id' => old('state_id'),
                        'notes' => old('notes'),
                    ]"
                    :create-label="__('Create a new community')" :restore-label="__('Restore the deleted record instead')"
                    create-test="community-conflict-create-new"
                    restore-test="community-conflict-restore" />
            @endif
        @endif

        <x-basics13::list.confirm-modal prefix="community" />
    </div>
</x-layouts::app>
