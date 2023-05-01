<div>
    <template x-if="selectPage">
        <div class="p-2 bg-primary-50 dark:bg-gray-900 dark:text-white text-gray-900 rounded-lg text-sm">
            <template x-if="!selectAll">
                <div>
                    <span>
                        You have selected
                        <strong x-text="selected.length"></strong>
                        <span x-text="selected.length === 1 ? 'row' : 'rows'"></span>.
                    </span>

                    <button x-on:click="selectAll=true" type="button"
                        class="ml-1 text-sm font-medium leading-5 text-gray-700 text-primary-600 underline transition duration-150 ease-in-out focus:outline-none focus:text-gray-800 focus:underline dark:text-white dark:hover:text-gray-400">
                        Select All
                    </button>

                    <button x-on:click="resetBulk();" type="button"
                        class="ml-1 text-sm font-medium leading-5 text-gray-700 text-primary-600 underline transition duration-150 ease-in-out focus:outline-none focus:text-gray-800 focus:underline dark:text-white dark:hover:text-gray-400">
                        Unselect All
                    </button>
                </div>
            </template>
            <template x-if="selectAll">
                <div>
                    <span>
                        You are currently selecting all
                        <strong x-text="total"></strong>
                        rows.
                    </span>

                    <button x-on:click="resetBulk()" type="button"
                        class="ml-1 text-sm font-medium leading-5 text-gray-700 text-primary-600 underline transition duration-150 ease-in-out focus:outline-none focus:text-gray-800 focus:underline dark:text-white dark:hover:text-gray-400">
                        Unselect All
                    </button>
                </div>
            </template>
        </div>
    </template>
</div>
