<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Badge from '@/Components/ui/Badge.vue';
import Card from '@/Components/ui/Card.vue';
import { BookOpen, MapPin, Hash, Star, ArrowLeft } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps({
    book: Object,
    canBorrow: Boolean,
    canRate: Boolean,
});

const form = useForm({ note: '' });
const ratingForm = useForm({ rating: 5, comment: '' });
const hoverStar = ref(0);

function requestBorrow() {
    form.post(route('borrowings.request', props.book.id), { onSuccess: () => form.reset() });
}
function submitRating() {
    ratingForm.post(route('ratings.store', props.book.id), {
        onSuccess: () => ratingForm.reset('comment'),
    });
}
</script>

<template>
    <Head :title="book.title" />
    <AppLayout>
        <div class="mb-6">
            <Link :href="route('catalog.index')" class="inline-flex items-center gap-1.5 text-sm font-medium text-stone-500 hover:text-brand">
                <ArrowLeft class="h-4 w-4" /> Kembali ke katalog
            </Link>
        </div>

        <div class="grid gap-8 lg:grid-cols-[280px_1fr]">
            <div class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm dark:border-stone-800 dark:bg-stone-900">
                <div class="aspect-[3/4] bg-gradient-to-br from-stone-100 to-stone-200 dark:from-stone-800 dark:to-stone-900">
                    <img v-if="book.cover_url" :src="book.cover_url" :alt="book.title" class="h-full w-full object-cover" />
                    <div v-else class="grid h-full place-items-center text-stone-400">
                        <BookOpen class="h-20 w-20 opacity-40" />
                    </div>
                </div>
            </div>

            <div>
                <h1 class="font-display text-3xl font-bold text-stone-900 dark:text-white">{{ book.title }}</h1>
                <p class="mt-1 text-lg text-stone-500">{{ book.author || 'Penulis tidak diketahui' }}</p>

                <div class="mt-3 flex items-center gap-2">
                    <div class="flex items-center gap-0.5 text-amber-500">
                        <Star v-for="n in 5" :key="n" class="h-4 w-4" :class="n <= Math.round(book.average_rating || 0) ? 'fill-current' : 'text-stone-300'" />
                    </div>
                    <span class="text-sm text-stone-500">{{ book.average_rating || 0 }} ({{ book.ratings_count || 0 }} ulasan)</span>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <Badge v-if="book.genre">{{ book.genre.name || book.genre }}</Badge>
                    <Badge v-if="book.type" variant="muted">{{ book.type.name || book.type }}</Badge>
                    <Badge :variant="book.available_stock > 0 ? 'success' : 'danger'">
                        {{ book.available_stock > 0 ? `${book.available_stock} tersedia` : 'Stok habis' }}
                    </Badge>
                </div>

                <dl class="mt-6 grid gap-3 text-sm sm:grid-cols-2">
                    <div class="flex items-center gap-2 text-stone-600 dark:text-stone-400"><Hash class="h-4 w-4" /> ISBN: {{ book.isbn || '—' }}</div>
                    <div class="flex items-center gap-2 text-stone-600 dark:text-stone-400"><MapPin class="h-4 w-4" /> Rak: {{ book.location || '—' }}</div>
                    <div class="text-stone-600 dark:text-stone-400">Tahun: {{ book.publication_year || '—' }}</div>
                    <div class="text-stone-600 dark:text-stone-400">Penerbit: {{ book.publisher || '—' }}</div>
                </dl>

                <div v-if="book.description" class="mt-6">
                    <h2 class="font-display text-sm font-semibold uppercase tracking-wider text-stone-500">Sinopsis</h2>
                    <p class="mt-2 whitespace-pre-line text-stone-700 dark:text-stone-300">{{ book.description }}</p>
                </div>

                <Card v-if="canBorrow && book.available_stock > 0" class="mt-8 border-brand/20 bg-brand/5 p-5">
                    <h3 class="font-semibold text-stone-900 dark:text-white">Ajukan peminjaman</h3>
                    <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">Permintaan akan ditinjau pustakawan.</p>
                    <textarea v-model="form.note" rows="2" placeholder="Catatan opsional..." class="mt-3 w-full rounded-xl border-stone-200 bg-white text-sm focus:border-brand focus:ring-brand dark:border-stone-700 dark:bg-stone-900"></textarea>
                    <Button class="mt-3" :loading="form.processing" @click="requestBorrow">Ajukan Pinjam</Button>
                </Card>

                <!-- Rating form -->
                <Card v-if="canRate" class="mt-6 p-5">
                    <h3 class="font-semibold text-stone-900 dark:text-white">Tulis ulasan</h3>
                    <div class="mt-3 flex gap-1">
                        <button
                            v-for="n in 5"
                            :key="n"
                            type="button"
                            class="text-2xl transition"
                            :class="n <= (hoverStar || ratingForm.rating) ? 'text-amber-400' : 'text-stone-300'"
                            @mouseenter="hoverStar = n"
                            @mouseleave="hoverStar = 0"
                            @click="ratingForm.rating = n"
                        >★</button>
                    </div>
                    <textarea v-model="ratingForm.comment" rows="3" placeholder="Ceritakan pengalaman membaca..." class="mt-3 w-full rounded-xl border-stone-200 bg-white text-sm focus:border-brand focus:ring-brand dark:border-stone-700 dark:bg-stone-900"></textarea>
                    <Button class="mt-3" size="sm" :loading="ratingForm.processing" @click="submitRating">Kirim Ulasan</Button>
                </Card>

                <!-- Approved ratings -->
                <div v-if="book.ratings?.length" class="mt-8">
                    <h3 class="mb-4 font-display font-semibold text-stone-900 dark:text-white">Ulasan pembaca</h3>
                    <ul class="space-y-4">
                        <li v-for="r in book.ratings.filter(x => x.is_approved)" :key="r.id" class="rounded-xl border border-stone-100 p-4 dark:border-stone-800">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-stone-900 dark:text-white">{{ r.user?.full_name || 'Anggota' }}</span>
                                <div class="flex text-amber-400 text-sm">
                                    <span v-for="n in r.rating" :key="n">★</span>
                                </div>
                            </div>
                            <p v-if="r.comment" class="mt-1.5 text-sm text-stone-600 dark:text-stone-400">{{ r.comment }}</p>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
