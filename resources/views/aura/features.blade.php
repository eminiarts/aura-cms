<div class="flex flex-col items-stretch md:flex-row flex-wrap">

                <div class="w-full md:w-1/3 px-3">
                    <div class="flex flex-col justify-between my-3 bg-white border border-gray-400/30 rounded-lg shadow-md dark:bg-gray-800 dark:border-gray-700 md:ml-3">
                    <div class="flex items-center justify-between p-6">
                        <div class="flex items-center space-x-2">

                        <svg xmlns="http://www.w3.org/2000/svg" width="49" height="48" fill="none"><rect width="48" height="48" x=".333" fill="#0088C5" rx="24"/><path stroke="#fff" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M29.115 27.358c-.27.306-.554.608-.851.905-4.296 4.296-9.678 5.88-12.021 3.536-1.607-1.606-1.368-4.641.326-7.775m2.32-3.299c.282-.32.579-.636.89-.947 4.295-4.295 9.677-5.878 12.02-3.535 1.608 1.607 1.367 4.645-.33 7.781my-3 mr-3 ml-0 md:ml-3.206-4.246c4.296 4.296 5.88 9.678 3.536 12.021-2.343 2.343-7.725.76-12.02-3.535-4.296-4.296-5.88-9.678-3.536-12.021 2.343-2.343 7.725-.76 12.02 3.535ZM25 24a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z"/></svg>
                        <div class="flex items-center">
                        <a href="#">
                            <h5 class="font-bold tracking-tight text-gray-900 dark:text-white">Teams</h5>
                        </a>
                        </div>

                        </div>

                        <x-input.toggle wire:model="config.teams" />

                    </div>
                    <div class="flex-1 px-6 py-4">
                    <p class="mb-3 text-sm font-normal text-gray-500 dark:text-gray-400">Do you have a multitenant application? Enable this Feature if you want to have teams in your application.</p>
                    </div>

                        <div class="flex justify-end px-6 py-4 border-t-2 border-gray-400/30">
                            <a href="#" class="inline-flex items-center font-semibold text-primary-600">
                            Docs

                        </a>
                        </div>

                </div>

                </div>

                <div class="w-full md:w-1/3 px-3">
                    <div class="flex flex-col justify-between my-3 bg-white border border-gray-400/30 rounded-lg shadow-md dark:bg-gray-800 dark:border-gray-700 md:ml-3">
                    <div class="flex items-center justify-between p-6">
                        <div class="flex items-center space-x-2">

                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="none"><rect width="48" height="48" fill="#000CEB" rx="24"/><path stroke="#fff" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M34 33v-2a4.002 4.002 0 0 0-3-3.874my-3 mr-3 ml-0 md:ml-3.5-11.835a4.001 4.001 0 0 1 0 7.418M29 33c0-1.864 0-2.796-.305-3.53a4 4 0 0 0-2.164-2.165C25.796 27 24.864 27 23 27h-3c-1.864 0-2.796 0-3.53.305a4 4 0 0 0-2.165 2.164C14 30.204 14 31.136 14 33m11.5-14a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z"/></svg>
                        <div class="flex items-center">
                        <a href="#">
                            <h5 class="font-bold tracking-tight text-gray-900 dark:text-white">Users and Roles</h5>
                        </a>
                        </div>

                        </div>
                        <x-input.toggle wire:model="config.users" />
                    </div>
                    <div class="flex-1 px-6 py-4">
                    <p class="mb-3 text-sm font-normal text-gray-500 dark:text-gray-400">Streamline software projects, sprints, and bug tracking.</p>
                    </div>

                        <div class="flex justify-end px-6 py-4 border-t-2 border-gray-400/30">
                            <a href="#" class="inline-flex items-center font-semibold text-primary-600">
                            Docs

                        </a>
                        </div>

                </div>

                </div>

                <div class="w-full md:w-1/3 px-3">
                    <div class="flex flex-col justify-between my-3 bg-white border border-gray-400/30 rounded-lg shadow-md dark:bg-gray-800 dark:border-gray-700 md:ml-3">
                    <div class="flex items-center justify-between p-6">
                        <div class="flex items-center space-x-2">

                        <svg xmlns="http://www.w3.org/2000/svg" width="49" height="48" fill="none"><rect width="48" height="48" x=".333" fill="#713BE8" rx="24"/><path stroke="#fff" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M31 33h1.01c.972 0 1.457 0 1.725-.203a1 1 0 0 0 .395-.737c.02-.335-.25-.74-.788-1.548l-3.01-4.515c-.446-.668-.668-1.002-.949-1.118a1 1 0 0 0-.766 0c-.28.116-.503.45-.948 1.118l-.744 1.116M31 33l-7.684-11.1c-.442-.638-.663-.957-.94-1.07a1 1 0 0 0-.752 0c-.276.113-.497.432-.94 1.07l-5.946 8.59c-.563.813-.844 1.22-.828 1.557a1 1 0 0 0 .391.747c.27.206.764.206 1.753.206H31Zm2-15a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                        <div class="flex items-center">
                        <a href="#">
                            <h5 class="font-bold tracking-tight text-gray-900 dark:text-white">Media Management</h5>
                        </a>
                        </div>

                        </div>
                        <x-input.toggle wire:model="config.media" />
                    </div>
                    <div class="flex-1 px-6 py-4">
                    <p class="mb-3 text-sm font-normal text-gray-500 dark:text-gray-400">Link pull requests and automate workflows.</p>
                    </div>

                        <div class="flex justify-end px-6 py-4 border-t-2 border-gray-400/30">
                            <a href="#" class="inline-flex items-center font-semibold text-primary-600">
                            Docs

                        </a>
                        </div>

                </div>

                </div>

                <div class="w-full md:w-1/3 px-3">
                    <div class="flex flex-col justify-between my-3 bg-white border border-gray-400/30 rounded-lg shadow-md dark:bg-gray-800 dark:border-gray-700 md:ml-3">
                    <div class="flex items-center justify-between p-6">
                        <div class="flex items-center space-x-2">

                        <svg xmlns="http://www.w3.org/2000/svg" width="49" height="48" fill="none"><rect width="48" height="48" x=".666" fill="#475576" rx="24"/><path stroke="#fff" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M27 23h-6m2 4h-2m8-8h-8m12-.2v10.4c0 1.68 0 2.52-.327 3.162a3 3 0 0 1-1.311 1.311C30.72 34 29.88 34 28.2 34h-6.4c-1.68 0-2.52 0-3.162-.327a3 3 0 0 1-1.311-1.311C17 31.72 17 30.88 17 29.2V18.8c0-1.68 0-2.52.327-3.162a3 3 0 0 1 1.311-1.311C19.28 14 20.12 14 21.8 14h6.4c1.68 0 2.52 0 3.162.327a3 3 0 0 1 1.311 1.311C33 16.28 33 17.12 33 18.8Z"/></svg>
                        <div class="flex items-center">
                        <a href="#">
                            <h5 class="font-bold tracking-tight text-gray-900 dark:text-white">Posts</h5>
                        </a>
                        </div>

                        </div>
                        <x-input.toggle wire:model="config.posts" />
                    </div>
                    <div class="flex-1 px-6 py-4">
                    <p class="mb-3 text-sm font-normal text-gray-500 dark:text-gray-400">Embed file previews in projects.</p>
                    </div>

                        <div class="flex justify-end px-6 py-4 border-t-2 border-gray-400/30">
                            <a href="#" class="inline-flex items-center font-semibold text-primary-600">
                            Docs

                        </a>
                        </div>

                </div>

                </div>

                <div class="w-full md:w-1/3 px-3">
                    <div class="flex flex-col justify-between my-3 bg-white border border-gray-400/30 rounded-lg shadow-md dark:bg-gray-800 dark:border-gray-700 md:ml-3">
                    <div class="flex items-center justify-between p-6">
                        <div class="flex items-center space-x-2">

                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="none"><rect width="48" height="48" fill="#E54920" rx="24"/><path stroke="#fff" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m24 33-.1-.15c-.695-1.042-1.042-1.563-1.5-1.94a3.999 3.999 0 0 0-1.378-.737C20.453 30 19.827 30 18.575 30H17.2c-1.12 0-1.68 0-2.108-.218a2 2 0 0 1-.874-.874C14 28.48 14 27.92 14 26.8v-8.6c0-1.12 0-1.68.218-2.108a2 2 0 0 1 .874-.874C15.52 15 16.08 15 17.2 15h.4c2.24 0 3.36 0 4.216.436a4 4 0 0 1 1.748 1.748C24 18.04 24 19.16 24 21.4M24 33V21.4M24 33l.1-.15c.695-1.042 1.042-1.563 1.5-1.94a3.999 3.999 0 0 1 1.378-.737C27.547 30 28.173 30 29.425 30H30.8c1.12 0 1.68 0 2.108-.218a2 2 0 0 0 .874-.874C34 28.48 34 27.92 34 26.8v-8.6c0-1.12 0-1.68-.218-2.108a2 2 0 0 0-.874-.874C32.48 15 31.92 15 30.8 15h-.4c-2.24 0-3.36 0-4.216.436a4 4 0 0 0-1.748 1.748C24 18.04 24 19.16 24 21.4"/></svg>
                        <div class="flex items-center">
                        <a href="#">
                            <h5 class="font-bold tracking-tight text-gray-900 dark:text-white">Pages</h5>
                        </a>
                        </div>

                        </div>
                        <x-input.toggle wire:model="config.pages" />
                    </div>
                    <div class="flex-1 px-6 py-4">
                    <p class="mb-3 text-sm font-normal text-gray-500 dark:text-gray-400">Build custom automations and integrations with apps.</p>
                    </div>

                        <div class="flex justify-end px-6 py-4 border-t-2 border-gray-400/30">
                            <a href="#" class="inline-flex items-center font-semibold text-primary-600">
                            Docs

                        </a>
                        </div>

                </div>



                </div>

                <div class="w-full md:w-1/3 px-3">
                    <div class="flex flex-col justify-between my-3 bg-white border border-gray-400/30 rounded-lg shadow-md dark:bg-gray-800 dark:border-gray-700 md:ml-3">
                    <div class="flex items-center justify-between p-6">
                        <div class="flex items-center space-x-2">

                        <svg xmlns="http://www.w3.org/2000/svg" width="49" height="48" fill="none"><rect width="48" height="48" x=".666" fill="#000CEB" rx="24"/><path fill="#fff" d="M35.902 33.971h-5.287l-3.851-6.781-2.098-3.822-2.097 3.822-2.443 4.252L18.718 34l-5.287-.029 11.235-19.827L35.902 33.97Z"/></svg>
                        <div class="flex items-center">
                        <a href="#">
                            <h5 class="font-bold tracking-tight text-gray-900 dark:text-white">Aura Pro</h5>
                        </a>
                        </div>

                        </div>
                        <x-input.toggle wire:model="config.pro" />
                        </div>
                        <div class="flex-1 px-6 py-4">
                        <p class="mb-3 text-sm font-normal text-gray-500 dark:text-gray-400">Send notifications to channels and create projects.</p>
                        </div>

                        <div class="flex justify-end px-6 py-4 border-t-2 border-gray-400/30">
                            <a href="#" class="inline-flex items-center font-semibold text-primary-600">
                            Docs

                        </a>
                        </div>

                </div>

                </div>

                <div class="w-full md:w-1/3 px-3">
                    <div class="flex flex-col justify-between my-3 bg-white border border-gray-400/30 rounded-lg shadow-md dark:bg-gray-800 dark:border-gray-700 md:ml-3">
                        <div class="flex items-center justify-between p-6">
                            <div class="flex items-center space-x-2">

                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="none"><rect width="48" height="48" fill="#CE8323" rx="24"/><path stroke="#fff" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M34 24h-4l-3 9-6-18-3 9h-4"/></svg>
                            <div class="flex items-center">
                            <a href="#">
                                <h5 class="font-bold tracking-tight text-gray-900 dark:text-white">Activity</h5>
                            </a>
                            </div>

                            </div>
                            <x-input.toggle wire:model="config.activity" />
                        </div>
                        <div class="flex-1 px-6 py-4">
                        <p class="mb-3 text-sm font-normal text-gray-500 dark:text-gray-400">Link and automate Zendesk tickets.</p>
                        </div>

                            <div class="flex justify-end px-6 py-4 border-t-2 border-gray-400/30">
                                <a href="#" class="inline-flex items-center font-semibold text-primary-600">
                                Docs

                            </a>
                            </div>

                </div>

                </div>

                <div class="w-full md:w-1/3 px-3">
                    <div class="flex flex-col justify-between my-3 bg-white border border-gray-400/30 rounded-lg shadow-md dark:bg-gray-800 dark:border-gray-700 md:ml-3">
                        <div class="flex items-center justify-between p-6">
                            <div class="flex items-center space-x-2">

                            <svg xmlns="http://www.w3.org/2000/svg" width="49" height="48" fill="none"><rect width="48" height="48" x=".333" fill="#4CA32D" rx="24"/><path stroke="#fff" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M25 19h-7.8c-1.12 0-1.68 0-2.108.218a2 2 0 0 0-.874.874C14 20.52 14 21.08 14 22.2v3.6c0 1.12 0 1.68.218 2.108a2 2 0 0 0 .874.874C15.52 29 16.08 29 17.2 29H25m4-10h1.8c1.12 0 1.68 0 2.108.218a2 2 0 0 1 .874.874C34 20.52 34 21.08 34 22.2v3.6c0 1.12 0 1.68-.218 2.108a2 2 0 0 1-.874.874C32.48 29 31.92 29 30.8 29H29m0 4V15m2.5 0h-5m5 18h-5"/></svg>
                            <div class="flex items-center">
                            <a href="#">
                                <h5 class="font-bold tracking-tight text-gray-900 dark:text-white">Forms</h5>
                            </a>
                            </div>

                            </div>
                            <x-input.toggle wire:model="config.forms" />
                        </div>
                        <div class="flex-1 px-6 py-4">
                        <p class="mb-3 text-sm font-normal text-gray-500 dark:text-gray-400">Plan, track, and release great software.</p>
                        </div>

                            <div class="flex justify-end px-6 py-4 border-t-2 border-gray-400/30">
                                <a href="#" class="inline-flex items-center font-semibold text-primary-600">
                                Docs

                            </a>
                            </div>

                </div>
                </div>

                <div class="w-full md:w-1/3 px-3">
                    <div class="flex flex-col justify-between my-3 bg-white border border-gray-400/30 rounded-lg shadow-md dark:bg-gray-800 dark:border-gray-700 md:ml-3">
                            <div class="flex items-center justify-between p-6">
                                <div class="flex items-center space-x-2">

                                <svg xmlns="http://www.w3.org/2000/svg" width="49" height="48" fill="none"><rect width="48" height="48" x=".666" fill="#4C5760" rx="24"/><path stroke="#fff" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m34 33-3.5-3.5m2.5-6a8.5 8.5 0 1 1-17 0 8.5 8.5 0 0 1 17 0Z"/></svg>
                                <div class="flex items-center">
                                <a href="#">
                                    <h5 class="font-bold tracking-tight text-gray-900 dark:text-white">SEO</h5>
                                </a>
                                </div>

                                </div>
                                <x-input.toggle wire:model="config.seo" />
                            </div>
                            <div class="flex-1 px-6 py-4">
                            <p class="mb-3 text-sm font-normal text-gray-500 dark:text-gray-400">Everything you need for work, all in one place.</p>
                            </div>

                                <div class="flex justify-end px-6 py-4 border-t-2 border-gray-400/30">
                                    <a href="#" class="inline-flex items-center font-semibold text-primary-600">
                                    Docs

                                </a>
                                </div>

                    </div>
                </div>
            </div>
