<div>
    
    <div class="my-8">
        <button wire:click="runComposerUpdate">Run composer update</button>
        
        <div>
            {{ $output }}
        </div>
    </div>
    
    
    <table class="w-full table-auto">
        <thead>
            <tr class="text-gray-700 bg-gray-200">
                <th class="px-4 py-2">Package</th>
                <th class="px-4 py-2">Version</th>
                <th class="px-4 py-2">Keywords</th>
                <th class="px-4 py-2">Update</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($installedPackages as $name => $package)
            <tr
            @if (in_array('laravel', $package['keywords']))
            class="bg-gray-50"
            @endif
            x-data="{
                name: '{{ $name }}',
                version: '{{ $package['version'] }}',
                loading: false,
                checkUpdates () {
                    this.loading = true;
                    
                    $wire.getPackageUpdates(this.name)
                    .then(() => {
                        this.loading = false;
                    })
                    
                },
                
            }"
            >
            <td class="px-4 py-2 border">{{ $name }}</td>
            <td class="px-4 py-2 border">
                <span class="inline-block px-3 py-1 mr-2 text-sm font-semibold rounded-full text-primary-800 bg-primary-100">{{ $package['version'] }}</span>
            </td>
            <td class="px-4 py-2 border">
                @foreach ($package['keywords'] as $keyword)
                <span class="inline-block px-3 py-1 mr-2 text-xs font-semibold text-gray-700 bg-gray-200 rounded-full">{{ $keyword }}</span>
                @endforeach
            </td>
            <td class="px-4 py-2 border">
                
                @if (!optional($latestVersions)[$name])
                <button
                @click="checkUpdates()"
                {{-- wire:click="getPackageUpdates('{{ $name }}')" --}}
                class="px-3 py-1 text-xs font-semibold text-gray-700 bg-gray-200 rounded-full"
                :class="{
                    'bg-gray-200': !loading,
                    'bg-gray-400': loading,
                }"
                >
                <span class="flex" x-show="loading">
                    <svg class="w-4 h-4 mr-1 text-gray-500 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M10 0a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm0 18a8 8 0 1 1 0-16 8 8 0 0 1 0 16z"/></svg>
                    <span>Checking...</span>
                </span>
                <span class="whitespace-nowrap" x-show="!loading">
                    Check for updates
                </span>
            </button>
            @elseif (version_compare($latestVersions[$name], $package['version'], '>'))
            <span class="inline-flex items-center px-3 py-1 mr-2 text-xs font-semibold text-red-700 bg-red-200 rounded-full">
                <!-- svg x -->
                <svg class="w-4 h-4 mr-1 text-red-500 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
                
                <span>Installed: {{ $package['version'] }}</span>
            </span>
            
            <span class="inline-flex items-center px-3 py-1 mr-2 text-xs font-semibold text-green-700 bg-green-200 rounded-full" wire:click="updatePackage('{{ $name }}', '{{ $latestVersions[$name] }}')">
                <!-- svg checkmark -->
                <svg class="w-4 h-4 mr-1 text-green-500 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M0 11l2-2 5 5L18 3l2 2L7 18z"/></svg>
                
                <span>Update to Latest: {{ $latestVersions[$name] }}</span>
            </span>
            @else
            <span class="inline-flex items-center px-3 py-1 mr-2 text-xs font-semibold text-green-700 bg-green-200 rounded-full">
                <!-- svg checkmark -->
                <svg class="w-4 h-4 mr-1 text-green-500 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M0 11l2-2 5 5L18 3l2 2L7 18z"/></svg>
                
                <span>{{ $latestVersions[$name] }}</span>
            </span>
            @endif
        </td>
    </tr>
    @endforeach
</tbody>
</table>
</div>
