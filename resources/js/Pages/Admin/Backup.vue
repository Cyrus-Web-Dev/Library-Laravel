<script setup>
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Card from '@/Components/ui/Card.vue';
import { Download, Trash2, Database, Plus } from 'lucide-vue-next';

defineProps({ backups: Array });

function create() {
    if (!confirm('Buat backup database sekarang?')) return;
    router.post(route('admin.backup.create'));
}
function download(name) {
    window.location.href = route('admin.backup.download', name);
}
function destroy(name) {
    if (!confirm(`Hapus backup ${name}?`)) return;
    router.delete(route('admin.backup.destroy', name));
}
function formatSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
}
function formatDate(ts) {
    return new Date(ts * 1000).toLocaleString('id-ID');
}
</script>

<template>
    <Head title="Backup" />
    <AppLayout title="Backup Database">
        <div class="mb-6 flex justify-end">
            <Button @click="create"><Plus class="h-4 w-4" /> Buat Backup Sekarang</Button>
        </div>
        <Card>
            <div v-if="!backups.length" class="py-16 text-center text-stone-500">
                <Database class="mx-auto h-10 w-10 text-stone-300" />
                <p class="mt-3">Belum ada file backup.</p>
            </div>
            <ul v-else class="divide-y divide-stone-100 dark:divide-stone-800">
                <li v-for="b in backups" :key="b.name" class="flex items-center gap-4 px-5 py-4">
                    <Database class="h-5 w-5 shrink-0 text-brand" />
                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-stone-900 dark:text-white">{{ b.name }}</p>
                        <p class="text-xs text-stone-500">{{ formatSize(b.size) }} · {{ formatDate(b.modified) }}</p>
                    </div>
                    <Button size="sm" variant="outline" @click="download(b.name)"><Download class="h-3.5 w-3.5" /> Unduh</Button>
                    <Button size="sm" variant="ghost" class="text-rose-500" @click="destroy(b.name)"><Trash2 class="h-3.5 w-3.5" /></Button>
                </li>
            </ul>
        </Card>
    </AppLayout>
</template>
