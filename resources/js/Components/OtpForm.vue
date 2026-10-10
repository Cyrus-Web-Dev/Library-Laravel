<script setup>
import { ref, onUnmounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    submitUrl: { type: String, required: true },
    resendUrl: { type: String, required: true },
    codeLength: { type: Number, default: 6 },
    resendIn: { type: Number, default: 0 },
    extraFields: { type: Object, default: () => ({}) },
    submitLabel: { type: String, default: 'Verifikasi' },
});

const form = useForm({ code: '', ...props.extraFields });
const countdown = ref(props.resendIn);
const resending = ref(false);

if (countdown.value > 0) {
    const timer = setInterval(() => {
        countdown.value = Math.max(0, countdown.value - 1);
        if (countdown.value === 0) clearInterval(timer);
    }, 1000);
    onUnmounted(() => clearInterval(timer));
}

function submit() {
    form.post(props.submitUrl);
}

function resend() {
    resending.value = true;
    router.post(props.resendUrl, {}, {
        preserveScroll: true,
        onFinish: () => { resending.value = false; countdown.value = 60; },
    });
}
</script>

<template>
    <form class="space-y-4" @submit.prevent="submit">
        <div>
            <label class="mb-1 block text-sm font-semibold text-stone-700 dark:text-stone-200">Kode verifikasi</label>
            <input
                v-model="form.code"
                type="text"
                inputmode="numeric"
                :maxlength="codeLength"
                autocomplete="one-time-code"
                autofocus
                class="w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-center text-2xl tracking-[0.5em] text-stone-900 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40 dark:border-stone-700 dark:bg-stone-900 dark:text-white"
                :class="{ 'border-rose-400': form.errors.code }"
            >
            <p v-if="form.errors.code" class="mt-1 text-xs text-rose-600">{{ form.errors.code }}</p>
        </div>

        <slot name="extra" :form="form" />

        <PrimaryButton :loading="form.processing">{{ submitLabel }}</PrimaryButton>

        <button
            type="button"
            class="w-full text-center text-sm text-stone-500 hover:text-brand disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="countdown > 0 || resending"
            @click="resend"
        >
            {{ countdown > 0 ? `Kirim ulang kode dalam ${countdown} detik` : 'Kirim ulang kode' }}
        </button>
    </form>
</template>
