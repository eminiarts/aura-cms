@props([
'header' => null,
'footer' => null,
'slug'
])

<div class="mx-auto max-w-8xl" wire:key="table-index" x-data="{
    selected: @entangle('selected').defer,
    rows: @js($this->rows->pluck('id')->toArray()), 
    lastSelectedId: null,
    total: @js($this->rows->total()),
    selectPage: false,
    currentPage: @entangle('page'),
    selectAll: @entangle('selectAll').defer,
    loading: false,
    
    init() {
        Livewire.on('selectedRows', (updatedSelected) => {
            this.selected = updatedSelected;
        });

        console.log('init table');
        
        if(this.selectAll) {
            this.selectPage = true;
        }

        // watch rows for changes
        this.$watch('rows', (rows) => {
           // Check if rows (array of ids) is included in this.selected. if so, set this.selectPage to true
           console.log('watch rows', rows, this.selected)

              //this.selectPage = rows.every(row => this.selected.includes(row.toString()));
        });

        this.$watch('currentPage', (rows) => {
           console.log('currentPage', this.currentPage)
           console.log('rows', this.rows)
           console.log('selected', this.selected)

           /// check if this.rows are in this.selected. if so, set this.selectPage to true
            this.selectPage = this.rows.every(row => this.selected.includes(row));

            console.log('every', this.rows.every(row => this.selected.includes(row)))
           

        });
    },

    selectCurrentPage() {
         this.$nextTick(() => {
                if (this.selectPage) {
                    this.selected = this.rows.slice();
                } else {
                    this.selected = [];
                }
            });
    },

    selectAllRows: async function () {

            this.loading = true

            this.selected = await $wire.getAllTableRows()
            this.selectAll = true

            console.log(this.selected, 'selected')

            this.loading = false
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

            // Select All
            if (this.selected.length === this.total) {
                this.selectAll = true;
            } else {
                this.selectAll = false;
            }

            // Select Page
            if (!this.selected.includes(id.toString())) {
                this.selectPage = false;

                console.log('deselect', id)
            }

        });
    }
}">

{{-- @dump($selected)
@dump($selectAll) --}}

<div wire:key="table-bulk-select">
    @include('aura::components.table.bulk-select-row')
</div>

@include($this->model->tableView())

</div>
