<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Card from '@/Components/ui/Card.vue';
import { ArrowLeft } from 'lucide-vue-next';

const props = defineProps({
    book: Object,
    genres: Array,
    types: Array,
});

const isEdit = !!props.book;

const form = useForm({
    title: props.book?.title || '',
    author: props.book?.author || '',
    isbn: props.book?.isbn || '',
    publisher: props.book?.publisher || '',
    publication_year: props.book?.publication_year || '',
    genre_id: props.book?.genre_id || '',
    type_id: props.book?.type_id || '',
    description: props.book?.description || '',
    location: props.book?.location || '',
    total_stock: props.book?.total_stock ?? 1,
    pages: props.book?.pages || '',
    language: props.book?.language || 'Indonesia',
    price: props.book?.price || '',
    status: props.book?.status || 'available',
    cover: null,
    ebook: null,
});

function submit() {
    if (isEdit) {
        form.transform((data) => ({ ...data, _method: 'put' }))
            .post(route('staff.books.update', props.book.id), { forceFormData: true });
    } else {
        form.post(route('staff.books.store'), { forceFormData: true });
    }
}
</script>

<template>
    <Head :title="isEdit ? 'Edit Buku' : 'Tambah Buku'" />
    <AppLayout :title="isEdit ? 'Edit Buku' : 'Tambah Buku'">
        <div class="mb-6">
            <Link :href="route('staff.books.index')" class="inline-flex items-center gap-1.5 text-sm font-medium text-stone-500 hover:text-brand">
                <ArrowLeft class="h-4 w-4" /> Kembali
            </Link>
        </div>

        <Card class="p-6 md:p-8">
            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid gap-5 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="mb-1.5 block text-sm font-medium">Judul *</label>
                        <Input v-model="form.title" :error="form.errors.title" required />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Penulis</label>
                        <Input v-model="form.author" :error="form.errors.author" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">ISBN</label>
                        <Input v-model="form.isbn" :error="form.errors.isbn" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Penerbit</label>
                        <Input v-model="form.publisher" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Tahun Terbit</label>
                        <Input v-model="form.publication_year" type="number" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Genre</label>
                        <select v-model="form.genre_id" class="h-10 w-full rounded-xl border-stone-200 bg-white text-sm dark:border-stone-700 dark:bg-stone-900">
                            <option value="">— Pilih —</option>
                            <option v-for="g in genres" :key="g.id" :value="g.id">{{ g.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Tipe</label>
                        <select v-model="form.type_id" class="h-10 w-full rounded-xl border-stone-200 bg-white text-sm dark:border-stone-700 dark:bg-stone-900">
                            <option value="">— Pilih —</option>
                            <option v-for="t in types" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Lokasi Rak</label>
                        <Input v-model="form.location" placeholder="Mis. A-12" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Stok Total *</label>
                        <Input v-model="form.total_stock" type="number" min="0" required />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Halaman</label>
                        <Input v-model="form.pages" type="number" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Bahasa</label>
                        <Input v-model="form.language" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Harga (untuk denda hilang)</label>
                        <Input v-model="form.price" type="number" step="1000" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Status *</label>
                        <select v-model="form.status" class="h-10 w-full rounded-xl border-stone-200 bg-white text-sm dark:border-stone-700 dark:bg-stone-900">
                            <option value="available">Tersedia</option>
                            <option value="unavailable">Tidak tersedia</option>
                            <option value="archived">Arsip</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1.5 block text-sm font-medium">Sinopsis</label>
                        <textarea v-model="form.description" rows="4" class="w-full rounded-xl border-stone-200 bg-white text-sm focus:border-brand focus:ring-brand dark:border-stone-700 dark:bg-stone-900"></textarea>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Cover (JPG/PNG max 2MB)</label>
                        <input type="file" accept="image/*" @change="form.cover = $event.target.files[0]" class="block w-full text-sm text-stone-500 file:mr-3 file:rounded-lg file:border-0 file:bg-brand/10 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-brand" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">E-Book PDF (max 20MB)</label>
                        <input type="file" accept="application/pdf" @change="form.ebook = $event.target.files[0]" class="block w-full text-sm text-stone-500 file:mr-3 file:rounded-lg file:border-0 file:bg-brand/10 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-brand" />
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <Button type="submit" :loading="form.processing">
                        {{ isEdit ? 'Simpan Perubahan' : 'Tambah Buku' }}
                    </Button>
                    <Link :href="route('staff.books.index')">
                        <Button variant="outline" type="button">Batal</Button>
                    </Link>
                </div>
            </form>
        </Card>
    </AppLayout>
</template>
