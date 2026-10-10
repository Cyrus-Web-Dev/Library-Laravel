<script setup>
import { Head } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import OtpForm from '@/Components/OtpForm.vue';

defineProps({ maskedEmail: String, resendIn: Number, codeLength: Number });
</script>

<template>
    <Head title="Verifikasi Email" />
    <AuthLayout title="Verifikasi email" :subtitle="`Kami mengirim kode ${codeLength} digit ke ${maskedEmail}.`">
        <OtpForm
            :submit-url="route('register.verify')"
            :resend-url="route('register.verify.resend')"
            :code-length="codeLength"
            :resend-in="resendIn"
            submit-label="Verifikasi & buat akun"
        />
    </AuthLayout>
</template>
