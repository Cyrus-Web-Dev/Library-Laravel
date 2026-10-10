<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Card from '@/Components/ui/Card.vue';

const props = defineProps({ settings: Object });

const form = useForm({ ...props.settings });

function submit() {
    form.put(route('admin.settings.update'));
}
</script>

<template>
    <Head title="Pengaturan" />
    <AppLayout title="Pengaturan Perpustakaan">
        <Card class="max-w-2xl p-6 md:p-8">
            <form @submit.prevent="submit" class="space-y-5">
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Nama Perpustakaan</label>
                    <Input v-model="form.library_name" :error="form.errors.library_name" />
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Maks. buku per anggota</label>
                        <Input v-model="form.max_books_per_user" type="number" min="1" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Durasi pinjam (hari)</label>
                        <Input v-model="form.loan_days" type="number" min="1" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Denda per hari (Rp)</label>
                        <Input v-model="form.fine_per_day" type="number" min="0" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Batas maks. denda (Rp)</label>
                        <Input v-model="form.max_fine" type="number" min="0" />
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Blokir pinjam jika ada denda</label>
                    <select v-model="form.block_if_fine" class="h-10 w-full rounded-xl border-stone-200 bg-white text-sm dark:border-stone-700 dark:bg-stone-900">
                        <option value="1">Ya</option>
                        <option value="0">Tidak</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Verifikasi OTP email saat login</label>
                    <select v-model="form.login_otp" class="h-10 w-full rounded-xl border-stone-200 bg-white text-sm dark:border-stone-700 dark:bg-stone-900">
                        <option value="1">Aktif (lebih aman)</option>
                        <option value="0">Nonaktif</option>
                    </select>
                    <p class="mt-1 text-xs text-stone-500">Jika aktif, setiap login membutuhkan kode yang dikirim ke email.</p>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Base URL untuk QR (opsional)</label>
                    <Input v-model="form.qr_base_url" placeholder="https://perpustakaan.sekolah.sch.id" />
                    <p class="mt-1 text-xs text-stone-500">Agar QR bisa di-scan dari HP (bukan localhost).</p>
                </div>
                <Button type="submit" :loading="form.processing">Simpan Pengaturan</Button>
            </form>
        </Card>
    </AppLayout>
</template>
