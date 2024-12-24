@props(['cols'])

@php
    $colSpan = match($cols) {
        'full' => 'col-span-12',
        '6' => 'col-span-6',
        '4' => 'col-span-4',
        default => 'col-span-12'
    };
@endphp

<div {{ $attributes->merge(['class' => "{$colSpan} rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10"]) }}>
    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Quick Actions</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Common tasks and actions</p>
            </div>
            <div class="p-2 rounded-lg bg-primary-50 dark:bg-primary-900/50">
                <svg class="size-6 text-primary-600 dark:text-primary-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" color="currentColor" fill="none">
    <path d="M8.62814 12.6736H8.16918C6.68545 12.6736 5.94358 12.6736 5.62736 12.1844C5.31114 11.6953 5.61244 11.0138 6.21504 9.65083L8.02668 5.55323C8.57457 4.314 8.84852 3.69438 9.37997 3.34719C9.91142 3 10.5859 3 11.935 3H14.0244C15.6632 3 16.4826 3 16.7916 3.53535C17.1007 4.0707 16.6942 4.78588 15.8811 6.21623L14.8092 8.10188C14.405 8.81295 14.2029 9.16849 14.2057 9.45952C14.2094 9.83775 14.4105 10.1862 14.7354 10.377C14.9854 10.5239 15.3927 10.5239 16.2074 10.5239C17.2373 10.5239 17.7523 10.5239 18.0205 10.7022C18.3689 10.9338 18.5513 11.3482 18.4874 11.7632C18.4382 12.0826 18.0918 12.4656 17.399 13.2317L11.8639 19.3523C10.7767 20.5545 10.2331 21.1556 9.86807 20.9654C9.50303 20.7751 9.67833 19.9822 10.0289 18.3962L10.7157 15.2896C10.9826 14.082 11.1161 13.4782 10.7951 13.0759C10.4741 12.6736 9.85877 12.6736 8.62814 12.6736Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
</svg>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <button onclick="Livewire.dispatch('openModal', { component: 'aura::create-resource' })" class="flex items-center p-4 text-left rounded-lg transition group hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <div class="p-2 mr-4 bg-green-50 rounded-lg shrink-0 dark:bg-green-900/50 group-hover:bg-green-100 dark:group-hover:bg-green-900/70">

                    <svg class="text-green-600 size-6 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"  color="currentColor" fill="none">
    <path d="M12 22C11.1818 22 10.4002 21.6646 8.83693 20.9939C4.94564 19.3243 3 18.4895 3 17.0853L3 7.7475M12 22C12.8182 22 13.5998 21.6646 15.1631 20.9939C19.0544 19.3243 21 18.4895 21 17.0853V7.7475M12 22L12 12.1707M21 7.7475C21 8.35125 20.1984 8.7325 18.5953 9.495L15.6741 10.8844C13.8712 11.7419 12.9697 12.1707 12 12.1707M21 7.7475C21 7.14376 20.1984 6.7625 18.5953 6M3 7.7475C3 8.35125 3.80157 8.7325 5.40472 9.495L8.32592 10.8844C10.1288 11.7419 11.0303 12.1707 12 12.1707M3 7.7475C3 7.14376 3.80157 6.7625 5.40472 6M6.33203 13.311L8.32591 14.2594" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
    <path d="M12 2V4M16 3L14.5 5M8 3L9.5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
</svg>
                </div>
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-gray-900 dark:text-white">Create Resource</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Add a new resource to your site</span>
                </div>
            </button>

            <a href="{{ route('aura.settings') }}" class="flex items-center p-4 rounded-lg transition group hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <div class="p-2 mr-4 bg-pink-50 rounded-lg shrink-0 dark:bg-pink-900/50 group-hover:bg-pink-100 dark:group-hover:bg-pink-900/70">
                    <svg class="text-pink-600 size-6 dark:text-pink-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" color="currentColor" fill="none">
    <path d="M2.5 12C2.5 7.52166 2.5 5.28249 3.89124 3.89124C5.28249 2.5 7.52166 2.5 12 2.5C16.4783 2.5 18.7175 2.5 20.1088 3.89124C21.5 5.28249 21.5 7.52166 21.5 12C21.5 16.4783 21.5 18.7175 20.1088 20.1088C18.7175 21.5 16.4783 21.5 12 21.5C7.52166 21.5 5.28249 21.5 3.89124 20.1088C2.5 18.7175 2.5 16.4783 2.5 12Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
    <path d="M8.5 10C7.67157 10 7 9.32843 7 8.5C7 7.67157 7.67157 7 8.5 7C9.32843 7 10 7.67157 10 8.5C10 9.32843 9.32843 10 8.5 10Z" stroke="currentColor" stroke-width="1.5" />
    <path d="M15.5 17C16.3284 17 17 16.3284 17 15.5C17 14.6716 16.3284 14 15.5 14C14.6716 14 14 14.6716 14 15.5C14 16.3284 14.6716 17 15.5 17Z" stroke="currentColor" stroke-width="1.5" />
    <path d="M10 8.5L17 8.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
    <path d="M14 15.5L7 15.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
</svg>
                </div>
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-gray-900 dark:text-white">Settings</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Configure your site settings</span>
                </div>
            </a>

            <a href="{{ route('aura.team.edit', ['id' => auth()->user()->current_team_id]) }}" class="flex items-center p-4 rounded-lg transition group hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <div class="p-2 mr-4 bg-blue-50 rounded-lg shrink-0 dark:bg-blue-900/50 group-hover:bg-blue-100 dark:group-hover:bg-blue-900/70">

                    <svg class="text-blue-600 size-6 dark:text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" color="currentColor" fill="none">
    <path d="M13 7C13 9.20914 11.2091 11 9 11C6.79086 11 5 9.20914 5 7C5 4.79086 6.79086 3 9 3C11.2091 3 13 4.79086 13 7Z" stroke="currentColor" stroke-width="1.5" />
    <path d="M15 11C17.2091 11 19 9.20914 19 7C19 4.79086 17.2091 3 15 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
    <path d="M11 14H7C4.23858 14 2 16.2386 2 19C2 20.1046 2.89543 21 4 21H14C15.1046 21 16 20.1046 16 19C16 16.2386 13.7614 14 11 14Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
    <path d="M17 14C19.7614 14 22 16.2386 22 19C22 20.1046 21.1046 21 20 21H18.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
</svg>
                </div>
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-gray-900 dark:text-white">Edit Team</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Manage team settings</span>
                </div>
            </a>

            <a href="{{ route('aura.plugins') }}" class="flex items-center p-4 rounded-lg transition group hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <div class="p-2 mr-4 bg-purple-50 rounded-lg shrink-0 dark:bg-purple-900/50 group-hover:bg-purple-100 dark:group-hover:bg-purple-900/70">
                    <svg class="text-purple-600 size-6 dark:text-purple-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" color="currentColor" fill="none">
    <path d="M12.828 6.00096C12.9388 5.68791 12.999 5.35099 12.999 5C12.999 3.34315 11.6559 2 9.99904 2C8.34219 2 6.99904 3.34315 6.99904 5C6.99904 5.35099 7.05932 5.68791 7.17008 6.00096C4.88532 6.0093 3.66601 6.09039 2.87772 6.87868C2.08951 7.66689 2.00836 8.88603 2 11.1704C2.31251 11.06 2.64876 11 2.99904 11C4.6559 11 5.99904 12.3431 5.99904 14C5.99904 15.6569 4.6559 17 2.99904 17C2.64876 17 2.31251 16.94 2 16.8296C2.00836 19.114 2.08951 20.3331 2.87772 21.1213C3.66593 21.9095 4.88508 21.9907 7.16941 21.999C7.05908 21.6865 6.99904 21.3503 6.99904 21C6.99904 19.3431 8.34219 18 9.99904 18C11.6559 18 12.999 19.3431 12.999 21C12.999 21.3503 12.939 21.6865 12.8287 21.999C15.113 21.9907 16.3322 21.9095 17.1204 21.1213C17.9086 20.333 17.9897 19.1137 17.9981 16.829C18.3111 16.9397 18.648 17 18.999 17C20.6559 17 21.999 15.6569 21.999 14C21.999 12.3431 20.6559 11 18.999 11C18.648 11 18.3111 11.0603 17.9981 11.171C17.9897 8.88627 17.9086 7.66697 17.1204 6.87868C16.3321 6.09039 15.1128 6.0093 12.828 6.00096Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
</svg>
                </div>
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-gray-900 dark:text-white">Plugins</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Browse and manage plugins</span>
                </div>
            </a>
        </div>
    </div>
</div>
