@if($this->model->getContextMenu())
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
    class="absolute z-10 w-48 py-2 mt-1 bg-white rounded-md shadow-lg dark:bg-gray-800 ring-1 ring-black ring-opacity-5"
    @click.away="closeContextMenu" x-cloak>

    @can('update', $this->model)
    <a href="#"
        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
        @click="editAction">
        <div class="flex items-center space-x-2">
            <div class="shrink-0">
                <x-aura::icon icon="edit" size="xs" />
            </div>
            <span class="text-sm font-medium">{{ __('Edit') }}</span>
        </div>
    </a>
    @endcan

    @can('view', $this->model)
    <a href="#"
        class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
        @click="viewAction">
        <div class="flex items-center space-x-2">
            <div class="shrink-0">
                <x-aura::icon icon="view" size="xs" />
            </div>
            <span class="text-sm font-medium">{{ __('View') }}</span>
        </div>
    </a>
    @endcan

    @if(count($this->model->getActions()))
    <div class="border-t border-gray-100 dark:border-gray-700"></div>
    @endif

    @foreach($this->model->getActions() as $action => $label)

    <button @click="customAction('{{ $action }}')"
        class="block w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
        @if(is_array($label))
        <div class="flex flex-col {{ $label['class'] ?? ''}}">
            <div class="flex items-center space-x-2">
                <div class="shrink-0">
                    {!! $label['icon'] ?? '' !!}
                    @if(optional($label)['icon-view'])
                    @include($label['icon-view'])
                    @endif
                </div>
                <span class="text-sm font-medium">{{ $label['label'] ?? '' }}
                    @if(optional($label)['description'])
                    <span
                        class="inline-block text-sm font-normal leading-tight text-gray-500">{{ $label['description'] ?? '' }}</span>
                    @endif
                </span>
            </div>

        </div>
        @else
        {{ $label }}
        @endif
    </button>
    @endforeach
</div>
 @else
<div>
@endif
