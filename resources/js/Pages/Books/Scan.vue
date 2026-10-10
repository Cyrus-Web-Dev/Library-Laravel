<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({ book: Object });
</script>

<template>
    <Head :title="book.title" />
    <div class="flex min-h-screen items-center justify-center bg-stone-100 p-4 dark:bg-[#0e1714]">
        <div class="w-full max-w-md overflow-hidden rounded-xl border border-stone-200 bg-white shadow-lg dark:border-stone-800 dark:bg-stone-900">
            <div class="bg-side p-6 text-white">
                <p class="text-xs text-stone-300">Hasil pindai QR</p>
                <h1 class="mt-1 font-display text-xl font-semibold">{{ book.title }}</h1>
                <p class="text-sm text-stone-300">{{ book.author }}</p>
            </div>

            <div class="p-6">
                <div class="flex gap-4">
                    <img v-if="book.cover_url" :src="book.cover_url" class="h-32 w-24 flex-shrink-0 rounded object-cover" alt="">
                    <div v-else class="grid h-32 w-24 flex-shrink-0 place-items-center rounded bg-brand-soft text-3xl dark:bg-brand/20">📖</div>

                    <dl class="grid flex-1 grid-cols-[auto,1fr] gap-x-3 gap-y-1 text-sm">
                        <dt class="text-stone-500">Genre</dt><dd>{{ book.genre || '-' }}</dd>
                        <dt class="text-stone-500">Tipe</dt><dd>{{ book.type || '-' }}</dd>
                        <dt class="text-stone-500">Lokasi</dt><dd>📍 {{ book.location || 'Belum diisi' }}</dd>
                        <dt class="text-stone-500">Kode</dt><dd class="font-mono">{{ book.book_code || '-' }}</dd>
                    </dl>
                </div>

                <div
                    class="mt-5 flex items-center gap-3 rounded-lg px-4 py-3"
                    :class="book.is_borrowable ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300' : 'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-300'"
                >
                    <span class="font-display text-2xl font-bold">{{ book.available_stock }}</span>
                    <span class="text-sm">
                        dari {{ book.total_stock }} eksemplar {{ book.is_borrowable ? 'tersedia. Silakan ambil & pinjam di meja pustakawan.' : 'sedang dipinjam semua.' }}
                    </span>
                </div>

                <p v-if="book.ratings_count" class="mt-3 text-sm text-stone-500">
                    ⭐ {{ book.average_rating }} dari {{ book.ratings_count }} ulasan
                </p>

                <Link :href="route('login')" class="mt-5 block w-full rounded-lg bg-brand px-4 py-2.5 text-center text-sm font-semibold text-white hover:bg-brand-light">
                    Masuk untuk ajukan peminjaman
                </Link>
            </div>
        </div>
    </div>
</template>
