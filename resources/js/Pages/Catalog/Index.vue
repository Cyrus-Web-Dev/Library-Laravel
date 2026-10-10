<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Search, BookOpen, Filter } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const props = defineProps({
    books: Object,
    filters: Object,
    genres: Array,
    types: Array,
});

const q = ref(props.filters.q || '');
const genreId = ref(props.filters.genre_id || '');
const typeId = ref(props.filters.type_id || '');
const availableOnly = ref(!!props.filters.available_only);

function applyFilters() {
    router.get(route('catalog.index'), {
        q: q.value || undefined,
        genre_id: genreId.value || undefined,
        type_id: typeId.value || undefined,
        available_only: availableOnly.value || undefined,
    }, { preserveState: true, replace: true });
}

let debounceTimer;
watch(q, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(applyFilters, 350);
});
</script>

<template>
    <Head title="Katalog Buku" />
    <AppLayout title="Katalog Buku">
        <div class="space-y-6">
            <!-- Filters -->
            <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-stone-800 dark:bg-stone-900">
                <div class="flex flex-col gap-4 md:flex-row md:items-end">
                    <div class="flex-1">
                        <label class="mb-1.5 block text-xs font-medium text-stone-500">Cari judul / penulis / ISBN</label>
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400" />
                            <input
                                v-model="q"
                                type="search"
                                placeholder="Ketik untuk mencari..."
                                class="w-full rounded-xl border-stone-200 bg-stone-50 py-2.5 pl-10 pr-4 text-sm focus:border-brand focus:ring-brand dark:border-stone-700 dark:bg-stone-800"
                            />
                        </div>
                    </div>
                    <div class="w-full md:w-40">
                        <label class="mb-1.5 block text-xs font-medium text-stone-500">Genre</label>
                        <select v-model="genreId" @change="applyFilters" class="w-full rounded-xl border-stone-200 bg-stone-50 py-2.5 text-sm focus:border-brand focus:ring-brand dark:border-stone-700 dark:bg-stone-800">
                            <option value="">Semua</option>
                            <option v-for="g in genres" :key="g.id" :value="g.id">{{ g.name }}</option>
                        </select>
                    </div>
                    <div class="w-full md:w-40">
                        <label class="mb-1.5 block text-xs font-medium text-stone-500">Tipe</label>
                        <select v-model="typeId" @change="applyFilters" class="w-full rounded-xl border-stone-200 bg-stone-50 py-2.5 text-sm focus:border-brand focus:ring-brand dark:border-stone-700 dark:bg-stone-800">
                            <option value="">Semua</option>
                            <option v-for="t in types" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>
                    </div>
                    <label class="flex items-center gap-2 pb-1 text-sm text-stone-600 dark:text-stone-300">
                        <input type="checkbox" v-model="availableOnly" @change="applyFilters" class="rounded border-stone-300 text-brand focus:ring-brand" />
                        Tersedia saja
                    </label>
                </div>
            </div>

            <!-- Grid -->
            <div v-if="books.data.length" class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <Link
                    v-for="book in books.data"
                    :key="book.id"
                    :href="route('catalog.show', book.id)"
                    class="group overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm transition hover:-translate-y-1 hover:border-brand/30 hover:shadow-lg hover:shadow-brand/5 dark:border-stone-800 dark:bg-stone-900"
                >
                    <div class="aspect-[3/4] overflow-hidden bg-gradient-to-br from-stone-100 to-stone-200 dark:from-stone-800 dark:to-stone-900">
                        <img
                            v-if="book.cover_url"
                            :src="book.cover_url"
                            :alt="book.title"
                            class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                        />
                        <div v-else class="grid h-full place-items-center text-stone-400">
                            <BookOpen class="h-16 w-16 opacity-40" />
                        </div>
                    </div>
                    <div class="p-4">
                        <p class="line-clamp-2 font-display font-semibold text-stone-900 dark:text-white">{{ book.title }}</p>
                        <p class="mt-1 text-sm text-stone-500">{{ book.author || '—' }}</p>
                        <div class="mt-3 flex items-center justify-between text-xs">
                            <span class="rounded-full bg-stone-100 px-2 py-0.5 text-stone-600 dark:bg-stone-800 dark:text-stone-300">
                                {{ book.genre?.name || 'Umum' }}
                            </span>
                            <span :class="book.available_stock > 0 ? 'text-emerald-600' : 'text-rose-500'">
                                {{ book.available_stock > 0 ? `${book.available_stock} tersedia` : 'Habis' }}
                            </span>
                        </div>
                    </div>
                </Link>
            </div>
            <div v-else class="rounded-2xl border border-dashed border-stone-300 py-16 text-center text-stone-500 dark:border-stone-700">
                Tidak ada buku yang cocok dengan filter.
            </div>

            <!-- Pagination -->
            <div v-if="books.last_page > 1" class="flex justify-center gap-2">
                <Link
                    v-for="link in books.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    v-html="link.label"
                    class="rounded-lg px-3 py-1.5 text-sm"
                    :class="link.active ? 'bg-brand text-white' : 'bg-white text-stone-600 hover:bg-stone-100 dark:bg-stone-900 dark:text-stone-300'"
                    :preserve-scroll="true"
                />
            </div>
        </div>
    </AppLayout>
</template>
