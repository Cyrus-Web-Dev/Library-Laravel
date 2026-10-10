<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TextField from '@/Components/TextField.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({ tab: String, user: Object, memberTypes: Array });

const activeTab = ref(props.tab === 'password' ? 'password' : 'profile');

const profileForm = useForm({
    full_name: props.user.full_name,
    email: props.user.email,
    phone: props.user.phone,
    address: props.user.address,
    bio: props.user.bio,
    institution: props.user.institution,
    member_code: props.user.member_code,
    member_type: props.user.member_type,
});

const passwordForm = useForm({ current_password: '', password: '', password_confirmation: '' });

function saveProfile() {
    profileForm.put(route('profile.update'));
}

function savePassword() {
    passwordForm.put(route('profile.password'), {
        onSuccess: () => passwordForm.reset(),
    });
}
</script>

<template>
    <Head title="Profil Saya" />
    <AppLayout title="Profil saya">
        <div v-if="user.must_change_password" class="mb-6 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-300">
            Password Anda masih sementara. Silakan ganti dulu di tab "Password" di bawah.
        </div>

        <div class="mb-6 flex gap-2 border-b border-stone-200 dark:border-stone-800">
            <button
                type="button"
                class="border-b-2 px-3 py-2 text-sm font-semibold"
                :class="activeTab === 'profile' ? 'border-brand text-brand' : 'border-transparent text-stone-500'"
                @click="activeTab = 'profile'"
            >Profil</button>
            <button
                type="button"
                class="border-b-2 px-3 py-2 text-sm font-semibold"
                :class="activeTab === 'password' ? 'border-brand text-brand' : 'border-transparent text-stone-500'"
                @click="activeTab = 'password'"
            >Password</button>
        </div>

        <form v-if="activeTab === 'profile'" class="max-w-xl space-y-4" @submit.prevent="saveProfile">
            <TextField v-model="profileForm.full_name" label="Nama lengkap" required :error="profileForm.errors.full_name" />
            <TextField v-model="profileForm.email" type="email" label="Email" required :error="profileForm.errors.email" />
            <TextField v-model="profileForm.phone" label="Telepon" :error="profileForm.errors.phone" />

            <div v-if="memberTypes?.length">
                <label class="mb-1 block text-sm font-semibold text-stone-700 dark:text-stone-200">Jenis anggota</label>
                <select v-model="profileForm.member_type" class="w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm dark:border-stone-700 dark:bg-stone-900 dark:text-white">
                    <option v-for="t in memberTypes" :key="t" :value="t">{{ t }}</option>
                </select>
            </div>

            <TextField v-model="profileForm.institution" label="Sekolah / kampus / instansi" />
            <TextField v-model="profileForm.member_code" label="NIS / NIM / NIP" />

            <div>
                <label class="mb-1 block text-sm font-semibold text-stone-700 dark:text-stone-200">Alamat</label>
                <textarea v-model="profileForm.address" rows="3" class="w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm dark:border-stone-700 dark:bg-stone-900 dark:text-white" />
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-stone-700 dark:text-stone-200">Tentang saya</label>
                <textarea v-model="profileForm.bio" rows="3" class="w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm dark:border-stone-700 dark:bg-stone-900 dark:text-white" />
            </div>

            <PrimaryButton :block="false" :loading="profileForm.processing">Simpan profil</PrimaryButton>
        </form>

        <form v-else class="max-w-sm space-y-4" @submit.prevent="savePassword">
            <TextField v-model="passwordForm.current_password" type="password" label="Password saat ini" required :error="passwordForm.errors.current_password" />
            <TextField v-model="passwordForm.password" type="password" label="Password baru" required :error="passwordForm.errors.password" hint="Min. 8 karakter, huruf besar+kecil, dan angka." />
            <TextField v-model="passwordForm.password_confirmation" type="password" label="Ulangi password baru" required />
            <PrimaryButton :block="false" :loading="passwordForm.processing">Ganti password</PrimaryButton>
        </form>
    </AppLayout>
</template>
