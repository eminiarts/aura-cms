
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
            class="w-full max-w-sm bg-white rounded-xl shadow-lg ring-1 pointer-events-auto ring-gray-950/10 dark:bg-gray-800 dark:ring-white/10"
            @mouseenter="clearTimer(message)"
            @mouseleave="addTimer(message)"
        >
            <div class="overflow-hidden rounded-xl">
                <div class="p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="flex justify-center items-center w-6 h-6 text-green-600 bg-green-100 rounded-full dark:bg-green-500/15 dark:text-green-400" x-show="message.type == 'success'">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M5 13L9.5 17.5L19 7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>

                            </div>

                            <div class="flex justify-center items-center w-6 h-6 text-red-600 bg-red-100 rounded-full dark:bg-red-500/15 dark:text-red-400" x-show="message.type == 'error'">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 6.5V13.5M12 17H12.01" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>

                            </div>
                        </div>

                        <div class="flex-1 ml-3 w-0">
                            <p x-text="message.message" class="text-sm font-medium text-gray-900 dark:text-gray-100"></p>
                        </div>
                        <div class="flex flex-shrink-0 ml-4">
                            <button @click="remove(message)" class="inline-flex p-1 -m-1 text-gray-400 rounded-md transition duration-150 ease-out hover:bg-gray-950/5 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                                <span class="sr-only">Dismiss notification</span>
                                <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="relative h-0.5 bg-transparent">
                    <div :style="'width:' + progress[messages.indexOf(message)] + '%'" :class="message.type == 'error' ? 'bg-red-500/80 dark:bg-red-400/80' : 'bg-primary-500/80 dark:bg-primary-400/80'" class="absolute left-0 h-0.5"></div>
                </div>
            </div>
        </div>
    </template>
</div>
