<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import FlashToasts from '@/Components/FlashToasts.vue';
import {
    BookOpen, LayoutDashboard, User, LogOut, Search, Menu, X, Moon, Sun,
    Library, ClipboardList, Settings, Users, BarChart3, QrCode,
    Star, Bell, Database, ScrollText
} from 'lucide-vue-next';

defineProps({ title: String });

const page = usePage();
const user = computed(() => page.props.auth?.user);
const libraryName = computed(() => page.props.library?.name || 'Perpustakaan Nusantara');
const menuOpen = ref(false);
const mobileNav = ref(false);
const menuRef = ref(null);
const isDark = ref(false);

function handleOutsideClick(event) {
    if (menuOpen.value && menuRef.value && !menuRef.value.contains(event.target)) {
        menuOpen.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', handleOutsideClick);
    const saved = localStorage.getItem('theme');
    isDark.value = saved === 'dark' || (!saved && window.matchMedia('(prefers-color-scheme: dark)').matches);
    document.documentElement.classList.toggle('dark', isDark.value);
    document.documentElement.dataset.theme = isDark.value ? 'dark' : 'light';
});
onUnmounted(() => document.removeEventListener('click', handleOutsideClick));

function toggleTheme() {
    isDark.value = !isDark.value;
    document.documentElement.classList.toggle('dark', isDark.value);
    document.documentElement.dataset.theme = isDark.value ? 'dark' : 'light';
    try { localStorage.setItem('theme', isDark.value ? 'dark' : 'light'); } catch (e) {}
}

function logout() {
    router.post(route('logout'));
}

const isStaff = computed(() => user.value?.is_staff || ['superadmin', 'pustakawan'].includes(user.value?.role));
const isAdmin = computed(() => user.value?.is_superadmin || user.value?.role === 'superadmin');

const navItems = computed(() => {
    const items = [
        { name: 'Dashboard', href: route('dashboard'), icon: LayoutDashboard },
        { name: 'Katalog', href: route('catalog.index'), icon: Search },
        { name: 'Notifikasi', href: route('notifications.index'), icon: Bell, badge: user.value?.unread_notifications },
    ];
    if (isStaff.value) {
        items.push(
            { name: 'Buku', href: route('staff.books.index'), icon: Library },
            { name: 'Peminjaman', href: route('staff.borrowings.index'), icon: ClipboardList },
            { name: 'Laporan', href: route('staff.reports.index'), icon: BarChart3 },
            { name: 'Label QR', href: route('staff.qr.index'), icon: QrCode },
            { name: 'Ulasan', href: route('staff.ratings.index'), icon: Star },
        );
    }
    if (isAdmin.value) {
        items.push(
            { name: 'Pengguna', href: route('admin.users.index'), icon: Users },
            { name: 'Pengaturan', href: route('admin.settings.edit'), icon: Settings },
            { name: 'Log Aktivitas', href: route('admin.activity.index'), icon: ScrollText },
            { name: 'Backup', href: route('admin.backup.index'), icon: Database },
        );
    }
    return items;
});
</script>

<template>
    <div class="min-h-screen bg-stone-50 dark:bg-[#0a1210]">
        <FlashToasts />

        <header class="sticky top-0 z-40 border-b border-stone-200/80 bg-white/80 backdrop-blur-xl dark:border-stone-800/80 dark:bg-stone-950/80">
            <div class="mx-auto flex max-w-[1400px] items-center gap-3 px-3 py-2.5 sm:px-5">
                <!-- Brand -->
                <Link :href="route('dashboard')" class="flex shrink-0 items-center gap-2 font-display font-bold tracking-tight text-stone-900 dark:text-white">
                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-gradient-to-br from-brand to-emerald-600 text-white shadow-md shadow-brand/20">
                        <BookOpen class="h-5 w-5" />
                    </span>
                    <span class="hidden max-w-[160px] truncate text-sm lg:inline xl:max-w-none">{{ libraryName }}</span>
                </Link>

                <!-- Nav with labels -->
                <nav class="ml-1 hidden min-w-0 flex-1 items-center gap-0.5 overflow-x-auto lg:flex">
                    <Link
                        v-for="item in navItems"
                        :key="item.name"
                        :href="item.href"
                        class="relative flex shrink-0 items-center gap-1.5 rounded-lg px-2.5 py-2 text-[13px] font-medium text-stone-600 transition hover:bg-stone-100 hover:text-stone-900 dark:text-stone-300 dark:hover:bg-stone-800 dark:hover:text-white"
                    >
                        <component :is="item.icon" class="h-4 w-4 shrink-0" />
                        <span>{{ item.name }}</span>
                        <span
                            v-if="item.badge"
                            class="ml-0.5 grid h-4 min-w-4 place-items-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white"
                        >{{ item.badge > 99 ? '99+' : item.badge }}</span>
                    </Link>
                </nav>

                <div class="ml-auto flex shrink-0 items-center gap-1">
                    <button type="button" class="grid h-9 w-9 place-items-center rounded-lg text-stone-500 transition hover:bg-stone-100 dark:hover:bg-stone-800" @click="toggleTheme" title="Mode gelap/terang">
                        <Sun v-if="isDark" class="h-4 w-4" />
                        <Moon v-else class="h-4 w-4" />
                    </button>

                    <div ref="menuRef" class="relative">
                        <button type="button" class="flex items-center gap-2 rounded-xl px-2 py-1.5 transition hover:bg-stone-100 dark:hover:bg-stone-800" @click="menuOpen = !menuOpen">
                            <span class="grid h-8 w-8 place-items-center rounded-full bg-brand/15 text-sm font-bold text-brand dark:bg-brand/25 dark:text-emerald-300">
                                {{ user?.initials || '?' }}
                            </span>
                            <span class="hidden text-sm font-medium text-stone-700 dark:text-stone-200 md:inline">{{ user?.full_name?.split(' ')[0] }}</span>
                        </button>
                        <div v-if="menuOpen" class="absolute right-0 mt-2 w-56 origin-top-right rounded-xl border border-stone-200 bg-white p-1.5 shadow-xl dark:border-stone-700 dark:bg-stone-900">
                            <div class="px-3 py-2.5">
                                <p class="font-semibold text-stone-900 dark:text-white">{{ user?.full_name }}</p>
                                <p class="text-xs text-stone-500">{{ user?.role_label }}</p>
                            </div>
                            <hr class="my-1 border-stone-100 dark:border-stone-800">
                            <Link :href="route('profile.edit')" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-stone-700 hover:bg-stone-100 dark:text-stone-200 dark:hover:bg-stone-800" @click="menuOpen = false">
                                <User class="h-4 w-4" /> Profil
                            </Link>
                            <button type="button" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/40" @click="logout">
                                <LogOut class="h-4 w-4" /> Keluar
                            </button>
                        </div>
                    </div>

                    <button type="button" class="grid h-9 w-9 place-items-center rounded-lg text-stone-500 lg:hidden" @click="mobileNav = !mobileNav">
                        <Menu v-if="!mobileNav" class="h-5 w-5" />
                        <X v-else class="h-5 w-5" />
                    </button>
                </div>
            </div>

            <!-- Mobile nav with full labels -->
            <div v-if="mobileNav" class="max-h-[70vh] overflow-y-auto border-t border-stone-100 px-3 py-2 lg:hidden dark:border-stone-800">
                <Link
                    v-for="item in navItems"
                    :key="item.name"
                    :href="item.href"
                    class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 text-sm font-medium text-stone-700 hover:bg-stone-100 dark:text-stone-200 dark:hover:bg-stone-800"
                    @click="mobileNav = false"
                >
                    <component :is="item.icon" class="h-4 w-4 shrink-0" />
                    {{ item.name }}
                    <span v-if="item.badge" class="ml-auto rounded-full bg-rose-500 px-1.5 text-[10px] font-bold text-white">{{ item.badge }}</span>
                </Link>
            </div>
        </header>

        <main class="mx-auto max-w-[1400px] px-4 py-8 sm:px-6">
            <h1 v-if="title" class="mb-6 font-display text-2xl font-semibold tracking-tight text-stone-900 dark:text-white">{{ title }}</h1>
            <slot />
        </main>
    </div>
</template>
