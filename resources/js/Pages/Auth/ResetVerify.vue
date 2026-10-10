<script setup>
import { Head } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import OtpForm from '@/Components/OtpForm.vue';
import TextField from '@/Components/TextField.vue';

defineProps({ resendIn: Number, codeLength: Number });
</script>

<template>
    <Head title="Atur Ulang Password" />
    <AuthLayout title="Password baru" subtitle="Masukkan kode verifikasi yang dikirim ke email Anda, lalu buat password baru.">
        <OtpForm
            :submit-url="route('password.store')"
            :resend-url="route('password.verify.resend')"
            :code-length="codeLength"
            :resend-in="resendIn"
            :extra-fields="{ password: '', password_confirmation: '' }"
            submit-label="Simpan password baru"
        >
            <template #extra="{ form }">
                <TextField v-model="form.password" type="password" label="Password baru" required :error="form.errors.password" hint="Min. 8 karakter, huruf besar+kecil, dan angka." />
                <TextField v-model="form.password_confirmation" type="password" label="Ulangi password baru" required />
            </template>
        </OtpForm>
    </AuthLayout>
</template>
