<script setup>
import { Head } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import OtpForm from '@/Components/OtpForm.vue';

defineProps({
    maskedEmail: String,
    resendIn: Number,
    codeLength: Number,
});
</script>

<template>
    <Head title="Verifikasi Login" />
    <AuthLayout
        title="Verifikasi login"
        :subtitle="`Kami mengirim kode ${codeLength} digit ke ${maskedEmail}. Masukkan kode untuk menyelesaikan login.`"
    >
        <OtpForm
            :submit-url="route('login.verify')"
            :resend-url="route('login.verify.resend')"
            :code-length="codeLength"
            :resend-in="resendIn"
            submit-label="Verifikasi & masuk"
        />
    </AuthLayout>
</template>
