@props([
  'id' => '',
])


<div class="relative" :id="$id">
  <div class="block w-full overflow-hidden bg-gray-100 rounded-lg group aspect-w-10 aspect-h-7 focus-within:ring-2 focus-within:ring-primary-500 focus-within:ring-offset-2 focus-within:ring-offset-gray-100">
    <img src="https://images.unsplash.com/photo-1582053433976-25c00369fc93?ixid=MXwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHw%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=512&q=80" alt="" class="object-cover pointer-events-none group-hover:opacity-75">
    <button type="button" class="absolute inset-0 focus:outline-none">
      <span class="sr-only">View details for IMG_4985.HEIC</span>
    </button>
  </div>
  <p class="block mt-2 text-sm font-medium text-gray-900 truncate pointer-events-none">IMG_4985.HEIC</p>
  <p class="block text-sm font-medium text-gray-500 pointer-events-none">3.9 MB</p>
</div>
