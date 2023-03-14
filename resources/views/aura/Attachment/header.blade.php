<div class="flex items-center justify-between mt-6">
    <div>
        <h1 class="text-3xl font-semibold">Custom {{ $model->pluralName() }}</h1>

        @if($this->parent)
        <span class="text-primary-500">from {{ $this->parent->name }}</span>
        @endif
        </h3>
    </div>

    <div>
        <div>
           <label for="file-upload">
                         <p class="mb-2 text-sm text-gray-500 dark:text-gray-400"><span class="font-semibold">Click to
                                upload</span> or drag and drop</p>
                    
                    <input type="file" id="file-upload" multiple @change="handleFileSelect" class="hidden"
                        wire:model.defer="media" />
                        </label>

            
        </div>
    </div>
</div>
