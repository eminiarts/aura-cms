@props([
'header' => null,
'footer' => null,
'slug'
])

<div class="mx-auto max-w-8xl" wire:key="table-index-{{ str()->random(4) }}" x-data="{
                selected: @entangle('selected').defer,
                rows: @js($this->rows->pluck('id')->toArray()), 
                lastSelectedId: null,
                total: @js($this->rows->total()),
                selectPage: false,
                selectAll: @entangle('selectAll'),

                init() {
                      Livewire.on('selectedRows', (updatedSelected) => {
                            this.selected = updatedSelected;
                        });
                },
                selectAllRows() {
                    this.$nextTick(() => {
                        if (this.selectPage) {
                            this.selected = this.rows.slice();
                        } else {
                            this.selected = [];
                        }
                    });
                },
                resetBulk() {
                    this.selected = [];
                    this.selectPage = false;
                    this.selectAll = false;
                },
                toggleRow(event, id) {
                    this.$nextTick(() => {
                        // Check if shift key was pressed and last selected id exists
                        if (event.shiftKey && this.lastSelectedId !== null) {
                            // Get the indexes of the current and last selected rows
                            const lastIndex = this.rows.indexOf(this.lastSelectedId);
                            const currentIndex = this.rows.indexOf(id);

                            // Determine the start and end indexes of the rows to be selected
                            const start = Math.min(lastIndex, currentIndex);
                            const end = Math.max(lastIndex, currentIndex);

                            // If the current row is not already selected, remove all rows between start and end
                            if (!this.selected.includes(id.toString())) {
                                this.selected = this.selected.filter(row => !this.rows.slice(start, end + 1).map(item => item.toString()).includes(row.toString()));
                            }
                            // Otherwise, add all rows between start and end
                            else {
                                this.selected = [...this.selected, ...this.rows.slice(start, end + 1)].map(item => item.toString());

                                // Remove duplicates from the selected rows
                                this.selected = this.selected.filter((item, index) => this.selected.indexOf(item) === index);
                            }
                        }

                        this.lastSelectedId = id;
                    });
                }
            }">

    <div>
        @include('aura::components.table.bulk-select-row')
    </div>

    @include($this->model->tableView())

    {{-- @dump($this->model->tableView()) --}}

     {{-- @include($this->model->tableView()) --}}
    
</div>
