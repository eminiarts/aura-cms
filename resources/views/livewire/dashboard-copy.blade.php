<x-aura::dashboard>
    <x-aura::dashboard.welcome cols="full" />

    <x-aura::dashboard.docs cols="6"/>

    <x-aura::dashboard.quick-actions cols="6"/>

    <x-aura::dashboard.users cols="4"/>
    <x-aura::dashboard.media cols="4"/>
    <x-aura::dashboard.recent-activity cols="4"/>


</x-aura::dashboard>


<div class="space-y-6">
    <!-- Welcome Card -->
    <div class="aura-card">
        <h2 class="mb-4 text-2xl font-bold">{{ $welcomeMessage }}</h2>
        <div class="max-w-none">
            <p>Get started with Aura CMS by exploring these resources:</p>
            <div class="grid grid-cols-1 gap-4 mt-4 md:grid-cols-2">
                <a href="/docs/getting-started" class="aura-card">
                    <h3 class="font-semibold">📚 Getting Started Guide</h3>
                    <p class="text-sm">Learn the basics of Aura CMS</p>
                </a>
                <a href="/docs/resources" class="aura-card">
                    <h3 class="font-semibold">🎯 Resource Management</h3>
                    <p class="text-sm">Create and manage your content</p>
                </a>
            </div>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
        <div class="aura-card">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold">Total Posts</h3>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9.5a2.5 2.5 0 00-2.5-2.5H15"></path>
                </svg>
            </div>
            <p class="mt-2 text-3xl font-bold">{{ $totalPosts }}</p>
        </div>

        <div class="aura-card">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold">Active Users</h3>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </div>
            <p class="mt-2 text-3xl font-bold">{{ $totalUsers }}</p>
        </div>

        <div class="aura-card">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold">Media Files</h3>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
            </div>
            <p class="mt-2 text-3xl font-bold">{{ $totalMedia }}</p>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="aura-card">
        <h3 class="mb-4 text-lg font-semibold">Quick Actions</h3>
        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            @foreach($quickLinks as $link)
            <a href="{{ $link['url'] }}" class="flex items-center p-3 rounded-lg transition hover:bg-gray-50">
                <span class="mr-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                </span>
                {{ $link['label'] }}
            </a>
            @endforeach
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="aura-card">
        <h3 class="mb-4 text-lg font-semibold">Recent Activity</h3>
        @if(count($recentActivity) > 0)
            <div class="space-y-4">
                @foreach($recentActivity as $activity)
                    <div class="flex justify-between items-center aura-card">
                        <div>
                            <h4 class="font-medium">{{ $activity->title }}</h4>
                            <p class="text-sm">Status: {{ $activity->status }}</p>
                        </div>
                        <span class="text-sm">
                            {{ \Carbon\Carbon::parse($activity->updated_at)->diffForHumans() }}
                        </span>
                    </div>
                @endforeach
            </div>
        @else
            <p class="py-4 text-center">No recent activity to display</p>
        @endif
    </div>
</div>
