<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Badge from '@/Components/ui/Badge.vue';
import Card from '@/Components/ui/Card.vue';
import { Check, X, RotateCcw, AlertTriangle } from 'lucide-vue-next';

const props = defineProps({
    borrowings: Object,
    tab: String,
    counts: Object,
});

const tabs = [
    { key: 'requested', label: 'Menunggu', count: props.counts?.requested },
    { key: 'borrowed', label: 'Dipinjam', count: props.counts?.borrowed },
    { key: 'return', label: 'Pengajuan Kembali', count: props.counts?.return },
    { key: 'overdue', label: 'Terlambat', count: props.counts?.overdue },
    { key: 'history', label: 'Riwayat', count: null },
];

function switchTab(key) {
    router.get(route('staff.borrowings.index'), { tab: key }, { preserveState: true });
}

function action(routeName, id, extra = {}) {
    router.post(route(routeName, id), extra, { preserveScroll: true });
}
</script>

<template>
    <Head title="Peminjaman" />
    <AppLayout title="Kelola Peminjaman">
        <!-- Tabs -->
        <div class="mb-6 flex flex-wrap gap-2">
            <button
                v-for="t in tabs"
                :key="t.key"
                type="button"
                @click="switchTab(t.key)"
                class="rounded-xl px-4 py-2 text-sm font-medium transition"
                :class="tab === t.key
                    ? 'bg-brand text-white shadow-md shadow-brand/20'
                    : 'bg-white text-stone-600 hover:bg-stone-100 dark:bg-stone-900 dark:text-stone-300 dark:hover:bg-stone-800'"
            >
                {{ t.label }}
                <span v-if="t.count != null" class="ml-1.5 rounded-full bg-white/20 px-1.5 py-0.5 text-xs">{{ t.count }}</span>
            </button>
        </div>

        <Card>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-stone-100 text-left text-xs uppercase tracking-wider text-stone-500 dark:border-stone-800">
                            <th class="px-5 py-3 font-medium">Anggota</th>
                            <th class="px-5 py-3 font-medium">Buku</th>
                            <th class="px-5 py-3 font-medium">Tanggal</th>
                            <th class="px-5 py-3 font-medium">Status</th>
                            <th class="px-5 py-3 font-medium text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 dark:divide-stone-800">
                        <tr v-for="b in borrowings.data" :key="b.id" class="hover:bg-stone-50/50 dark:hover:bg-stone-800/30">
                            <td class="px-5 py-3.5">
                                <p class="font-medium text-stone-900 dark:text-white">{{ b.user?.full_name }}</p>
                                <p class="text-xs text-stone-500">{{ b.user?.member_code || b.user?.username }}</p>
                            </td>
                            <td class="px-5 py-3.5">
                                <p class="font-medium text-stone-900 dark:text-white">{{ b.book?.title }}</p>
                                <p class="text-xs text-stone-500">{{ b.book?.book_code }}</p>
                            </td>
                            <td class="px-5 py-3.5 text-stone-600 dark:text-stone-400">
                                <template v-if="b.due_date">Jatuh tempo: {{ b.due_date }}</template>
                                <template v-else>Diajukan: {{ b.created_at?.slice?.(0, 10) || '—' }}</template>
                            </td>
                            <td class="px-5 py-3.5">
                                <Badge :variant="b.status === 'requested' ? 'info' : b.status === 'borrowed' ? 'success' : b.status === 'returned' ? 'muted' : 'warning'">
                                    {{ b.status }}
                                </Badge>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex flex-wrap justify-end gap-1.5">
                                    <template v-if="tab === 'requested'">
                                        <Button size="sm" @click="action('staff.borrowings.approve', b.id, { days: 7 })">
                                            <Check class="h-3.5 w-3.5" /> Setujui
                                        </Button>
                                        <Button size="sm" variant="danger" @click="action('staff.borrowings.reject', b.id)">
                                            <X class="h-3.5 w-3.5" /> Tolak
                                        </Button>
                                    </template>
                                    <template v-else-if="tab === 'return'">
                                        <Button size="sm" @click="action('staff.borrowings.confirm-return', b.id)">
                                            <RotateCcw class="h-3.5 w-3.5" /> Terima
                                        </Button>
                                        <Button size="sm" variant="outline" @click="action('staff.borrowings.reject-return', b.id)">
                                            Belum diterima
                                        </Button>
                                    </template>
                                    <template v-else-if="tab === 'borrowed' || tab === 'overdue'">
                                        <Button size="sm" variant="danger" @click="action('staff.borrowings.lost', b.id)">
                                            <AlertTriangle class="h-3.5 w-3.5" /> Hilang
                                        </Button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="!borrowings.data.length" class="py-16 text-center text-stone-500">
                Tidak ada data di tab ini.
            </div>
        </Card>
    </AppLayout>
</template>
