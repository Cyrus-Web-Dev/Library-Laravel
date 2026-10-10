<script setup>
import { useForm, Link, Head } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import TextField from '@/Components/TextField.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const form = useForm({ login: '' });

function submit() {
    form.post(route('password.request'));
}
</script>

<template>
    <Head title="Lupa Password" />
    <AuthLayout title="Lupa password" subtitle="Masukkan username atau email akun Anda. Kami akan mengirim kode verifikasi ke email terdaftar.">
        <form class="space-y-4" @submit.prevent="submit">
            <TextField v-model="form.login" label="Username atau email" required autofocus :error="form.errors.login" />
            <PrimaryButton :loading="form.processing">Kirim kode verifikasi</PrimaryButton>
        </form>
        <p class="mt-4 text-sm"><Link :href="route('login')" class="text-brand hover:underline">← Kembali ke halaman masuk</Link></p>
    </AuthLayout>
</template>
