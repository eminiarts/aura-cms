<div class="w-full p-6 ">
    <div class="flex justify-between w-full">
        <div>
            <h2 class="text-lg font-semibold">Start with a Template</h2>
            <span class="text-sm text-gray-500">Flexible Templates to get you started</span>
        </div>
        <button onclick="$wire.dispatch('closeModal', 'choose-template')">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="25" fill="none">
                <path stroke="#000" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m18 6.5-12 12m0-12 12 12"/>
            </svg>
        </button>
    </div>
    <div class="grid justify-between w-full grid-flow-col space-x-8 auto-cols-max">
        <div class="flex flex-col items-center justify-between p-6 my-6 bg-gray-100 rounded-md">
            <div class="flex flex-col items-center my-6">
            <h3 class="text-lg font-semibold">Plain</h3>
            <span class="text-sm text-gray-500">Without Tabs and Panels</span>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-56 w-72" fill="none">
                <g filter="url(#a)"><path fill="#fff" d="M12 .5h269v196H12z"/><path fill="#F9FAFB" d="M35.636 23.844h220.403v157.691H35.636V23.845Z"/><path fill="#EFEFEF" d="M35.874 24.274h51.24v157.261h-51.24V24.274ZM92.824 37.206h22.274v4.283H92.824v-4.283ZM92.654 29.496h35.466v2.57H92.654v-2.57ZM92.654 49.776a2.621 2.621 0 0 1 2.62-2.62h152.488a2.621 2.621 0 0 1 2.621 2.62V174.23a2.621 2.621 0 0 1-2.621 2.621H95.275a2.62 2.62 0 0 1-2.621-2.621V49.776Z"/></g><defs><filter id="a" width="293" height="220" x="0" y=".5" color-interpolation-filters="sRGB" filterUnits="userSpaceOnUse"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" result="hardAlpha" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0"/><feMorphology in="SourceAlpha" radius="2" result="effect1_dropShadow_1596_94543"/><feOffset dy="4"/><feGaussianBlur stdDeviation="3"/><feColorMatrix values="0 0 0 0 0.0627451 0 0 0 0 0.0941176 0 0 0 0 0.156863 0 0 0 0.03 0"/><feBlend in2="BackgroundImageFix" result="effect1_dropShadow_1596_94543"/><feColorMatrix in="SourceAlpha" result="hardAlpha" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0"/><feMorphology in="SourceAlpha" radius="4" result="effect2_dropShadow_1596_94543"/><feOffset dy="12"/><feGaussianBlur stdDeviation="8"/><feColorMatrix values="0 0 0 0 0.0627451 0 0 0 0 0.0941176 0 0 0 0 0.156863 0 0 0 0.08 0"/><feBlend in2="effect1_dropShadow_1596_94543" result="effect2_dropShadow_1596_94543"/><feBlend in="SourceGraphic" in2="effect2_dropShadow_1596_94543" result="shape"/></filter></defs>
            </svg>

            <button class="flex items-center justify-center w-full py-3 text-white rounded-md bg-primary-600 hover:bg-primary-900">
                Choose Template
            </button>
        </div>

        <div class="flex flex-col items-center justify-between p-6 my-6 bg-gray-100 rounded-md">
            <div class="flex flex-col items-center my-6">
            <h3 class="text-lg font-semibold">Tabs</h3>
            <span class="text-sm text-gray-500">Use global Tabs to group Fields</span>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-56 w-72"  fill="none">
                <g filter="url(#a)"><path fill="#fff" d="M12.333.5h266v195h-266z"/><path fill="#F9FAFB" d="M35.769 22.28H254.69v157.554H35.769V22.279Z"/><path fill="#EFEFEF" d="M36.136 23.724H87v156.11H36.136V23.724ZM92.668 36.561h22.112v4.252H92.668v-4.252ZM92.5 28.907h35.206v2.552H92.499v-2.552ZM93.333 52.379h155.742v.85H93.333v-.85Z"/><path fill="#0019E4" d="M92.5 53.23h14.797v-.651H92.499v.65Z"/><path fill="#EFEFEF" d="M110.189 48.467h7.483v1.87h-7.483v-1.87Z"/><path fill="#0019E4" d="M96.157 48.467h7.483v1.87h-7.483v-1.87Z"/><path fill="#EFEFEF" d="M121.074 48.467h7.483v1.87h-7.483v-1.87ZM131.959 48.467h7.483v1.87h-7.483v-1.87ZM92.5 59.403A2.602 2.602 0 0 1 95.1 56.8h151.372a2.602 2.602 0 0 1 2.602 2.602v113.179a2.602 2.602 0 0 1-2.602 2.602H95.101a2.602 2.602 0 0 1-2.602-2.602V59.402Z"/></g><defs><filter id="a" width="290" height="219" x=".333" y=".5" color-interpolation-filters="sRGB" filterUnits="userSpaceOnUse"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" result="hardAlpha" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0"/><feMorphology in="SourceAlpha" radius="2" result="effect1_dropShadow_1596_94707"/><feOffset dy="4"/><feGaussianBlur stdDeviation="3"/><feColorMatrix values="0 0 0 0 0.0627451 0 0 0 0 0.0941176 0 0 0 0 0.156863 0 0 0 0.03 0"/><feBlend in2="BackgroundImageFix" result="effect1_dropShadow_1596_94707"/><feColorMatrix in="SourceAlpha" result="hardAlpha" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0"/><feMorphology in="SourceAlpha" radius="4" result="effect2_dropShadow_1596_94707"/><feOffset dy="12"/><feGaussianBlur stdDeviation="8"/><feColorMatrix values="0 0 0 0 0.0627451 0 0 0 0 0.0941176 0 0 0 0 0.156863 0 0 0 0.08 0"/><feBlend in2="effect1_dropShadow_1596_94707" result="effect2_dropShadow_1596_94707"/><feBlend in="SourceGraphic" in2="effect2_dropShadow_1596_94707" result="shape"/></filter></defs>
             </svg>

            <button class="flex items-center justify-center w-full py-3 text-white rounded-md bg-primary-600 hover:bg-primary-900">
                Choose Template
            </button>
        </div>

        <div class="flex flex-col items-center justify-between p-6 my-6 bg-gray-100 rounded-md">
            <div class="flex flex-col items-center my-6">
            <h3 class="text-lg font-semibold">Tabs and Panels</h3>
            <span class="text-sm text-gray-500">Complex Models require both</span>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-56 w-72"  fill="none">
                <g filter="url(#a)"><path fill="#fff" d="M12.667.5h265v192.559h-265z"/><path fill="#F9FAFB" d="M36.102 22.28h212.611v153.012H36.103V22.279Z"/><path fill="#EFEFEF" d="M36.46 23.682h49.398v151.61H36.46V23.682ZM91.362 36.15h21.474v4.129H91.362v-4.13ZM91.198 28.716h34.192v2.478H91.198v-2.478ZM92.007 51.51H243.26v.827H92.007v-.826Z"/><path fill="#0019E4" d="M91.198 52.337h14.371v-.632h-14.37v.632Z"/><path fill="#EFEFEF" d="M108.378 47.712h7.267v1.817h-7.267v-1.817Z"/><path fill="#0019E4" d="M94.75 47.712h7.268v1.817H94.75v-1.817Z"/><path fill="#EFEFEF" d="M118.949 47.712h7.268v1.817h-7.268v-1.817ZM129.52 47.712h7.268v1.817h-7.268v-1.817ZM91.198 58.333a2.527 2.527 0 0 1 2.527-2.527h147.008a2.526 2.526 0 0 1 2.526 2.527v39.443a2.527 2.527 0 0 1-2.526 2.527H93.725a2.527 2.527 0 0 1-2.527-2.527V58.333ZM91.198 109.107a2.527 2.527 0 0 1 2.527-2.527h147.008a2.527 2.527 0 0 1 2.526 2.527v39.444a2.526 2.526 0 0 1-2.526 2.526H93.725a2.526 2.526 0 0 1-2.527-2.526v-39.444Z"/></g><defs><filter id="a" width="289" height="216.559" x=".667" y=".5" color-interpolation-filters="sRGB" filterUnits="userSpaceOnUse"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" result="hardAlpha" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0"/><feMorphology in="SourceAlpha" radius="2" result="effect1_dropShadow_1596_94828"/><feOffset dy="4"/><feGaussianBlur stdDeviation="3"/><feColorMatrix values="0 0 0 0 0.0627451 0 0 0 0 0.0941176 0 0 0 0 0.156863 0 0 0 0.03 0"/><feBlend in2="BackgroundImageFix" result="effect1_dropShadow_1596_94828"/><feColorMatrix in="SourceAlpha" result="hardAlpha" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0"/><feMorphology in="SourceAlpha" radius="4" result="effect2_dropShadow_1596_94828"/><feOffset dy="12"/><feGaussianBlur stdDeviation="8"/><feColorMatrix values="0 0 0 0 0.0627451 0 0 0 0 0.0941176 0 0 0 0 0.156863 0 0 0 0.08 0"/><feBlend in2="effect1_dropShadow_1596_94828" result="effect2_dropShadow_1596_94828"/><feBlend in="SourceGraphic" in2="effect2_dropShadow_1596_94828" result="shape"/></filter></defs>
            </svg>

            <button class="flex items-center justify-center w-full py-3 text-white rounded-md bg-primary-600 hover:bg-primary-900">
                Choose Template
            </button>
        </div>
    </div>
</div>
