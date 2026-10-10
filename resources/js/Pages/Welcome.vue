<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import FlashToasts from '@/Components/FlashToasts.vue';
import BookScene from '@/Components/BookScene.vue';
import { BookOpen, Search, QrCode, Users, Sparkles } from 'lucide-vue-next';

const page = usePage();
const libraryName = page.props.library?.name || 'Perpustakaan Nusantara';
const user = page.props.auth?.user;
</script>

<template>
    <Head title="Beranda" />
    <FlashToasts />

    <div class="relative min-h-screen overflow-hidden bg-gradient-to-br from-stone-50 via-emerald-50/30 to-stone-100 dark:from-[#0a1210] dark:via-[#0e1714] dark:to-[#0a1210]">
        <!-- 3D floating books background -->
        <BookScene />

        <!-- Gradient overlay for readability -->
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-b from-transparent via-transparent to-stone-50/90 dark:to-[#0a1210]/90"></div>

        <header class="relative z-10 border-b border-stone-200/50 bg-white/70 backdrop-blur-xl dark:border-stone-800/50 dark:bg-stone-950/70">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                <span class="flex items-center gap-2.5 font-display text-lg font-bold tracking-tight text-stone-900 dark:text-white">
                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-gradient-to-br from-brand to-emerald-600 text-white shadow-lg shadow-brand/25">
                        <BookOpen class="h-5 w-5" />
                    </span>
                    {{ libraryName }}
                </span>
                <nav class="flex items-center gap-2 text-sm">
                    <template v-if="user">
                        <Link :href="route('dashboard')" class="rounded-xl bg-brand px-5 py-2.5 font-semibold text-white shadow-lg shadow-brand/20 transition hover:bg-brand-light hover:shadow-brand/30">
                            Dashboard
                        </Link>
                    </template>
                    <template v-else>
                        <Link :href="route('login')" class="rounded-xl px-4 py-2.5 font-semibold text-stone-700 transition hover:bg-stone-100/80 dark:text-stone-200 dark:hover:bg-stone-800/80">
                            Masuk
                        </Link>
                        <Link :href="route('register')" class="rounded-xl bg-brand px-5 py-2.5 font-semibold text-white shadow-lg shadow-brand/20 transition hover:bg-brand-light">
                            Daftar
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <main class="relative z-10">
            <section class="mx-auto max-w-6xl px-6 pb-16 pt-20 md:pt-28">
                <div class="max-w-2xl">
                    <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-brand/20 bg-brand/5 px-3 py-1 text-xs font-medium text-brand dark:border-brand/30 dark:bg-brand/10">
                        <Sparkles class="h-3.5 w-3.5" />
                        Platform Perpustakaan Modern
                    </div>
                    <h1 class="font-display text-4xl font-bold leading-[1.15] tracking-tight text-stone-900 dark:text-white md:text-5xl lg:text-6xl">
                        Temukan, pinjam,<br>
                        <span class="bg-gradient-to-r from-brand to-emerald-500 bg-clip-text text-transparent">baca tanpa batas</span>
                    </h1>
                    <p class="mt-6 max-w-lg text-lg leading-relaxed text-stone-600 dark:text-stone-400">
                        Katalog digital lengkap: novel, komik, buku pelajaran, e-book, dan koleksi spesial.
                        Ajukan peminjaman online, scan QR, atau baca e-book langsung dari perangkatmu.
                    </p>
                    <div class="mt-10 flex flex-wrap gap-3">
                        <Link
                            v-if="!user"
                            :href="route('register')"
                            class="inline-flex items-center gap-2 rounded-xl bg-brand px-6 py-3.5 font-semibold text-white shadow-xl shadow-brand/25 transition hover:bg-brand-light hover:shadow-brand/40"
                        >
                            <Users class="h-5 w-5" />
                            Daftar jadi anggota
                        </Link>
                        <Link
                            v-else
                            :href="route('dashboard')"
                            class="inline-flex items-center gap-2 rounded-xl bg-brand px-6 py-3.5 font-semibold text-white shadow-xl shadow-brand/25 transition hover:bg-brand-light"
                        >
                            Ke Dashboard
                        </Link>
                        <a
                            href="#fitur"
                            class="inline-flex items-center gap-2 rounded-xl border border-stone-300 bg-white/80 px-6 py-3.5 font-semibold text-stone-700 backdrop-blur transition hover:bg-white dark:border-stone-700 dark:bg-stone-900/80 dark:text-stone-200 dark:hover:bg-stone-900"
                        >
                            Lihat fitur
                        </a>
                    </div>
                </div>
            </section>

            <!-- Feature cards -->
            <section id="fitur" class="mx-auto max-w-6xl px-6 pb-24">
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="group rounded-2xl border border-stone-200/80 bg-white/70 p-6 shadow-sm backdrop-blur transition hover:border-brand/30 hover:shadow-lg hover:shadow-brand/5 dark:border-stone-800/80 dark:bg-stone-900/60">
                        <div class="mb-4 grid h-12 w-12 place-items-center rounded-xl bg-brand/10 text-brand transition group-hover:bg-brand group-hover:text-white">
                            <Search class="h-6 w-6" />
                        </div>
                        <h3 class="font-display text-lg font-semibold text-stone-900 dark:text-white">Katalog Cerdas</h3>
                        <p class="mt-2 text-sm leading-relaxed text-stone-600 dark:text-stone-400">
                            Cari berdasarkan judul, penulis, genre, atau ISBN. Filter stok tersedia dan tipe buku secara instan.
                        </p>
                    </div>
                    <div class="group rounded-2xl border border-stone-200/80 bg-white/70 p-6 shadow-sm backdrop-blur transition hover:border-brand/30 hover:shadow-lg hover:shadow-brand/5 dark:border-stone-800/80 dark:bg-stone-900/60">
                        <div class="mb-4 grid h-12 w-12 place-items-center rounded-xl bg-brand/10 text-brand transition group-hover:bg-brand group-hover:text-white">
                            <QrCode class="h-6 w-6" />
                        </div>
                        <h3 class="font-display text-lg font-semibold text-stone-900 dark:text-white">Scan QR Instan</h3>
                        <p class="mt-2 text-sm leading-relaxed text-stone-600 dark:text-stone-400">
                            Setiap buku punya label QR. Scan untuk lihat detail, stok, dan lokasi rak — tanpa perlu login.
                        </p>
                    </div>
                    <div class="group rounded-2xl border border-stone-200/80 bg-white/70 p-6 shadow-sm backdrop-blur transition hover:border-brand/30 hover:shadow-lg hover:shadow-brand/5 dark:border-stone-800/80 dark:bg-stone-900/60">
                        <div class="mb-4 grid h-12 w-12 place-items-center rounded-xl bg-brand/10 text-brand transition group-hover:bg-brand group-hover:text-white">
                            <BookOpen class="h-6 w-6" />
                        </div>
                        <h3 class="font-display text-lg font-semibold text-stone-900 dark:text-white">E-Book & Pinjaman</h3>
                        <p class="mt-2 text-sm leading-relaxed text-stone-600 dark:text-stone-400">
                            Baca e-book langsung di browser. Ajukan pinjam fisik online, pustakawan menyetujui dalam satu klik.
                        </p>
                    </div>
                </div>
            </section>
        </main>

        <footer class="relative z-10 border-t border-stone-200/50 bg-white/50 py-8 backdrop-blur dark:border-stone-800/50 dark:bg-stone-950/50">
            <div class="mx-auto max-w-6xl px-6 text-center text-sm text-stone-500 dark:text-stone-400">
                &copy; {{ new Date().getFullYear() }} {{ libraryName }}. Dibangun dengan Laravel + Vue 3 + Three.js.
            </div>
        </footer>
    </div>
</template>
