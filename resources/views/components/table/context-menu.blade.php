@if($model->getContextMenu())
<div @contextmenu="openContextMenu($event)" @click.away="closeContextMenu" @keydown.escape="closeContextMenu" x-data="{
            visible: false,
            currentRow: null,
            init() {
                document.addEventListener('scroll', () => {
                    if (this.visible) {
                        this.closeContextMenu();
                    }
                }, true);
            },
            openContextMenu(event) {
                const row = event.target.closest('.cm-table-row');
                if (row) {
                    event.preventDefault();
                    this.$refs.contextMenu.style.top = event.clientY + 'px';
                    this.$refs.contextMenu.style.left = event.clientX + 'px';
                    this.visible = true;
                    this.currentRow = row.getAttribute('data-id');
                    if(this.currentRow == null) {
                        this.closeContextMenu();
                    }
                } else {
                    this.closeContextMenu();
                }
            },
            closeContextMenu() {
                this.visible = false;
            },
            viewAction(e) {
                if(!this.currentRow) {
                    this.closeContextMenu();
                    return;
                }
                @this.call('action', {action: 'view', id: this.currentRow});
                this.closeContextMenu();
            },
            editAction() {
                if(!this.currentRow) {
                    this.closeContextMenu();
                    return;
                }
                @this.call('action', {action: 'edit', id: this.currentRow});
                this.closeContextMenu();
            },
            customAction(action) {
                if(!this.currentRow) {
                    this.closeContextMenu();
                    return;
                }
                @this.call('action', {action: action, id: this.currentRow});
                this.closeContextMenu();
            }
}">

<div x-show="visible" x-ref="contextMenu"
    class="absolute z-10 py-2 mt-1 w-48 bg-white rounded-md ring-1 ring-black ring-opacity-5 shadow-lg dark:bg-gray-800"
    @click.away="closeContextMenu" x-cloak>

    @can('update', $model)
    <a href="#"
        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
        @click="editAction">
        <div class="flex justify-start items-center space-x-2">
            <div class="shrink-0">
                <x-aura::icon icon="edit" size="xs" />
            </div>
            <span class="text-sm font-medium">{{ __('Edit') }}</span>
        </div>
    </a>
    @endcan

    @can('view', $model)
    <a href="#"
        class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
        @click="viewAction">
        <div class="flex justify-start items-center space-x-2">
            <div class="shrink-0">
                <x-aura::icon icon="view" size="xs" />
            </div>
            <span class="text-sm font-medium">{{ __('View') }}</span>
        </div>
    </a>
    @endcan

    {{-- @if(count($model->getBulkActions()))
    <div class="border-t border-gray-100 dark:border-gray-700"></div>

    @foreach($model->getBulkActions() as $action => $label)

    <button @click="customAction('{{ $action }}')"
        class="flex justify-start px-4 py-2 w-full text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
        @if(is_array($label))
        <div class="flex flex-col {{ $label['class'] ?? ''}}">
            <div class="flex justify-start items-center space-x-2">
                <div class="shrink-0">
                    {!! $label['icon'] ?? '' !!}
                    @if(optional($label)['icon-view'])
                    @include($label['icon-view'])
                    @endif
                </div>
                <span>{{ $label['label'] ?? '' }}
                    @if(optional($label)['description'])
                    <span
                        class="inline-block">{{ $label['description'] ?? '' }}</span>
                    @endif
                </span>
            </div>

        </div>
        @else
        {{ $label }}
        @endif
    </button>
    @endforeach

    @endif --}}

</div>
 @else
<div>
@endif
