<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Card from '@/Components/ui/Card.vue';
import Input from '@/Components/ui/Input.vue';
import { Search, Printer, QrCode } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const props = defineProps({ books: Object, filters: Object, qrBase: String });
const q = ref(props.filters?.q || '');
const selected = ref([]);

function apply() {
    router.get(route('staff.qr.index'), { q: q.value || undefined }, { preserveState: true, replace: true });
}
let t;
watch(q, () => { clearTimeout(t); t = setTimeout(apply, 350); });

function toggle(id) {
    const i = selected.value.indexOf(id);
    if (i >= 0) selected.value.splice(i, 1);
    else selected.value.push(id);
}
function toggleAll() {
    if (selected.value.length === props.books.data.length) selected.value = [];
    else selected.value = props.books.data.map((b) => b.id);
}

const form = useForm({ ids: [] });
function printSelected() {
    if (!selected.value.length) return alert('Pilih minimal satu buku.');
    form.ids = [...selected.value];
    form.post(route('staff.qr.print'));
}
</script>

<template>
    <Head title="Label QR" />
    <AppLayout title="Cetak Label QR">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="relative max-w-sm flex-1">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400" />
                <Input v-model="q" placeholder="Cari buku..." class="pl-10" />
            </div>
            <div class="flex items-center gap-2">
                <p class="text-sm text-stone-500">{{ selected.length }} dipilih</p>
                <Button :disabled="!selected.length" @click="printSelected">
                    <Printer class="h-4 w-4" /> Cetak Label
                </Button>
            </div>
        </div>
        <p class="mb-4 text-xs text-stone-500">Base URL QR: <code class="rounded bg-stone-100 px-1 dark:bg-stone-800">{{ qrBase }}</code> — ubah di Pengaturan agar bisa di-scan dari HP.</p>

        <Card>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-stone-100 text-left text-xs uppercase text-stone-500 dark:border-stone-800">
                            <th class="px-4 py-3"><input type="checkbox" :checked="selected.length === books.data.length && books.data.length" @change="toggleAll" class="rounded border-stone-300 text-brand" /></th>
                            <th class="px-4 py-3">Kode</th>
                            <th class="px-4 py-3">Judul</th>
                            <th class="px-4 py-3">Penulis</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 dark:divide-stone-800">
                        <tr v-for="b in books.data" :key="b.id" class="hover:bg-stone-50/50 dark:hover:bg-stone-800/30">
                            <td class="px-4 py-3"><input type="checkbox" :checked="selected.includes(b.id)" @change="toggle(b.id)" class="rounded border-stone-300 text-brand" /></td>
                            <td class="px-4 py-3 font-mono text-xs">{{ b.book_code }}</td>
                            <td class="px-4 py-3 font-medium text-stone-900 dark:text-white">{{ b.title }}</td>
                            <td class="px-4 py-3 text-stone-500">{{ b.author || '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>
    </AppLayout>
</template>
