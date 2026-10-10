<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Badge from '@/Components/ui/Badge.vue';
import Card from '@/Components/ui/Card.vue';
import Input from '@/Components/ui/Input.vue';
import { Plus, Search, Pencil, Trash2, BookOpen } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const props = defineProps({
    books: Object,
    filters: Object,
    genres: Array,
    types: Array,
});

const q = ref(props.filters?.q || '');
const genreId = ref(props.filters?.genre_id || '');
const status = ref(props.filters?.status || '');

function apply() {
    router.get(route('staff.books.index'), {
        q: q.value || undefined,
        genre_id: genreId.value || undefined,
        status: status.value || undefined,
    }, { preserveState: true, replace: true });
}

let t;
watch(q, () => { clearTimeout(t); t = setTimeout(apply, 350); });

function destroy(book) {
    if (!confirm(`Hapus / arsipkan "${book.title}"?`)) return;
    router.delete(route('staff.books.destroy', book.id));
}
</script>

<template>
    <Head title="Kelola Buku" />
    <AppLayout title="Kelola Buku">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-1 flex-wrap gap-3">
                <div class="relative min-w-[200px] flex-1">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400" />
                    <Input v-model="q" placeholder="Cari judul, penulis, ISBN..." class="pl-10" />
                </div>
                <select v-model="genreId" @change="apply" class="h-10 rounded-xl border-stone-200 bg-white text-sm dark:border-stone-700 dark:bg-stone-900">
                    <option value="">Semua genre</option>
                    <option v-for="g in genres" :key="g.id" :value="g.id">{{ g.name }}</option>
                </select>
                <select v-model="status" @change="apply" class="h-10 rounded-xl border-stone-200 bg-white text-sm dark:border-stone-700 dark:bg-stone-900">
                    <option value="">Semua status</option>
                    <option value="available">Tersedia</option>
                    <option value="unavailable">Tidak tersedia</option>
                    <option value="archived">Arsip</option>
                </select>
            </div>
            <Link :href="route('staff.books.create')">
                <Button><Plus class="h-4 w-4" /> Tambah Buku</Button>
            </Link>
        </div>

        <Card>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-stone-100 text-left text-xs uppercase tracking-wider text-stone-500 dark:border-stone-800">
                            <th class="px-5 py-3 font-medium">Buku</th>
                            <th class="px-5 py-3 font-medium">Genre</th>
                            <th class="px-5 py-3 font-medium">Stok</th>
                            <th class="px-5 py-3 font-medium">Status</th>
                            <th class="px-5 py-3 font-medium text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 dark:divide-stone-800">
                        <tr v-for="book in books.data" :key="book.id" class="hover:bg-stone-50/50 dark:hover:bg-stone-800/30">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="h-12 w-9 shrink-0 overflow-hidden rounded-lg bg-stone-100 dark:bg-stone-800">
                                        <img v-if="book.cover_url || book.cover_image" :src="book.cover_url || `/storage/${book.cover_image}`" class="h-full w-full object-cover" />
                                        <div v-else class="grid h-full place-items-center"><BookOpen class="h-4 w-4 text-stone-400" /></div>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-stone-900 dark:text-white">{{ book.title }}</p>
                                        <p class="truncate text-xs text-stone-500">{{ book.author || '—' }} · {{ book.book_code }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-stone-600 dark:text-stone-400">{{ book.genre?.name || '—' }}</td>
                            <td class="px-5 py-3.5">
                                <span class="font-medium">{{ book.available_stock }}</span>
                                <span class="text-stone-400"> / {{ book.total_stock }}</span>
                            </td>
                            <td class="px-5 py-3.5">
                                <Badge :variant="book.status === 'available' ? 'success' : book.status === 'archived' ? 'muted' : 'warning'">
                                    {{ book.status }}
                                </Badge>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex justify-end gap-1">
                                    <Link :href="route('staff.books.edit', book.id)">
                                        <Button variant="ghost" size="icon"><Pencil class="h-4 w-4" /></Button>
                                    </Link>
                                    <Button variant="ghost" size="icon" class="text-rose-500 hover:bg-rose-50" @click="destroy(book)">
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="!books.data.length" class="py-16 text-center text-stone-500">Belum ada buku.</div>
        </Card>
    </AppLayout>
</template>
