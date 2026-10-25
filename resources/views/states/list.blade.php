@php
    $editingState = $list['create'] && str_starts_with(old('_state_form', ''), 'edit-');
    $deletedStateConflict = session('deleted_state_conflict');

    $initialForm = $list['create']
        ? [
            'id' => $editingState ? old('_state_id') : null,
            'name' => old('name', ''),
            'ine_code' => old('ine_code', ''),
            'notes' => old('notes', ''),
            'context' => $editingState ? old('_state_form') : 'create',
            'method' => $editingState ? 'PUT' : 'POST',
            'action' => $editingState
                ? route('states.update', old('_state_id'))
                : route('states.store'),
            'title' => $editingState ? __('Edit state') : __('New state'),
            'submitLabel' => $editingState ? __('Update state') : __('Save state'),
        ]
        : null;
    $scope = $scope ?? 'active';
@endphp

<x-layouts::app :title="$list['resource']">
    <div x-data="listConfirmation({
        form: @js($initialForm),
        storeUrl: @js(route('states.store')),
        updateUrl: @js(route('states.update', '__STATE__')),
        createState() {
            this.form = {
                id: null,
                name: '',
                ine_code: '',
                notes: '',
                context: 'create',
                method: 'POST',
                action: this.storeUrl,
                title: @js(__('New state')),
                submitLabel: @js(__('Save state')),
            };
        },
        editState(state) {
            this.form = {
                ...state,
                ine_code: state.ine_code ?? '',
                context: `edit-${state.id}`,
                method: 'PUT',
                action: this.updateUrl.replace('__STATE__', state.id),
                title: @js(__('Edit state')),
                submitLabel: @js(__('Update state')),
            };
        },
    })" @if ($errors->any())
            x-init="$nextTick(() => $dispatch('modal-show', { name: 'state-form' }))"
        @elseif ($deletedStateConflict)
            x-init="$nextTick(() => $dispatch('modal-show', { name: 'state-name-conflict' }))"
        @elseif ($list['create'] && request()->boolean('create'))
            x-init="createState(); $nextTick(() => $dispatch('modal-show', { name: 'state-form' }))" @endif
        class="flex flex-col gap-y-2 sm:gap-y-3">
        <x-basics13::list.page-header :list="$list" prefix="state" :description="__('Countries used to qualify the rest of the system.')">
            <x-slot:actions>
                @if ($list['create'])
                    <x-basics13::list.create-action prefix="state" :label="__('New state')"
                        click="createState()" />
                @endif
            </x-slot:actions>
        </x-basics13::list.page-header>

        <x-basics13::list.flash prefix="state" />

        @fragment('list-results')
            <x-basics13::list.searchable-results :search="$list['search']" :total="$states->total()" :scope="$scope">
                <x-basics13::list.table prefix="state" :paginator="$states">
                    <x-slot:toolbar>
                        <x-basics13::list.search :search="$list['search']" prefix="state" :scope="$scope" />
                    </x-slot:toolbar>

                    <flux:table.columns>
                        <flux:table.column scope="col">{{ __('Name') }}</flux:table.column>
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
                                        data-test="state-name-{{ $row['id'] }}">
                                        {{ $row['name'] }}
                                    </span>
                                </flux:table.cell>
                                <flux:table.cell class="max-w-[8rem]">
                                    {{ $row['ineCode'] !== '' ? $row['ineCode'] : '—' }}
                                </flux:table.cell>
                                <flux:table.cell align="end" sticky
                                    class="bg-white dark:bg-zinc-900">
                                    <x-basics13::list.row-actions :actions="$row['actions']" prefix="state"
                                        edit-handler="editState" />
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <x-basics13::list.empty-state :narrowed="$list['narrowed']" :colspan="3" :message="$list['emptyMessage']">
                                @if ($list['create'] && ! $list['narrowed'])
                                    <flux:modal.trigger name="state-form">
                                        <flux:button size="sm" variant="primary" icon="plus"
                                            x-on:click="createState()"
                                            data-test="state-empty-create">
                                            {{ __('New state') }}
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
            <flux:modal name="state-form" aria-labelledby="state-form-heading"
                flyout variant="floating" position="right"
                x-on:close="window.clearForm($el.querySelector('form')); window.resetTrackedForms($el)"
                x-on:cancel.prevent="window.requestTrackedModalClose($el)"
                class="form-drawer max-w-none">
                @include('locations13::states.form')
            </flux:modal>

            @if ($deletedStateConflict)
                <x-basics13::name-conflict-modal name="state-name-conflict" :title="__('Record Name already in trash')"
                    :message="__('A deleted record already uses the name :name.', [
                        'name' => $deletedStateConflict['name'],
                    ])" :create-action="route('states.store')" :restore-action="route(
                        'states.trash.restore',
                        $deletedStateConflict['id'],
                    )" :create-fields="[
                        'name' => old('name', $deletedStateConflict['name']),
                        'ine_code' => old('ine_code'),
                        'notes' => old('notes'),
                    ]"
                    :create-label="__('Create a new state')" :restore-label="__('Restore the deleted record instead')"
                    create-test="state-conflict-create-new"
                    restore-test="state-conflict-restore" />
            @endif
        @endif

        <x-basics13::list.confirm-modal prefix="state" />
    </div>
</x-layouts::app>
