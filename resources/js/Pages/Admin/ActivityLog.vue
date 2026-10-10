<script setup>
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Input from '@/Components/ui/Input.vue';
import Badge from '@/Components/ui/Badge.vue';
import { Search } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const props = defineProps({ logs: Object, filters: Object });
const q = ref(props.filters?.q || '');
let t;
watch(q, () => {
    clearTimeout(t);
    t = setTimeout(() => {
        router.get(route('admin.activity.index'), { q: q.value || undefined }, { preserveState: true, replace: true });
    }, 350);
});
</script>

<template>
    <Head title="Log Aktivitas" />
    <AppLayout title="Log Aktivitas">
        <div class="mb-6 max-w-sm">
            <div class="relative">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400" />
                <Input v-model="q" placeholder="Cari aksi / deskripsi..." class="pl-10" />
            </div>
        </div>
        <Card>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-stone-100 text-left text-xs uppercase text-stone-500 dark:border-stone-800">
                            <th class="px-5 py-3">Waktu</th>
                            <th class="px-5 py-3">Pengguna</th>
                            <th class="px-5 py-3">Aksi</th>
                            <th class="px-5 py-3">Deskripsi</th>
                            <th class="px-5 py-3">IP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 dark:divide-stone-800">
                        <tr v-for="log in logs.data" :key="log.id">
                            <td class="px-5 py-3 text-xs text-stone-500">{{ log.created_at }}</td>
                            <td class="px-5 py-3">{{ log.user?.full_name || '—' }}</td>
                            <td class="px-5 py-3"><Badge variant="muted">{{ log.action }}</Badge></td>
                            <td class="px-5 py-3 text-stone-600 dark:text-stone-400">{{ log.description }}</td>
                            <td class="px-5 py-3 font-mono text-xs text-stone-400">{{ log.ip_address }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="!logs.data.length" class="py-12 text-center text-stone-500">Belum ada log.</div>
        </Card>
    </AppLayout>
</template>
