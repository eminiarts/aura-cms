export default function (Alpine) {
    Alpine.directive('modal', (el, directive) => {
        if (directive.value === 'overlay') handleOverlay(el, Alpine)
        else if (directive.value === 'panel') handlePanel(el, Alpine)
        else if (directive.value === 'title') handleTitle(el, Alpine)
        else if (directive.value === 'description') handleDescription(el, Alpine)
        else handleRoot(el, Alpine)
    })

    Alpine.magic('modal', el => {
        let $data = Alpine.$data(el)

        return {
            get isOpen() {
                return $data.__isOpen
            },
            close() {
                $data.__close()
            }
        }
    })
}

function handleRoot(el, Alpine) {
    Alpine.bind(el, {
        'x-data'() {
            return {
                init() {
                    
                    (Alpine.bound(el, 'open') !== undefined) && Alpine.effect(() => {
                        this.__isOpenState = Alpine.bound(el, 'open')

                        console.log('init modal here');
                    })

                    if (Alpine.bound(el, 'initial-focus') !== undefined) this.$watch('__isOpenState', () => {
                        if (!this.__isOpenState) return

                        console.log('init modal here');

                        setTimeout(() => {
                            Alpine.bound(el, 'initial-focus').focus()
                        }, 0);
                    })
                },
                __isOpenState: false,
                __close() {
                    if (Alpine.bound(el, 'open')) this.$dispatch('close')
                    else this.__isOpenState = false
                },
                get __isOpen() {
                    return Alpine.bound(el, 'static', this.__isOpenState)
                },
            }
        },
        'x-modelable': '__isOpenState',
        'x-id'() { return ['alpine-dialog-title', 'alpine-dialog-description'] },
        'x-show'() { return this.__isOpen },
        'x-trap.inert.noscroll'() { return this.__isOpen },
        '@keydown.escape'() { this.__close() },
        ':aria-labelledby'() { return this.$id('alpine-dialog-title') },
        ':aria-describedby'() { return this.$id('alpine-dialog-description') },
        'role': 'dialog',
        'aria-modal': 'true',
    })
}

function handleOverlay(el, Alpine) {
    Alpine.bind(el, {
        'x-init'() { if (this.$data.__isOpen === undefined) console.warn('"x-modal:overlay" is missing a parent element with "x-modal".') },
        'x-show'() { return this.__isOpen },
        '@click.prevent.stop'() {
            // Check if this is the topmost dialog
            let topmostDialog = Array.from(document.querySelectorAll('[x-modal]')).reverse().find(dialog => dialog.__x.$data.__isOpen);
            if (topmostDialog === this.$el.closest('[x-modal]')) {
                this.$data.__close()
            }
        },
    })
}

function handlePanel(el, Alpine) {
    Alpine.bind(el, {
        '@click.outside'() {
            // Prevent closing if this panel is not the topmost open panel
            let topmostDialog = Array.from(document.querySelectorAll('[x-modal]')).reverse().find(dialog => dialog.__x.$data.__isOpen);
            if (topmostDialog === this.$el.closest('[x-modal]')) {
                this.$data.__close()
            }
        },
        'x-show'() { return this.$data.__isOpen },
    })
}

function handleTitle(el, Alpine) {
    Alpine.bind(el, {
        'x-init'() { if (this.$data.__isOpen === undefined) console.warn('"x-modal:title" is missing a parent element with "x-modal".') },
        ':id'() { return this.$id('alpine-dialog-title') },
    })
}

function handleDescription(el, Alpine) {
    Alpine.bind(el, {
        ':id'() { return this.$id('alpine-dialog-description') },
    })
}
