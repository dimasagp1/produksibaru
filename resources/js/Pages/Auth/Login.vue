<script setup>
import { ref, onMounted } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import {
    Mail,
    Lock,
    Eye,
    EyeOff,
    ArrowRight,
    Sun,
    Moon,
    AlertCircle,
} from 'lucide-vue-next';

const showPassword = ref(false);
const isDarkMode = ref(false);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
}

// Toggle Mode Terang / Gelap
function toggleTheme() {
    isDarkMode.value = !isDarkMode.value;
    if (isDarkMode.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
}

onMounted(() => {
    const savedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
        isDarkMode.value = true;
        document.documentElement.classList.add('dark');
    } else {
        isDarkMode.value = false;
        document.documentElement.classList.remove('dark');
    }
});
</script>

<template>
    <Head title="Masuk - Sistem Produksi Herbatech" />

    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 flex flex-col justify-center py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden transition-colors duration-200">
        <!-- Dekorasi Background Ambient Gradients -->
        <div class="absolute -top-32 -left-32 w-96 h-96 bg-emerald-500/10 dark:bg-emerald-500/5 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-indigo-500/10 dark:bg-indigo-500/5 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Tombol Toggle Tema (Pojok Kanan Atas) -->
        <div class="absolute top-6 right-6 z-20">
            <button
                type="button"
                @click="toggleTheme"
                class="p-2.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white shadow-sm transition hover:scale-105 active:scale-95 cursor-pointer"
                :title="isDarkMode ? 'Ganti ke Mode Terang' : 'Ganti ke Mode Gelap'"
            >
                <Sun v-if="isDarkMode" class="w-5 h-5 text-amber-400" />
                <Moon v-else class="w-5 h-5 text-indigo-600" />
            </button>
        </div>

        <div class="sm:mx-auto sm:w-full sm:max-w-md relative z-10 space-y-6">
            <!-- Header Brand Herbatech dengan Logo Gambar Resmi -->
            <div class="text-center space-y-3">
                <div class="flex justify-center mb-1">
                    <img
                        src="/images/logo-hbt.webp"
                        alt="Logo Herbatech"
                        class="h-14 md:h-16 w-auto max-w-[220px] object-contain drop-shadow-sm hover:scale-105 transition-transform duration-200"
                    />
                </div>
                <h2 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">
                    Sistem Manajemen Produksi
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto leading-relaxed">
                    Masuk dengan akun terdaftar untuk mengelola laporan shift, OEE, dan kontrol proses manufaktur.
                </p>
            </div>

            <!-- Card Form Login Utama -->
            <div class="bg-white/90 dark:bg-slate-900/90 backdrop-blur-md rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-2xl p-7 sm:p-8 space-y-5 transition-colors">
                <!-- Alert Kesalahan Login -->
                <div
                    v-if="form.errors.email || form.errors.password"
                    class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/70 border border-rose-300 dark:border-rose-800/80 text-rose-900 dark:text-rose-200 text-xs flex items-start gap-3 shadow-xs animate-in fade-in"
                >
                    <AlertCircle class="w-5 h-5 text-rose-600 dark:text-rose-400 shrink-0 mt-0.5" />
                    <div>
                        <span class="font-bold block">Gagal Masuk:</span>
                        <span>{{ form.errors.email || form.errors.password }}</span>
                    </div>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <!-- Input Email -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            Alamat Email
                        </label>
                        <div class="relative">
                            <Mail class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                            <input
                                type="email"
                                v-model="form.email"
                                required
                                autofocus
                                placeholder="nama@herbatech.co.id"
                                :class="[
                                    'w-full pl-10 pr-4 py-2.5 text-xs md:text-sm bg-slate-50 dark:bg-slate-800/80 border rounded-xl font-medium text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition',
                                    form.errors.email ? 'border-rose-500 ring-1 ring-rose-500' : 'border-slate-200 dark:border-slate-700'
                                ]"
                            />
                        </div>
                    </div>

                    <!-- Input Password -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            Kata Sandi
                        </label>
                        <div class="relative">
                            <Lock class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                            <input
                                :type="showPassword ? 'text' : 'password'"
                                v-model="form.password"
                                required
                                placeholder="••••••••"
                                :class="[
                                    'w-full pl-10 pr-10 py-2.5 text-xs md:text-sm bg-slate-50 dark:bg-slate-800/80 border rounded-xl font-medium text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition',
                                    form.errors.password ? 'border-rose-500 ring-1 ring-rose-500' : 'border-slate-200 dark:border-slate-700'
                                ]"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer p-1"
                                :title="showPassword ? 'Sembunyikan sandi' : 'Lihat sandi'"
                            >
                                <EyeOff v-if="showPassword" class="w-4 h-4" />
                                <Eye v-else class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center justify-between text-xs pt-1">
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <input
                                type="checkbox"
                                v-model="form.remember"
                                class="rounded border-slate-300 dark:border-slate-700 text-emerald-600 focus:ring-emerald-500 w-4 h-4"
                            />
                            <span class="text-slate-600 dark:text-slate-400 font-medium">Ingat saya di perangkat ini</span>
                        </label>
                    </div>

                    <!-- Tombol Masuk Submit -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full py-3 px-4 rounded-xl font-bold text-xs sm:text-sm text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 active:scale-[0.98] shadow-lg shadow-emerald-600/20 transition cursor-pointer flex items-center justify-center gap-2 disabled:opacity-50"
                    >
                        <span v-if="form.processing">Memverifikasi Data...</span>
                        <template v-else>
                            <span>Masuk ke Sistem</span>
                            <ArrowRight class="w-4 h-4" />
                        </template>
                    </button>
                </form>
            </div>

            <!-- Footer Hak Cipta & Info Pabrik -->
            <p class="text-center text-2xs text-slate-400 dark:text-slate-600 font-mono">
                &copy; {{ new Date().getFullYear() }} PT Herbatech Manufaktur. All rights reserved.
            </p>
        </div>
    </div>
</template>

