import { computed, ref } from 'vue';
import { user } from './session';

const KEY = 'product-claim.user-mode';

export const userMode = ref(localStorage.getItem(KEY) === '1');

export const actsAsAdmin = computed(() => Boolean(user.value?.is_admin) && !userMode.value);

export function setUserMode(enabled) {
    userMode.value = enabled;
    localStorage.setItem(KEY, enabled ? '1' : '0');
}

export function homePath() {
    return actsAsAdmin.value ? '/admin' : '/employee';
}
