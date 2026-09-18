@if($this->settings['actions'])
@if($model->getContextMenu())
<div class="table-context-menu" @contextmenu="openContextMenu($event)" @click.away="closeContextMenu" @keydown.escape="closeContextMenu" x-data="{
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
    class="fixed z-10 p-1 mt-1 w-48 bg-white rounded-lg ring-1 shadow-lg ring-gray-950/10 dark:bg-gray-800 dark:ring-white/10"
    @click.away="closeContextMenu" x-cloak>

    @can('update', $model)
    <a href="#"
        class="block px-3 py-1.5 text-sm text-gray-700 rounded-md transition-colors duration-150 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5"
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
        class="block px-3 py-1.5 text-sm font-medium text-gray-700 rounded-md transition-colors duration-150 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5"
        @click="viewAction">
        <div class="flex justify-start items-center space-x-2">
            <div class="shrink-0">
                <x-aura::icon icon="view" size="xs" />
            </div>
            <span class="text-sm font-medium">{{ __('View') }}</span>
        </div>
    </a>
    @endcan

</div>
 @else
<div>
@endif
@endif
