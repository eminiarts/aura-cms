import flatpickr from "flatpickr";
// import { German } from "flatpickr/dist/l10n/de.js"

// window.german = German;
window.flatpickr = flatpickr;

const registerAuraDatetimePicker = () => {
    window.Alpine.data('auraDatetimePicker', () => ({
        pickerOptions: {},

        changed(event) {
            this.$dispatch('input', { value: event.target.value });
        },

        init() {
            this.pickerOptions = JSON.parse(this.$el.dataset.pickerOptions || '{}');

            window.flatpickr(this.$refs.input, this.pickerOptions);
        },
    }));
};

if (window.Alpine) {
    registerAuraDatetimePicker();
} else {
    document.addEventListener('alpine:init', registerAuraDatetimePicker, { once: true });
}
// flatpickr.localize(German); // default locale is now German
