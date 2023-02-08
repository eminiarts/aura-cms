<div x-data="{open: false}">
<div x-data="{ selected: 'grid' }">
  <div class="flex items-center h-20">
    <h3 class="pr-4 text-2xl font-medium leading-6 text-gray-900">Media labrary</h3>
    <button x-on:click="open = !open" type="button" class="p-2 text-sm bg-white border rounded-sm shadow-sm">Add New</button>
  </div>
<div class="flex items-center h-12 mt-5 mb-5 bg-white border  justify center">

    <div class="flex items-center p-4 justify center">

    <button type="button" x-on:click=" selected = 'list' " >
      <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 cursor-pointer" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
      </svg>
    </button>

      <div class="ml-4">

       <button type="button" x-on:click=" selected = 'grid' ">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 pt-1 pl-4 text-black cursor-pointer hover:fill-current hover:text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
      </svg>
    </button>

      </div>

      <div class="ml-4">
        <select id="media-items" name="media-items" autocomplete="media-items" class="block w-48 h-10 bg-white border border-gray-500/30 rounded-sm cursor-pointer focus:outline-none focus:ring-gray-500 focus:border-gray-500 sm:text-sm">
          <option>All media items</option>
          <option>Images</option>
          <option>Audio</option>
          <option>Video</option>
          <option>Documents</option>
          <option>Spreadsheets</option>
          <option>Archives</option>
          <option>Unattaches</option>
          <option>Mine</option>
        </select>
      </div>

       <div class="ml-4">
        <select id="dates" name="dates" autocomplete="dates" class="block w-48 h-10 bg-white border border-gray-500/30 rounded-sm cursor-pointer focus:outline-none focus:ring-gray-500 focus:border-gray-500 sm:text-sm">
          <option>All dates</option>
          <option>Images</option>
          <option>Audio</option>
          <option>Video</option>
          <option>Documents</option>
          <option>Spreadsheets</option>
          <option>Archives</option>
          <option>Unattaches</option>
          <option>Mine</option>
        </select>
      </div>

      <button class="flex items-center justify-center h-10 p-2 ml-2 border">Filter</button>

    </div>
  </div>
  <div>
    @livewire('image-upload')
  </div>

   </div>
 </div>




{{--

    <div class="px-4 py-8 mx-auto max-w-7xl sm:px-6 lg:px-8">

  <ul role="list" class="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 sm:gap-x-6 lg:grid-cols-4 xl:gap-x-8">

      <li class="relative">
        <div class="block w-full overflow-hidden bg-gray-100 rounded-lg group aspect-w-10 aspect-h-7 focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-offset-gray-100 focus-within:ring-primary-500">
          <img src="https://images.unsplash.com/photo-1582053433976-25c00369fc93?ixid=MXwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHw%3D&amp;ixlib=rb-1.2.1&amp;auto=format&amp;fit=crop&amp;w=512&amp;q=80" alt="" class="object-cover pointer-events-none group-hover:opacity-75">
          <button type="button" class="absolute inset-0 focus:outline-none">
            <span class="sr-only">View details for IMG_4985.HEIC</span>
          </button>
        </div>
        <p class="block mt-2 text-sm font-medium text-gray-900 truncate pointer-events-none">IMG_4985.HEIC</p>
        <p class="block text-sm font-medium text-gray-500 pointer-events-none">3.9 MB</p>
      </li>

      <li class="relative">
        <div class="block w-full overflow-hidden bg-gray-100 rounded-lg group aspect-w-10 aspect-h-7 focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-offset-gray-100 focus-within:ring-primary-500">
          <img src="https://images.unsplash.com/photo-1614926857083-7be149266cda?ixlib=rb-1.2.1&amp;ixid=MXwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHw%3D&amp;auto=format&amp;fit=crop&amp;w=512&amp;q=80" alt="" class="object-cover pointer-events-none group-hover:opacity-75">
          <button type="button" class="absolute inset-0 focus:outline-none">
            <span class="sr-only">View details for IMG_5214.HEIC</span>
          </button>
        </div>
        <p class="block mt-2 text-sm font-medium text-gray-900 truncate pointer-events-none">IMG_5214.HEIC</p>
        <p class="block text-sm font-medium text-gray-500 pointer-events-none">4 MB</p>
      </li>

      <li class="relative">
        <div class="block w-full overflow-hidden bg-gray-100 rounded-lg group aspect-w-10 aspect-h-7 focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-offset-gray-100 focus-within:ring-primary-500">
          <img src="https://images.unsplash.com/photo-1614705827065-62c3dc488f40?ixid=MXwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHw%3D&amp;ixlib=rb-1.2.1&amp;auto=format&amp;fit=crop&amp;w=512&amp;q=80" alt="" class="object-cover pointer-events-none group-hover:opacity-75">
          <button type="button" class="absolute inset-0 focus:outline-none">
            <span class="sr-only">View details for IMG_3851.HEIC</span>
          </button>
        </div>
        <p class="block mt-2 text-sm font-medium text-gray-900 truncate pointer-events-none">IMG_3851.HEIC</p>
        <p class="block text-sm font-medium text-gray-500 pointer-events-none">3.8 MB</p>
      </li>

      <li class="relative">
        <div class="block w-full overflow-hidden bg-gray-100 rounded-lg group aspect-w-10 aspect-h-7 focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-offset-gray-100 focus-within:ring-primary-500">
          <img src="https://images.unsplash.com/photo-1441974231531-c6227db76b6e?ixid=MXwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHw%3D&amp;ixlib=rb-1.2.1&amp;auto=format&amp;fit=crop&amp;w=512&amp;q=80" alt="" class="object-cover pointer-events-none group-hover:opacity-75">
          <button type="button" class="absolute inset-0 focus:outline-none">
            <span class="sr-only">View details for IMG_4278.HEIC</span>
          </button>
        </div>
        <p class="block mt-2 text-sm font-medium text-gray-900 truncate pointer-events-none">IMG_4278.HEIC</p>
        <p class="block text-sm font-medium text-gray-500 pointer-events-none">4.1 MB</p>
      </li>

      <li class="relative">
        <div class="block w-full overflow-hidden bg-gray-100 rounded-lg group aspect-w-10 aspect-h-7 focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-offset-gray-100 focus-within:ring-primary-500">
          <img src="https://images.unsplash.com/photo-1586348943529-beaae6c28db9?ixid=MXwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHw%3D&amp;ixlib=rb-1.2.1&amp;auto=format&amp;fit=crop&amp;w=512&amp;q=80" alt="" class="object-cover pointer-events-none group-hover:opacity-75">
          <button type="button" class="absolute inset-0 focus:outline-none">
            <span class="sr-only">View details for IMG_6842.HEIC</span>
          </button>
        </div>
        <p class="block mt-2 text-sm font-medium text-gray-900 truncate pointer-events-none">IMG_6842.HEIC</p>
        <p class="block text-sm font-medium text-gray-500 pointer-events-none">4 MB</p>
      </li>

      <li class="relative">
        <div class="block w-full overflow-hidden bg-gray-100 rounded-lg group aspect-w-10 aspect-h-7 focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-offset-gray-100 focus-within:ring-primary-500">
          <img src="https://images.unsplash.com/photo-1497436072909-60f360e1d4b1?ixlib=rb-1.2.1&amp;ixid=MXwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHw%3D&amp;auto=format&amp;fit=crop&amp;w=512&amp;q=80" alt="" class="object-cover pointer-events-none group-hover:opacity-75">
          <button type="button" class="absolute inset-0 focus:outline-none">
            <span class="sr-only">View details for IMG_3284.HEIC</span>
          </button>
        </div>
        <p class="block mt-2 text-sm font-medium text-gray-900 truncate pointer-events-none">IMG_3284.HEIC</p>
        <p class="block text-sm font-medium text-gray-500 pointer-events-none">3.9 MB</p>
      </li>

      <li class="relative">
        <div class="block w-full overflow-hidden bg-gray-100 rounded-lg group aspect-w-10 aspect-h-7 focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-offset-gray-100 focus-within:ring-primary-500">
          <img src="https://images.unsplash.com/photo-1547036967-23d11aacaee0?ixid=MXwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHw%3D&amp;ixlib=rb-1.2.1&amp;auto=format&amp;fit=crop&amp;w=512&amp;q=80" alt="" class="object-cover pointer-events-none group-hover:opacity-75">
          <button type="button" class="absolute inset-0 focus:outline-none">
            <span class="sr-only">View details for IMG_4841.HEIC</span>
          </button>
        </div>
        <p class="block mt-2 text-sm font-medium text-gray-900 truncate pointer-events-none">IMG_4841.HEIC</p>
        <p class="block text-sm font-medium text-gray-500 pointer-events-none">3.8 MB</p>
      </li>

      <li class="relative">
        <div class="block w-full overflow-hidden bg-gray-100 rounded-lg group aspect-w-10 aspect-h-7 focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-offset-gray-100 focus-within:ring-primary-500">
          <img src="https://images.unsplash.com/photo-1492724724894-7464c27d0ceb?ixid=MXwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHw%3D&amp;ixlib=rb-1.2.1&amp;auto=format&amp;fit=crop&amp;w=512&amp;q=80" alt="" class="object-cover pointer-events-none group-hover:opacity-75">
          <button type="button" class="absolute inset-0 focus:outline-none">
            <span class="sr-only">View details for IMG_5644.HEIC</span>
          </button>
        </div>
        <p class="block mt-2 text-sm font-medium text-gray-900 truncate pointer-events-none">IMG_5644.HEIC</p>
        <p class="block text-sm font-medium text-gray-500 pointer-events-none">4 MB</p>
      </li>

  </ul>
    </div> --}}
