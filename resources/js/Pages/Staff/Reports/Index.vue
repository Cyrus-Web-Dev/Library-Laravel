<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Badge from '@/Components/ui/Badge.vue';
import { BookOpen, Users, Clock, AlertTriangle, Banknote, Inbox } from 'lucide-vue-next';
import { Line, Doughnut, Bar } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale, LinearScale, PointElement, LineElement,
    BarElement, ArcElement, Title, Tooltip, Legend, Filler,
} from 'chart.js';
import { computed } from 'vue';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, ArcElement, Title, Tooltip, Legend, Filler);

const props = defineProps({
    stats: Object,
    chart: Object,
    statusCounts: Object,
    topBooks: Array,
    genreStats: Array,
});

const lineData = computed(() => ({
    labels: props.chart.labels,
    datasets: [
        {
            label: 'Peminjaman',
            data: props.chart.borrowings,
            borderColor: '#1f4d43',
            backgroundColor: 'rgba(31,77,67,0.12)',
            fill: true,
            tension: 0.35,
        },
        {
            label: 'Pengembalian',
            data: props.chart.returns,
            borderColor: '#e0a526',
            backgroundColor: 'rgba(224,165,38,0.1)',
            fill: true,
            tension: 0.35,
        },
    ],
}));

const lineOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { position: 'bottom' } },
    scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
};

const statusLabels = { requested: 'Menunggu', borrowed: 'Dipinjam', returned: 'Dikembalikan', rejected: 'Ditolak', lost: 'Hilang' };
const doughnutData = computed(() => {
    const keys = Object.keys(props.statusCounts || {});
    return {
        labels: keys.map((k) => statusLabels[k] || k),
        datasets: [{
            data: keys.map((k) => props.statusCounts[k]),
            backgroundColor: ['#0ea5e9', '#10b981', '#78716c', '#f43f5e', '#f59e0b'],
        }],
    };
});

const barData = computed(() => ({
    labels: (props.genreStats || []).map((g) => g.name),
    datasets: [{
        label: 'Jumlah buku',
        data: (props.genreStats || []).map((g) => g.total),
        backgroundColor: '#1f4d43',
        borderRadius: 6,
    }],
}));

const statCards = computed(() => [
    { label: 'Total Buku', value: props.stats.total_books, icon: BookOpen, color: 'text-brand bg-brand/10' },
    { label: 'Anggota', value: props.stats.total_members, icon: Users, color: 'text-sky-600 bg-sky-100 dark:bg-sky-900/40' },
    { label: 'Sedang Dipinjam', value: props.stats.active_loans, icon: Clock, color: 'text-emerald-600 bg-emerald-100 dark:bg-emerald-900/40' },
    { label: 'Terlambat', value: props.stats.overdue, icon: AlertTriangle, color: 'text-rose-600 bg-rose-100 dark:bg-rose-900/40' },
    { label: 'Permintaan', value: props.stats.pending_requests, icon: Inbox, color: 'text-amber-600 bg-amber-100 dark:bg-amber-900/40' },
    { label: 'Denda Belum Lunas', value: 'Rp ' + Number(props.stats.total_fines || 0).toLocaleString('id-ID'), icon: Banknote, color: 'text-violet-600 bg-violet-100 dark:bg-violet-900/40' },
]);
</script>

<template>
    <Head title="Laporan" />
    <AppLayout title="Laporan & Statistik">
        <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Card v-for="s in statCards" :key="s.label" class="flex items-center gap-4 p-5">
                <div class="grid h-12 w-12 place-items-center rounded-xl" :class="s.color">
                    <component :is="s.icon" class="h-6 w-6" />
                </div>
                <div>
                    <p class="font-display text-2xl font-semibold text-stone-900 dark:text-white">{{ s.value }}</p>
                    <p class="text-sm text-stone-500">{{ s.label }}</p>
                </div>
            </Card>
        </div>

        <div class="mb-6 grid gap-6 lg:grid-cols-2">
            <Card class="p-5">
                <h3 class="mb-4 font-display font-semibold text-stone-900 dark:text-white">Tren 6 Bulan</h3>
                <div class="h-64">
                    <Line :data="lineData" :options="lineOptions" />
                </div>
            </Card>
            <Card class="p-5">
                <h3 class="mb-4 font-display font-semibold text-stone-900 dark:text-white">Distribusi Status</h3>
                <div class="mx-auto h-64 max-w-xs">
                    <Doughnut :data="doughnutData" :options="{ responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }" />
                </div>
            </Card>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <Card class="p-5">
                <h3 class="mb-4 font-display font-semibold text-stone-900 dark:text-white">Buku Paling Sering Dipinjam</h3>
                <ul class="divide-y divide-stone-100 dark:divide-stone-800">
                    <li v-for="(b, i) in topBooks" :key="b.id" class="flex items-center gap-3 py-2.5">
                        <span class="grid h-7 w-7 place-items-center rounded-lg bg-brand/10 text-xs font-bold text-brand">{{ i + 1 }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-stone-900 dark:text-white">{{ b.title }}</p>
                            <p class="text-xs text-stone-500">{{ b.author || '—' }}</p>
                        </div>
                        <Badge variant="muted">{{ b.borrowings_count }}x</Badge>
                    </li>
                </ul>
            </Card>
            <Card class="p-5">
                <h3 class="mb-4 font-display font-semibold text-stone-900 dark:text-white">Buku per Genre</h3>
                <div class="h-64">
                    <Bar :data="barData" :options="{ responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }" />
                </div>
            </Card>
        </div>
    </AppLayout>
</template>
