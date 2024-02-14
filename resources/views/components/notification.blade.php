
<div
    x-data="{
        messages: [],
        timers: [],
        durations: [],
        startTimes: [],
        remainingTimes: [],
        progress: [],
        paused: [],
        totalDuration: 3000,
        remove(message) {
            clearTimeout(this.timers[this.messages.indexOf(message)]);
            this.timers.splice(this.messages.indexOf(message), 1);
            this.startTimes.splice(this.messages.indexOf(message), 1);
            this.remainingTimes.splice(this.messages.indexOf(message), 1);
            this.durations.splice(this.messages.indexOf(message), 1);
            this.messages.splice(this.messages.indexOf(message), 1);
        },
        addTimer(message) {
            let index = this.messages.indexOf(message);
            this.paused[index] = false;
            let duration = this.remainingTimes[index] || this.totalDuration;
            this.startTimes[index] = new Date();
            this.durations[index] = duration;
            let timer = setTimeout(() => { this.remove(message) }, duration);
            this.timers[index] = timer;
        },
        clearTimer(message) {
            let index = this.messages.indexOf(message);
            this.paused[index] = true;
            clearTimeout(this.timers[index]);
            let elapsedTime = new Date() - this.startTimes[index];
            this.remainingTimes[index] = this.durations[index] - elapsedTime;
        },
        getProgress(message) {
            let index = this.messages.indexOf(message);
            let elapsedTime = new Date() - this.startTimes[index];
            let progress = Math.max(0, (((this.durations[index] - elapsedTime) / this.totalDuration)) * 100);

            return progress;
        },
        animateProgress() {
            for (let i = 0; i < this.messages.length; i++) {
                if (!this.paused[i]) {
                    this.progress[i] = this.getProgress(this.messages[i]);
                }
            }
            window.requestAnimationFrame(() => this.animateProgress());
        },

        init() {
            this.animateProgress();
        }
    }"
    @notify.window="let message = $event.detail; messages.push(message); addTimer(message);"
    class="flex fixed inset-0 z-50 flex-col justify-center items-end px-4 py-6 space-y-4 pointer-events-none sm:p-6 sm:justify-start"
>
    <template x-for="(message, messageIndex) in messages" :key="messageIndex" hidden>
        <div
            x-transition:enter="transform ease-out duration-300 transition"
            x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="w-full max-w-sm bg-white rounded-lg shadow-lg pointer-events-auto dark:bg-gray-800"
            @mouseenter="clearTimer(message)"
            @mouseleave="addTimer(message)"
        >
            <div class="overflow-hidden rounded-lg shadow-xs">
                <div class="p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="text-green-500" x-show="message.type == 'success'">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M7.5 12L10.5 15L16.5 9M22 12C22 17.5228 17.5228 22 12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>

                            </div>

                            <div class="text-red-500" x-show="message.type == 'error'">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M11.9998 8.99999V13M11.9998 17H12.0098M10.6151 3.89171L2.39019 18.0983C1.93398 18.8863 1.70588 19.2803 1.73959 19.6037C1.769 19.8857 1.91677 20.142 2.14613 20.3088C2.40908 20.5 2.86435 20.5 3.77487 20.5H20.2246C21.1352 20.5 21.5904 20.5 21.8534 20.3088C22.0827 20.142 22.2305 19.8857 22.2599 19.6037C22.2936 19.2803 22.0655 18.8863 21.6093 18.0983L13.3844 3.89171C12.9299 3.10654 12.7026 2.71396 12.4061 2.58211C12.1474 2.4671 11.8521 2.4671 11.5935 2.58211C11.2969 2.71396 11.0696 3.10655 10.6151 3.89171Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>

                            </div>
                        </div>

                        <div class="flex-1 pt-0.5 ml-3 w-0">
                            <p x-text="message.message" class="text-sm text-gray-900 dark:text-gray-200 font-regular"></p>
                        </div>
                        <div class="flex flex-shrink-0 ml-4">
                            <button @click="remove(message)" class="inline-flex text-gray-400 transition duration-150 ease-in-out dark:text-gray-600 focus:outline-none focus:text-gray-500">
                                <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="relative h-0.5 bg-white dark:bg-gray-800">
                    <div  :style="'width:' + progress[messages.indexOf(message)] + '%'" class="absolute left-0 h-0.5 bg-gray-300 dark:bg-gray-600"></div>
                </div>
            </div>
        </div>
    </template>
</div>
