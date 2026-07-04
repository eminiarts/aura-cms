<div>
    <div class="my-8">
        <div class="p-4 text-sm text-gray-700 bg-gray-100 border border-gray-200 rounded-lg">
            Plugins are managed via Composer. Run <code class="px-2 py-1 text-xs font-semibold text-gray-800 bg-white rounded">composer require vendor/plugin</code> to install or update plugins.
        </div>
    </div>

    <table class="w-full table-auto">
        <thead>
            <tr class="text-gray-700 bg-gray-200">
                <th class="px-4 py-2">Package</th>
                <th class="px-4 py-2">Version</th>
                <th class="px-4 py-2">Description</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($installedPackages as $name => $package)
                <tr
                    @if (in_array('laravel', $package['keywords']))
                        class="bg-gray-50"
                    @endif
                >
                    <td class="px-4 py-2 border">{{ $name }}</td>
                    <td class="px-4 py-2 border">
                        <span class="inline-block px-3 py-1 mr-2 text-sm font-semibold rounded-full text-primary-800 bg-primary-100">{{ $package['version'] }}</span>
                    </td>
                    <td class="px-4 py-2 border text-sm text-gray-700">{{ $package['description'] ?: 'No description available.' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
