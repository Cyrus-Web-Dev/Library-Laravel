<script setup>
import { useForm, Link, Head } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import TextField from '@/Components/TextField.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const form = useForm({ login: '', password: '', remember: false });

function submit() {
    form.post(route('login'), { onFinish: () => form.reset('password') });
}
</script>

<template>
    <Head title="Masuk" />
    <AuthLayout title="Masuk" subtitle="Gunakan username atau email yang terdaftar.">
        <form class="space-y-4" @submit.prevent="submit">
            <TextField v-model="form.login" label="Username atau email" required autocomplete="username" :error="form.errors.login" />
            <TextField v-model="form.password" type="password" label="Password" required autocomplete="current-password" :error="form.errors.password" />

            <label class="flex items-center gap-2 text-sm text-stone-600 dark:text-stone-300">
                <input v-model="form.remember" type="checkbox" class="rounded border-stone-300 text-brand focus:ring-brand">
                Ingat saya
            </label>

            <PrimaryButton :loading="form.processing">Masuk</PrimaryButton>
        </form>

        <p class="mt-4 text-sm"><Link :href="route('password.request')" class="text-brand hover:underline">Lupa password?</Link></p>
        <hr class="my-4 border-stone-200 dark:border-stone-800">
        <p class="text-sm text-stone-600 dark:text-stone-300">
            Belum punya akun?
            <Link :href="route('register')" class="font-semibold text-brand hover:underline">Daftar sebagai anggota</Link>
        </p>
    </AuthLayout>
</template>
