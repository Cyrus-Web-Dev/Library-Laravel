<script setup>
import { useForm, Link, Head } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import TextField from '@/Components/TextField.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

defineProps({ memberTypes: Array });

const form = useForm({
    full_name: '', username: '', email: '', member_type: '', institution: '',
    member_code: '', phone: '', password: '', password_confirmation: '',
});

function submit() {
    form.post(route('register'));
}
</script>

<template>
    <Head title="Daftar" />
    <AuthLayout title="Daftar anggota" subtitle="Gratis untuk siswa, mahasiswa, guru, dan umum. Kami akan mengirim kode verifikasi ke email Anda.">
        <form class="space-y-4" @submit.prevent="submit">
            <TextField v-model="form.full_name" label="Nama lengkap" required :error="form.errors.full_name" />
            <TextField v-model="form.username" label="Username" required :error="form.errors.username" hint="Huruf, angka, titik, atau garis bawah." />
            <TextField v-model="form.email" type="email" label="Email" required :error="form.errors.email" hint="Kode verifikasi dikirim ke alamat ini." />

            <div>
                <label class="mb-1 block text-sm font-semibold text-stone-700 dark:text-stone-200">Saya adalah <span class="text-rose-500">*</span></label>
                <select v-model="form.member_type" class="w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm dark:border-stone-700 dark:bg-stone-900 dark:text-white" required>
                    <option value="" disabled>Pilih…</option>
                    <option v-for="t in memberTypes" :key="t" :value="t">{{ t }}</option>
                </select>
                <p v-if="form.errors.member_type" class="mt-1 text-xs text-rose-600">{{ form.errors.member_type }}</p>
            </div>

            <TextField v-model="form.institution" label="Sekolah / kampus / instansi" />
            <TextField v-model="form.member_code" label="NIS / NIM / NIP" />
            <TextField v-model="form.phone" label="Telepon / WhatsApp" />
            <TextField v-model="form.password" type="password" label="Password" required :error="form.errors.password" hint="Min. 8 karakter, huruf besar+kecil, dan angka." />
            <TextField v-model="form.password_confirmation" type="password" label="Ulangi password" required />

            <PrimaryButton :loading="form.processing">Lanjut &amp; kirim kode verifikasi</PrimaryButton>
        </form>

        <p class="mt-4 text-sm text-stone-600 dark:text-stone-300">
            Sudah punya akun? <Link :href="route('login')" class="font-semibold text-brand hover:underline">Masuk</Link>
        </p>
    </AuthLayout>
</template>
