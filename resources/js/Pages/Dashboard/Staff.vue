<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import { BookOpen, Clock, AlertTriangle, BarChart3, QrCode, Star, Library, ClipboardList } from 'lucide-vue-next';

defineProps({ pendingBorrowCount: Number, pendingReturnCount: Number, overdueCount: Number });

const quickLinks = [
    { name: 'Kelola Buku', href: 'staff.books.index', icon: Library, desc: 'CRUD & upload cover' },
    { name: 'Peminjaman', href: 'staff.borrowings.index', icon: ClipboardList, desc: 'Setujui & kembalikan' },
    { name: 'Laporan', href: 'staff.reports.index', icon: BarChart3, desc: 'Statistik & chart' },
    { name: 'Label QR', href: 'staff.qr.index', icon: QrCode, desc: 'Cetak massal' },
    { name: 'Ulasan', href: 'staff.ratings.index', icon: Star, desc: 'Moderasi rating' },
];
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout title="Dashboard Pustakawan">
        <div class="mb-6 grid gap-4 sm:grid-cols-3">
            <Card class="p-5">
                <div class="flex items-center justify-between">
                    <p class="font-display text-3xl font-semibold text-stone-900 dark:text-white">{{ pendingBorrowCount }}</p>
                    <div class="grid h-10 w-10 place-items-center rounded-xl bg-sky-100 text-sky-600 dark:bg-sky-900/40 dark:text-sky-300">
                        <Clock class="h-5 w-5" />
                    </div>
                </div>
                <p class="mt-1 text-sm text-stone-500">permintaan pinjam menunggu</p>
            </Card>
            <Card class="p-5">
                <div class="flex items-center justify-between">
                    <p class="font-display text-3xl font-semibold text-stone-900 dark:text-white">{{ pendingReturnCount }}</p>
                    <div class="grid h-10 w-10 place-items-center rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-300">
                        <BookOpen class="h-5 w-5" />
                    </div>
                </div>
                <p class="mt-1 text-sm text-stone-500">pengajuan kembali menunggu</p>
            </Card>
            <Card class="p-5" :class="overdueCount ? 'border-rose-200 bg-rose-50/50 dark:border-rose-900 dark:bg-rose-950/20' : ''">
                <div class="flex items-center justify-between">
                    <p class="font-display text-3xl font-semibold" :class="overdueCount ? 'text-rose-600' : 'text-stone-900 dark:text-white'">{{ overdueCount }}</p>
                    <div class="grid h-10 w-10 place-items-center rounded-xl" :class="overdueCount ? 'bg-rose-100 text-rose-600' : 'bg-stone-100 text-stone-500 dark:bg-stone-800'">
                        <AlertTriangle class="h-5 w-5" />
                    </div>
                </div>
                <p class="mt-1 text-sm text-stone-500">buku terlambat</p>
            </Card>
        </div>

        <h2 class="mb-3 font-display text-lg font-semibold text-stone-900 dark:text-white">Akses cepat</h2>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <Link
                v-for="link in quickLinks"
                :key="link.name"
                :href="route(link.href)"
                class="group flex items-center gap-4 rounded-2xl border border-stone-200 bg-white p-4 shadow-sm transition hover:border-brand/30 hover:shadow-md dark:border-stone-800 dark:bg-stone-900"
            >
                <div class="grid h-11 w-11 place-items-center rounded-xl bg-brand/10 text-brand transition group-hover:bg-brand group-hover:text-white">
                    <component :is="link.icon" class="h-5 w-5" />
                </div>
                <div>
                    <p class="font-semibold text-stone-900 dark:text-white">{{ link.name }}</p>
                    <p class="text-xs text-stone-500">{{ link.desc }}</p>
                </div>
            </Link>
        </div>
    </AppLayout>
</template>
