<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import {
    BarChart3,
    ClipboardPen,
    ClipboardList,
    Layers,
    Factory,
    PanelLeftClose,
    PanelLeft,
    Menu,
    X as CloseIcon,
    Sun,
    Moon,
    User,
    LogOut,
    ShieldCheck,
    ChevronsUpDown,
    CheckCircle2,
    AlertCircle,
} from 'lucide-vue-next';

const page = usePage();
const flashDismissed = ref(false);

// Reset alert dismissal whenever a new flash message arrives & auto-dismiss after 6s
let flashTimer = null;
watch(
    () => [page.props.flash?.success, page.props.flash?.error],
    ([newSuccess, newError]) => {
        if (newSuccess || newError) {
            flashDismissed.value = false;
            if (flashTimer) clearTimeout(flashTimer);
            flashTimer = setTimeout(() => {
                flashDismissed.value = true;
            }, 6000);
        }
    },
    { deep: true, immediate: true }
);

// Desktop Sidebar Toggle (Expanded w-64 vs Collapsed w-20)
const isSidebarOpen = ref(true);

// Mobile Sidebar Drawer Toggle
const isMobileDrawerOpen = ref(false);

// Theme Toggle State (Light vs Dark)
const isDarkMode = ref(false);

// Profile User from Inertia Props (auth.user)
const user = computed(() => {
    return page.props.auth?.user || {
        name: 'Budi Santoso',
        email: 'supervisor@herbatech.co.id',
        role: 'Supervisor Produksi',
    };
});

// User Initials (e.g. BS for Budi Santoso)
const userInitials = computed(() => {
    if (!user.value?.name) return 'OP';
    const parts = user.value.name.trim().split(/\s+/);
    if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
});

// State Popup Logout Modal
const isLogoutModalOpen = ref(false);
const isLoggingOut = ref(false);

function openLogoutModal() {
    isLogoutModalOpen.value = true;
}

function closeLogoutModal() {
    isLogoutModalOpen.value = false;
}

function handleLogout() {
    isLoggingOut.value = true;
    router.post('/logout', {}, {
        onFinish: () => {
            isLoggingOut.value = false;
            isLogoutModalOpen.value = false;
        }
    });
}

onMounted(() => {
    // 1. Inisialisasi Tema (Dark / Light) dari LocalStorage atau System Preference
    const savedTheme = localStorage.getItem('hbt_theme');
    if (savedTheme) {
        isDarkMode.value = savedTheme === 'dark';
    } else {
        isDarkMode.value = window.matchMedia('(prefers-color-scheme: dark)').matches;
    }
    applyTheme(isDarkMode.value);

    // 2. Inisialisasi Status Sidebar dari LocalStorage
    const savedSidebar = localStorage.getItem('hbt_sidebar_open');
    if (savedSidebar !== null) {
        isSidebarOpen.value = savedSidebar === 'true';
    }
});

function toggleTheme() {
    isDarkMode.value = !isDarkMode.value;
    applyTheme(isDarkMode.value);
    localStorage.setItem('hbt_theme', isDarkMode.value ? 'dark' : 'light');
}

function applyTheme(dark) {
    if (dark) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
}

function toggleSidebar() {
    isSidebarOpen.value = !isSidebarOpen.value;
    localStorage.setItem('hbt_sidebar_open', String(isSidebarOpen.value));
}

function toggleMobileDrawer() {
    isMobileDrawerOpen.value = !isMobileDrawerOpen.value;
}

function closeMobileDrawer() {
    isMobileDrawerOpen.value = false;
}
</script>

<template>
    <div
        class="min-h-screen bg-slate-100/70 dark:bg-slate-950 text-slate-900 dark:text-slate-100 flex transition-colors duration-200"
        :style="{ '--sidebar-width': isSidebarOpen ? '16rem' : '5rem' }"
    >
        <!-- BACKDROP OVERLAY FOR MOBILE -->
        <div
            v-if="isMobileDrawerOpen"
            @click="closeMobileDrawer"
            class="fixed inset-0 bg-slate-950/60 backdrop-blur-2xs z-40 md:hidden transition-opacity"
        ></div>

        <!-- COLLAPSIBLE LEFT SIDEBAR (WARNA DISAMAKAN DENGAN KONTEN & DILEKAPI GARIS GRADIEN) -->
        <aside
            :class="[
                'fixed top-0 bottom-0 left-0 z-50 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 border-r border-slate-200 dark:border-slate-800 transition-all duration-300 flex flex-col justify-between shadow-xs dark:shadow-2xl',
                isSidebarOpen ? 'w-64' : 'w-20',
                isMobileDrawerOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'
            ]"
        >
            <!-- GRADIEN GARIS HANYA DI DARK MODE (DI LIGHT MODE TIDAK ADA GARIS BIRU) -->
            <div
                class="hidden dark:block absolute top-0 bottom-0 right-0 w-[2px] bg-gradient-to-b from-indigo-500 via-purple-500 to-indigo-500 shadow-[0_0_8px_rgba(99,102,241,0.5)] pointer-events-none"
            ></div>

            <!-- 1. Header Sidebar / Logo di Atas Sendiri -->
            <div
                :class="[
                    'h-16 flex items-center border-b border-slate-200 dark:border-slate-800 transition-all px-4',
                    isSidebarOpen ? 'justify-between' : 'justify-center'
                ]"
            >
                <!-- Saat Sidebar Terbuka: Logo Full di Atas Sendiri -->
                <Link
                    v-if="isSidebarOpen"
                    href="/dashboard"
                    class="flex items-center gap-2 overflow-hidden group py-1"
                    title="Produksi HBT - Dashboard"
                >
                    <img
                        src="/logo-hbt.webp"
                        alt="Logo HBT"
                        class="h-9 md:h-10 w-auto max-w-[155px] object-contain group-hover:brightness-105 transition"
                    />
                </Link>

                <!-- Saat Sidebar Dikecilkan: HANYA Huruf P Senter Dengan Layout Sidebar -->
                <Link
                    v-else
                    href="/dashboard"
                    class="flex items-center justify-center group"
                    title="Produksi HBT"
                >
                    <span class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-500/40 font-black text-xl font-mono flex items-center justify-center shadow-sm group-hover:bg-indigo-600 group-hover:text-white transition">
                        P
                    </span>
                </Link>

                <!-- Tombol Collapse Desktop (Saat Terbuka) -->
                <button
                    v-if="isSidebarOpen"
                    type="button"
                    @click="toggleSidebar"
                    class="hidden md:flex p-1.5 rounded-lg text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                    title="Tutup Sidebar"
                >
                    <PanelLeftClose class="w-5 h-5" />
                </button>

                <!-- Tombol Close Mobile (Saat Terbuka di HP) -->
                <button
                    v-if="isSidebarOpen"
                    type="button"
                    @click="closeMobileDrawer"
                    class="md:hidden p-1.5 rounded-lg text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                >
                    <CloseIcon class="w-5 h-5" />
                </button>
            </div>

            <!-- 2. Navigasi Menu Items -->
            <div class="flex-1 overflow-y-auto py-4 px-3 space-y-6">
                <!-- Group 1: Dashboard -->
                <div class="space-y-1">
                    <span v-show="isSidebarOpen" class="px-3 text-2xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 block mb-2">
                        Dashboard & Ringkasan
                    </span>

                    <Link
                        href="/dashboard"
                        :class="[
                            'flex items-center rounded-xl font-semibold text-xs md:text-sm transition group relative',
                            isSidebarOpen ? 'gap-3 px-3 py-2.5 justify-start' : 'p-2.5 justify-center',
                            $page.url.startsWith('/dashboard')
                                ? 'bg-indigo-600 text-white shadow-sm'
                                : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/80'
                        ]"
                        :title="!isSidebarOpen ? 'Dashboard OEE' : ''"
                    >
                        <BarChart3 class="w-5 h-5 shrink-0" />
                        <span v-show="isSidebarOpen" class="truncate">Dashboard OEE</span>
                    </Link>
                </div>

                <!-- Group 2: Operasional Shift Lini -->
                <div class="space-y-1">
                    <span v-show="isSidebarOpen" class="px-3 text-2xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 block mb-2">
                        Operasional Produksi
                    </span>

                    <!-- Tombol Input Laporan Highlighted -->
                    <Link
                        href="/reports/create"
                        :class="[
                            'flex items-center rounded-xl font-bold text-xs md:text-sm transition group border',
                            isSidebarOpen ? 'gap-3 px-3 py-2.5 justify-start' : 'p-2.5 justify-center',
                            $page.url.startsWith('/reports/create')
                                ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm'
                                : 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800/60 hover:bg-emerald-100 dark:hover:bg-emerald-900/50'
                        ]"
                        :title="!isSidebarOpen ? 'Input Laporan Baru' : ''"
                    >
                        <ClipboardPen class="w-5 h-5 shrink-0" />
                        <span v-show="isSidebarOpen" class="truncate">Input Laporan</span>
                    </Link>

                    <!-- Laporan Produksi Index -->
                    <Link
                        href="/reports"
                        :class="[
                            'flex items-center rounded-xl font-semibold text-xs md:text-sm transition group',
                            isSidebarOpen ? 'gap-3 px-3 py-2.5 justify-start' : 'p-2.5 justify-center',
                            ($page.url.startsWith('/reports') && !$page.url.startsWith('/reports/create'))
                                ? 'bg-indigo-600 text-white shadow-sm'
                                : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/80'
                        ]"
                        :title="!isSidebarOpen ? 'Laporan Produksi' : ''"
                    >
                        <ClipboardList class="w-5 h-5 shrink-0" />
                        <span v-show="isSidebarOpen" class="truncate">Laporan Produksi</span>
                    </Link>

                    <!-- Manajemen Batch (Khusus Admin) -->
                    <Link
                        v-if="$page.props.auth?.user?.can_manage_batches"
                        href="/batches"
                        :class="[
                            'flex items-center rounded-xl font-semibold text-xs md:text-sm transition group',
                            isSidebarOpen ? 'gap-3 px-3 py-2.5 justify-start' : 'p-2.5 justify-center',
                            $page.url.startsWith('/batches')
                                ? 'bg-indigo-600 text-white shadow-sm'
                                : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/80'
                        ]"
                        :title="!isSidebarOpen ? 'Manajemen Batch' : ''"
                    >
                        <Layers class="w-5 h-5 shrink-0" />
                        <span v-show="isSidebarOpen" class="truncate">Manajemen Batch</span>
                    </Link>
                </div>

                <!-- Group 3: Standar & Mesin Pabrik (Khusus Admin) -->
                <div v-if="$page.props.auth?.user?.can_manage_master" class="space-y-1">
                    <span v-show="isSidebarOpen" class="px-3 text-2xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 block mb-2">
                        Standar Manufaktur
                    </span>

                    <Link
                        href="/master/products"
                        :class="[
                            'flex items-center rounded-xl font-semibold text-xs md:text-sm transition group',
                            isSidebarOpen ? 'gap-3 px-3 py-2.5 justify-start' : 'p-2.5 justify-center',
                            $page.url.startsWith('/master')
                                ? 'bg-indigo-600 text-white shadow-sm'
                                : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/80'
                        ]"
                        :title="!isSidebarOpen ? 'Standar & Mesin' : ''"
                    >
                        <Factory class="w-5 h-5 shrink-0" />
                        <span v-show="isSidebarOpen" class="truncate">Standar & Mesin</span>
                    </Link>
                </div>
            </div>

            <!-- 3. Footer Sidebar: User Profile & Collapse Toggle -->
            <div class="p-3 border-t border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/50 space-y-2">
                <!-- USER PROFILE BUTTON (SIDEBAR EXPANDED) -->
                <button
                    v-if="isSidebarOpen"
                    type="button"
                    @click="openLogoutModal"
                    class="w-full flex items-center justify-between p-2 rounded-xl text-left hover:bg-slate-200/60 dark:hover:bg-slate-800/80 transition group border border-transparent hover:border-slate-200 dark:hover:border-slate-700/60 cursor-pointer"
                    title="Klik untuk membuka popup profil & logout"
                >
                    <div class="flex items-center gap-2.5 min-w-0">
                        <!-- Avatar Initials & Indikator Online -->
                        <div class="relative shrink-0">
                            <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-indigo-600 to-violet-500 text-white font-bold text-xs flex items-center justify-center shadow-xs">
                                {{ userInitials }}
                            </div>
                            <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-emerald-500 border-2 border-white dark:border-slate-900 ring-1 ring-emerald-400"></span>
                        </div>

                        <!-- Info User -->
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-bold text-slate-900 dark:text-slate-100 truncate group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                {{ user.name }}
                            </p>
                            <div class="flex items-center mt-0.5">
                                <span :class="[
                                    'inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[10px] font-semibold border leading-tight',
                                    user.role === 'admin' 
                                        ? 'bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 border-purple-200/80 dark:border-purple-800/60'
                                        : 'bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300 border-teal-200/80 dark:border-teal-800/60'
                                ]">
                                    <ShieldCheck class="w-2.5 h-2.5 shrink-0" />
                                    <span>{{ user.role === 'admin' ? 'Administrator' : 'Team Leader' }}</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Icon Expand -->
                    <ChevronsUpDown class="w-4 h-4 text-slate-400 group-hover:text-slate-600 dark:group-hover:text-slate-300 shrink-0 transition" />
                </button>

                <!-- USER PROFILE BUTTON (SIDEBAR COLLAPSED) -->
                <button
                    v-else
                    type="button"
                    @click="openLogoutModal"
                    class="w-full flex items-center justify-center py-2 rounded-xl hover:bg-slate-200/60 dark:hover:bg-slate-800 transition group cursor-pointer"
                    :title="`${user.name} (${user.role}) - Klik untuk Keluar/Logout`"
                >
                    <div class="relative">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-indigo-600 to-violet-500 text-white font-bold text-xs flex items-center justify-center shadow-xs group-hover:scale-105 transition-transform">
                            {{ userInitials }}
                        </div>
                        <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-emerald-500 border-2 border-white dark:border-slate-900 ring-1 ring-emerald-400"></span>
                    </div>
                </button>

                <!-- Tombol Collapse Sidebar -->
                <button
                    type="button"
                    @click="toggleSidebar"
                    class="w-full flex items-center justify-center gap-2 py-2 px-2 rounded-xl text-xs font-semibold text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200/60 dark:hover:bg-slate-800 transition cursor-pointer"
                    :title="isSidebarOpen ? 'Sembunyikan Sidebar' : 'Buka Sidebar'"
                >
                    <PanelLeftClose v-if="isSidebarOpen" class="w-4 h-4 shrink-0" />
                    <PanelLeft v-else class="w-5 h-5 shrink-0" />
                    <span v-show="isSidebarOpen" class="font-mono text-2xs">Sembunyikan Sidebar</span>
                </button>
            </div>
        </aside>

        <!-- MAIN AREA (OFFSET OLEH LEBAR SIDEBAR) -->
        <div
            :class="[
                'flex-1 flex flex-col min-w-0 transition-all duration-300 pb-20 md:pb-28',
                isSidebarOpen ? 'md:ml-64' : 'md:ml-20'
            ]"
        >
            <!-- TOP APP HEADER -->
            <header class="h-16 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 sticky top-0 z-30 shadow-2xs px-3 sm:px-6 lg:px-8 flex items-center justify-between transition-colors duration-200">
                <!-- Sisi Kiri: Tombol Toggle Sidebar & Judul -->
                <div class="flex items-center gap-2 sm:gap-3">
                    <!-- Tombol Hamburger Mobile -->
                    <button
                        type="button"
                        @click="toggleMobileDrawer"
                        class="md:hidden p-2 rounded-xl text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                        title="Buka Menu"
                    >
                        <Menu class="w-5 h-5" />
                    </button>

                    <!-- Tombol Toggle Desktop: Muncul saat sidebar tertutup untuk membukanya kembali -->
                    <button
                        v-if="!isSidebarOpen"
                        type="button"
                        @click="toggleSidebar"
                        class="hidden md:flex p-2 rounded-xl text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                        title="Buka Sidebar"
                    >
                        <PanelLeft class="w-5 h-5 text-slate-600 dark:text-slate-300" />
                    </button>

                    <!-- Breadcrumbs Ringkas -->
                    <div class="hidden sm:block">
                        <span class="text-xs text-slate-500 dark:text-slate-400 font-mono">Pabrik Produksi HBT</span>
                        <h2 class="text-sm font-bold text-slate-900 dark:text-slate-100 leading-tight">
                            Plant A & Plant B (Mixing, Filling, Sekunder)
                        </h2>
                    </div>

                    <!-- Label Ringkas di Mobile -->
                    <div class="sm:hidden min-w-0">
                        <h2 class="text-xs font-bold text-slate-900 dark:text-slate-100 truncate">
                            Produksi HBT
                        </h2>
                    </div>
                </div>

                <!-- Sisi Kanan: Status, Toggle Dark/Light & Shortcut -->
                <div class="flex items-center gap-1.5 sm:gap-3">
                    <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-mono font-semibold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Plant Online
                    </span>

                    <!-- TOGGLE DARK / LIGHT THEME BUTTON -->
                    <button
                        type="button"
                        @click="toggleTheme"
                        class="p-2 sm:px-3 sm:py-1.5 rounded-xl text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 transition cursor-pointer flex items-center gap-1.5 text-xs font-semibold shadow-2xs"
                        :title="isDarkMode ? 'Beralih ke Mode Terang' : 'Beralih ke Mode Gelap'"
                    >
                        <Sun v-if="isDarkMode" class="w-4 h-4 text-amber-400" />
                        <Moon v-else class="w-4 h-4 text-indigo-600" />
                        <span class="hidden md:inline">{{ isDarkMode ? 'Mode Terang' : 'Mode Gelap' }}</span>
                    </button>

                    <!-- Shortcut Tombol Input Laporan (Hanya muncul jika tidak di halaman create) -->
                    <Link
                        v-if="!$page.url.startsWith('/reports/create')"
                        href="/reports/create"
                        class="btn-primary text-xs py-1.5 px-2.5 sm:px-3 flex items-center gap-1.5"
                        title="Input Laporan Baru"
                    >
                        <ClipboardPen class="w-4 h-4 shrink-0" />
                        <span class="hidden sm:inline">+ Input Laporan</span>
                    </Link>
                </div>
            </header>

            <!-- KONTEN UTAMA HALAMAN -->
            <main class="flex-1 w-full px-3.5 sm:px-6 lg:px-8 py-4 sm:py-6">
                <!-- Global Flash Alert: Success -->
                <div
                    v-if="$page.props.flash?.success && !flashDismissed"
                    class="mb-5 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/70 border border-emerald-300 dark:border-emerald-800 text-emerald-900 dark:text-emerald-100 flex items-center justify-between gap-3 shadow-xs transition-all animate-in fade-in"
                >
                    <div class="flex items-center gap-3">
                        <CheckCircle2 class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                        <div>
                            <span class="text-xs font-bold block">Berhasil!</span>
                            <span class="text-xs font-medium">{{ $page.props.flash.success }}</span>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="flashDismissed = true"
                        class="p-1 rounded-md text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 cursor-pointer"
                        title="Tutup Notifikasi"
                    >
                        <CloseIcon class="w-4 h-4" />
                    </button>
                </div>

                <!-- Global Flash Alert: Error -->
                <div
                    v-if="$page.props.flash?.error && !flashDismissed"
                    class="mb-5 p-4 rounded-xl bg-rose-50 dark:bg-rose-950/70 border border-rose-300 dark:border-rose-800 text-rose-900 dark:text-rose-100 flex items-center justify-between gap-3 shadow-xs transition-all animate-in fade-in"
                >
                    <div class="flex items-center gap-3">
                        <AlertCircle class="w-5 h-5 text-rose-600 dark:text-rose-400 shrink-0" />
                        <div>
                            <span class="text-xs font-bold block">Terjadi Kendala!</span>
                            <span class="text-xs font-medium">{{ $page.props.flash.error }}</span>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="flashDismissed = true"
                        class="p-1 rounded-md text-rose-700 dark:text-rose-300 hover:bg-rose-100 dark:hover:bg-rose-900/60 cursor-pointer"
                        title="Tutup Notifikasi"
                    >
                        <CloseIcon class="w-4 h-4" />
                    </button>
                </div>

                <slot />
            </main>
        </div>

        <!-- FLOATING TOAST NOTIFICATION (POJOK KANAN ATAS - DIRECT FIXED POSITION WITHOUT RISKY TELEPORT) -->
        <div class="fixed top-5 right-5 z-50 pointer-events-none space-y-2">
            <!-- Toast Success -->
            <Transition
                enter-active-class="transform transition ease-out duration-300"
                enter-from-class="translate-y-[-20px] opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div
                    v-if="$page.props.flash?.success && !flashDismissed"
                    class="pointer-events-auto max-w-sm sm:max-w-md w-full bg-emerald-600 text-white p-4 rounded-2xl shadow-2xl flex items-start gap-3 border border-emerald-400/40 backdrop-blur-md"
                >
                    <CheckCircle2 class="w-6 h-6 text-white shrink-0 mt-0.5" />
                    <div class="flex-1 min-w-0">
                        <h4 class="text-xs font-black uppercase tracking-wider text-emerald-100">Berhasil Disimpan</h4>
                        <p class="text-xs font-semibold text-white mt-0.5 leading-relaxed">{{ $page.props.flash.success }}</p>
                    </div>
                    <button
                        type="button"
                        @click="flashDismissed = true"
                        class="p-1 rounded-lg text-emerald-100 hover:bg-white/20 transition cursor-pointer shrink-0"
                        title="Tutup Notifikasi"
                    >
                        <CloseIcon class="w-5 h-5" />
                    </button>
                </div>
            </Transition>

            <!-- Toast Error -->
            <Transition
                enter-active-class="transform transition ease-out duration-300"
                enter-from-class="translate-y-[-20px] opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div
                    v-if="$page.props.flash?.error && !flashDismissed"
                    class="pointer-events-auto max-w-sm sm:max-w-md w-full bg-rose-600 text-white p-4 rounded-2xl shadow-2xl flex items-start gap-3 border border-rose-400/40 backdrop-blur-md"
                >
                    <AlertCircle class="w-6 h-6 text-white shrink-0 mt-0.5" />
                    <div class="flex-1 min-w-0">
                        <h4 class="text-xs font-black uppercase tracking-wider text-rose-100">Pemberitahuan</h4>
                        <p class="text-xs font-semibold text-white mt-0.5 leading-relaxed">{{ $page.props.flash.error }}</p>
                    </div>
                    <button
                        type="button"
                        @click="flashDismissed = true"
                        class="p-1 rounded-lg text-rose-100 hover:bg-white/20 transition cursor-pointer shrink-0"
                        title="Tutup Notifikasi"
                    >
                        <CloseIcon class="w-5 h-5" />
                    </button>
                </div>
            </Transition>
        </div>

        <!-- POPUP MODAL LOGOUT USER (HANYA DITELEPORT SAAT MODAL AKTIF) -->
        <Teleport to="body" v-if="isLogoutModalOpen">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs"
                    @click.self="closeLogoutModal"
                >
                    <div
                        class="bg-white dark:bg-slate-900 w-full max-w-sm rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden p-6 space-y-5 text-left"
                    >
                        <!-- Header Popup: User Info & Tombol Close -->
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <div class="relative">
                                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-600 to-violet-500 text-white font-black text-base flex items-center justify-center shadow-md">
                                        {{ userInitials }}
                                    </div>
                                    <span class="absolute -bottom-1 -right-1 w-3.5 h-3.5 rounded-full bg-emerald-500 border-2 border-white dark:border-slate-900 ring-1 ring-emerald-400"></span>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-base font-bold text-slate-900 dark:text-slate-100 truncate">
                                        {{ user.name }}
                                    </h3>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 font-mono truncate">
                                        {{ user.email }}
                                    </p>
                                    <span :class="[
                                        'inline-flex items-center gap-1 mt-1 px-2 py-0.5 rounded-md text-2xs font-semibold border',
                                        user.role === 'admin'
                                            ? 'bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-400 border-purple-200 dark:border-purple-800/60'
                                            : 'bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-400 border-teal-200 dark:border-teal-800/60'
                                    ]">
                                        <ShieldCheck class="w-3 h-3" />
                                        {{ user.role_label || (user.role === 'admin' ? 'Administrator' : 'Team Leader') }}
                                    </span>
                                </div>
                            </div>

                            <button
                                type="button"
                                @click="closeLogoutModal"
                                class="p-1 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                                title="Tutup"
                            >
                                <CloseIcon class="w-5 h-5" />
                            </button>
                        </div>

                        <!-- Card Detail Sesi & Plant -->
                        <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-800 space-y-2 text-xs">
                            <div class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                                <span>Unit Manufaktur:</span>
                                <span class="font-bold text-slate-900 dark:text-slate-200">Plant A & Plant B</span>
                            </div>
                            <div class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                                <span>Sesi Shift:</span>
                                <span class="font-semibold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    Shift 1 (07:00 - 15:30)
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-slate-600 dark:text-slate-400 pt-1 border-t border-slate-200/60 dark:border-slate-700/60">
                                <span>Tema Tampilan:</span>
                                <button
                                    type="button"
                                    @click="toggleTheme"
                                    class="font-semibold flex items-center gap-1 text-indigo-600 dark:text-indigo-400 hover:underline cursor-pointer"
                                >
                                    <Sun v-if="isDarkMode" class="w-3.5 h-3.5 text-amber-500" />
                                    <Moon v-else class="w-3.5 h-3.5 text-indigo-600" />
                                    <span>{{ isDarkMode ? 'Dark' : 'Light' }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Pesan Konfirmasi Keluar -->
                        <div class="border-t border-slate-100 dark:border-slate-800 pt-2 text-center space-y-1">
                            <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">
                                Apakah Anda yakin ingin keluar dari sistem?
                            </p>
                            <p class="text-2xs text-slate-500 dark:text-slate-400">
                                Seluruh laporan produksi yang telah disimpan tetap tersimpan di server.
                            </p>
                        </div>

                        <!-- Action Buttons: Batal & Logout -->
                        <div class="grid grid-cols-2 gap-3 pt-1">
                            <button
                                type="button"
                                @click="closeLogoutModal"
                                class="w-full py-2.5 px-3 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                @click="handleLogout"
                                :disabled="isLoggingOut"
                                class="w-full py-2.5 px-3 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-sm disabled:opacity-50 cursor-pointer"
                            >
                                <LogOut class="w-4 h-4 shrink-0" />
                                <span>{{ isLoggingOut ? 'Keluar...' : 'Keluar / Logout' }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
