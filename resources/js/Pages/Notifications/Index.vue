<script setup>
import { Head, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Card from '@/Components/ui/Card.vue';
import { Bell, CheckCheck } from 'lucide-vue-next';

defineProps({ notifications: Object });

function markRead(id) {
    router.post(route('notifications.read', id), {}, { preserveScroll: true });
}
function markAll() {
    router.post(route('notifications.read-all'), {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Notifikasi" />
    <AppLayout title="Notifikasi">
        <div class="mb-4 flex justify-end">
            <Button variant="outline" size="sm" @click="markAll">
                <CheckCheck class="h-4 w-4" /> Tandai semua dibaca
            </Button>
        </div>
        <Card>
            <div v-if="!notifications.data.length" class="py-16 text-center text-stone-500">
                <Bell class="mx-auto h-10 w-10 text-stone-300" />
                <p class="mt-3">Belum ada notifikasi.</p>
            </div>
            <ul v-else class="divide-y divide-stone-100 dark:divide-stone-800">
                <li
                    v-for="n in notifications.data"
                    :key="n.id"
                    class="flex items-start gap-3 px-5 py-4 transition"
                    :class="n.is_read ? 'opacity-60' : 'bg-brand/[0.03]'"
                >
                    <div class="mt-0.5 grid h-8 w-8 shrink-0 place-items-center rounded-full" :class="n.is_read ? 'bg-stone-100 dark:bg-stone-800' : 'bg-brand/15 text-brand'">
                        <Bell class="h-4 w-4" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-stone-900 dark:text-white">{{ n.title }}</p>
                        <p class="mt-0.5 text-sm text-stone-600 dark:text-stone-400">{{ n.message }}</p>
                        <p class="mt-1 text-xs text-stone-400">{{ n.created_at }}</p>
                    </div>
                    <Button v-if="!n.is_read" size="sm" variant="ghost" @click="markRead(n.id)">Tandai dibaca</Button>
                </li>
            </ul>
        </Card>
    </AppLayout>
</template>
