<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import FlashToasts from '@/Components/FlashToasts.vue';

defineProps({ title: String, subtitle: String });

const page = usePage();
const libraryName = page.props.library?.name || 'Perpustakaan Digital';
</script>

<template>
    <div class="grid min-h-screen grid-cols-1 md:grid-cols-2">
        <FlashToasts />

        <aside class="hidden flex-col justify-between bg-side p-12 text-white md:flex">
            <Link :href="route('home')" class="flex items-center gap-3 text-lg font-display font-bold">
                <span class="grid h-9 w-9 place-items-center rounded-lg bg-gold text-[#3b2a00]">📚</span>
                {{ libraryName }}
            </Link>
            <div>
                <h2 class="mb-2 max-w-xs font-display text-3xl font-semibold">Satu kartu, ribuan halaman.</h2>
                <p class="max-w-sm text-sm text-stone-300">
                    Cari buku, baca versi digitalnya, beri bintang, dan usulkan judul baru — semuanya dari perpustakaan kita sendiri.
                </p>
            </div>
            <small class="text-stone-400">Diamankan dengan verifikasi email &amp; hashing Argon2id.</small>
        </aside>

        <main class="flex items-center justify-center p-6">
            <div class="w-full max-w-sm">
                <h1 class="font-display text-2xl font-semibold text-stone-900 dark:text-white">{{ title }}</h1>
                <p v-if="subtitle" class="mt-1 text-sm text-stone-500 dark:text-stone-400">{{ subtitle }}</p>
                <div class="mt-6">
                    <slot />
                </div>
            </div>
        </main>
    </div>
</template>
