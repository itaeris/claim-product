import { ref } from 'vue';

const message = ref('');
const visible = ref(false);
let timer = 0;

export function useToast() {
    function show(text) {
        message.value = text;
        visible.value = true;
        window.clearTimeout(timer);
        timer = window.setTimeout(() => {
            visible.value = false;
        }, 1800);
    }

    return { message, visible, show };
}
