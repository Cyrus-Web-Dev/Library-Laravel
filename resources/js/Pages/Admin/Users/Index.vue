<script setup>
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Badge from '@/Components/ui/Badge.vue';
import Card from '@/Components/ui/Card.vue';
import Input from '@/Components/ui/Input.vue';
import { Search, KeyRound } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const props = defineProps({ users: Object, filters: Object });

const q = ref(props.filters?.q || '');
const role = ref(props.filters?.role || '');

function apply() {
    router.get(route('admin.users.index'), {
        q: q.value || undefined,
        role: role.value || undefined,
    }, { preserveState: true, replace: true });
}
let t;
watch(q, () => { clearTimeout(t); t = setTimeout(apply, 350); });

function setRole(user, newRole) {
    router.put(route('admin.users.role', user.id), { role: newRole }, { preserveScroll: true });
}
function setStatus(user, status) {
    router.put(route('admin.users.status', user.id), { status }, { preserveScroll: true });
}
function resetPw(user) {
    if (!confirm(`Reset password untuk ${user.full_name}?`)) return;
    router.post(route('admin.users.reset-password', user.id), {}, { preserveScroll: true });
}

const roleLabel = { superadmin: 'Super Admin', pustakawan: 'Pustakawan', anggota: 'Anggota' };
</script>

<template>
    <Head title="Kelola Pengguna" />
    <AppLayout title="Kelola Pengguna">
        <div class="mb-6 flex flex-wrap gap-3">
            <div class="relative min-w-[220px] flex-1">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400" />
                <Input v-model="q" placeholder="Cari nama, username, email..." class="pl-10" />
            </div>
            <select v-model="role" @change="apply" class="h-10 rounded-xl border-stone-200 bg-white text-sm dark:border-stone-700 dark:bg-stone-900">
                <option value="">Semua peran</option>
                <option value="superadmin">Super Admin</option>
                <option value="pustakawan">Pustakawan</option>
                <option value="anggota">Anggota</option>
            </select>
        </div>

        <Card>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-stone-100 text-left text-xs uppercase tracking-wider text-stone-500 dark:border-stone-800">
                            <th class="px-5 py-3 font-medium">Pengguna</th>
                            <th class="px-5 py-3 font-medium">Peran</th>
                            <th class="px-5 py-3 font-medium">Status</th>
                            <th class="px-5 py-3 font-medium text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 dark:divide-stone-800">
                        <tr v-for="u in users.data" :key="u.id" class="hover:bg-stone-50/50 dark:hover:bg-stone-800/30">
                            <td class="px-5 py-3.5">
                                <p class="font-semibold text-stone-900 dark:text-white">{{ u.full_name }}</p>
                                <p class="text-xs text-stone-500">@{{ u.username }} · {{ u.email }}</p>
                            </td>
                            <td class="px-5 py-3.5">
                                <select
                                    :value="u.role"
                                    @change="setRole(u, $event.target.value)"
                                    class="rounded-lg border-stone-200 bg-white text-xs dark:border-stone-700 dark:bg-stone-900"
                                >
                                    <option value="anggota">Anggota</option>
                                    <option value="pustakawan">Pustakawan</option>
                                    <option value="superadmin">Super Admin</option>
                                </select>
                            </td>
                            <td class="px-5 py-3.5">
                                <select
                                    :value="u.status"
                                    @change="setStatus(u, $event.target.value)"
                                    class="rounded-lg border-stone-200 bg-white text-xs dark:border-stone-700 dark:bg-stone-900"
                                >
                                    <option value="active">Aktif</option>
                                    <option value="suspended">Ditangguhkan</option>
                                    <option value="pending">Pending</option>
                                </select>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <Button size="sm" variant="outline" @click="resetPw(u)">
                                    <KeyRound class="h-3.5 w-3.5" /> Reset PW
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>
    </AppLayout>
</template>
