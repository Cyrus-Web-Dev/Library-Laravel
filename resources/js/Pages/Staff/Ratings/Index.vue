<script setup>
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Card from '@/Components/ui/Card.vue';
import Badge from '@/Components/ui/Badge.vue';
import { Check, Trash2, Star } from 'lucide-vue-next';

defineProps({ ratings: Object });

function approve(id) {
    router.post(route('staff.ratings.approve', id), {}, { preserveScroll: true });
}
function reject(id) {
    if (!confirm('Tolak dan hapus ulasan ini?')) return;
    router.delete(route('staff.ratings.reject', id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Moderasi Ulasan" />
    <AppLayout title="Moderasi Ulasan Buku">
        <Card>
            <div v-if="!ratings.data.length" class="py-16 text-center text-stone-500">Tidak ada ulasan menunggu.</div>
            <ul v-else class="divide-y divide-stone-100 dark:divide-stone-800">
                <li v-for="r in ratings.data" :key="r.id" class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-start">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-semibold text-stone-900 dark:text-white">{{ r.book?.title }}</p>
                            <div class="flex items-center gap-0.5 text-amber-500">
                                <Star v-for="n in r.rating" :key="n" class="h-3.5 w-3.5 fill-current" />
                            </div>
                        </div>
                        <p class="mt-0.5 text-xs text-stone-500">oleh {{ r.user?.full_name }} · {{ r.created_at?.slice?.(0, 10) }}</p>
                        <p v-if="r.comment" class="mt-2 text-sm text-stone-700 dark:text-stone-300">{{ r.comment }}</p>
                    </div>
                    <div class="flex shrink-0 gap-2">
                        <Button size="sm" @click="approve(r.id)"><Check class="h-3.5 w-3.5" /> Setujui</Button>
                        <Button size="sm" variant="danger" @click="reject(r.id)"><Trash2 class="h-3.5 w-3.5" /> Tolak</Button>
                    </div>
                </li>
            </ul>
        </Card>
    </AppLayout>
</template>
