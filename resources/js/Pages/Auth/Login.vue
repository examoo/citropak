
<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Sign In - CitroPak DMS" />

    <div class="min-h-screen flex bg-white">
        <!-- Left Side - Form -->
        <div class="flex-1 flex items-center justify-center p-8 sm:p-12 lg:p-16">
            <div class="w-full max-w-md space-y-8">
                <!-- Mobile Logo (visible only on small screens) -->
                <div class="lg:hidden flex justify-center mb-8">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-lg shadow-emerald-500/30">
                        <span class="text-white font-bold text-xl">C</span>
                    </div>
                </div>

                <!-- Header -->
                <div class="text-center lg:text-left">
                    <h1 class="text-3xl font-bold tracking-tight text-gray-900">
                        Welcome back
                    </h1>
                    <p class="mt-2 text-sm text-gray-600">
                        Please enter your details to sign in.
                    </p>
                </div>

                <!-- Status Message -->
                <div v-if="status" class="p-4 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-700 text-sm font-medium flex items-center gap-3 animate-fade-in">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Email -->
                    <div class="space-y-2">
                        <label for="email" class="block text-sm font-medium text-gray-700">Email address</label>
                        <div class="relative">
                            <input
                                id="email"
                                type="email"
                                v-model="form.email"
                                required
                                autofocus
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 bg-gray-50/50 text-gray-900 placeholder:text-gray-400 focus:bg-white focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all duration-200 outline-none"
                                placeholder="Enter your email"
                            />
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                        </div>
                        <p v-if="form.errors.email" class="text-sm text-red-600 flex items-center gap-1.5 mt-1">
                            <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <!-- Password -->
                    <div class="space-y-2">
                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                        <div class="relative">
                            <input
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                v-model="form.password"
                                required
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 bg-gray-50/50 text-gray-900 placeholder:text-gray-400 focus:bg-white focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all duration-200 outline-none"
                                placeholder="••••••••"
                            />
                            <button 
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 transition-colors"
                            >
                                <svg v-if="!showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="text-sm text-red-600 flex items-center gap-1.5 mt-1">
                            <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 cursor-pointer group">
                            <div class="relative flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.remember"
                                    class="peer h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-600 transition cursor-pointer"
                                />
                            </div>
                            <span class="text-sm text-gray-600 group-hover:text-gray-900 transition-colors">Keep me signed in</span>
                        </label>

                        <Link 
                            v-if="canResetPassword"
                            :href="route('password.request')" 
                            class="text-sm font-medium text-emerald-600 hover:text-emerald-700 hover:underline"
                        >
                            Forgot password?
                        </Link>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        :class="[
                            'w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-lg text-sm font-semibold text-white transition-all duration-200 transform hover:-translate-y-0.5',
                            form.processing
                                ? 'bg-emerald-400 cursor-not-allowed shadow-none translate-y-0'
                                : 'bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 shadow-emerald-500/30'
                        ]"
                    >
                        <svg v-if="form.processing" class="w-5 h-5 mr-2 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ form.processing ? 'Signing in...' : 'Sign in' }}
                    </button>
                    
                    <div class="mt-6 text-center">
                        <p class="text-sm text-gray-500">
                            &copy; 2025 CitroPak DMS. All rights reserved.
                        </p>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Side - Visual -->
        <div class="hidden lg:flex flex-1 relative bg-slate-900 overflow-hidden">
            <!-- Background Image/Gradient -->
            <div class="absolute inset-0 bg-gradient-to-br from-emerald-600 via-teal-700 to-slate-900 opacity-90"></div>
            <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&q=80')] bg-cover bg-center mix-blend-overlay opacity-20"></div>
            
            <!-- Abstract Shapes -->
            <div class="absolute top-0 right-0 -mr-20 -mt-20 w-96 h-96 rounded-full bg-emerald-500 blur-3xl opacity-20 animate-pulse"></div>
            <div class="absolute bottom-0 left-0 -ml-20 -mb-20 w-80 h-80 rounded-full bg-teal-500 blur-3xl opacity-20 animate-pulse" style="animation-delay: 2s"></div>

            <!-- Content -->
            <div class="relative z-10 w-full flex flex-col justify-between p-16 text-white">
                <div>
                    <div class="w-16 h-16 rounded-2xl bg-white/10 backdrop-blur-lg flex items-center justify-center border border-white/10 shadow-xl mb-8">
                        <span class="font-bold text-3xl">C</span>
                    </div>
                </div>

                <div class="space-y-6">
                    <h2 class="text-4xl font-bold leading-tight">
                        Streamline your<br/>
                        distribution workflow.
                    </h2>
                    <p class="text-lg text-emerald-50/80 max-w-md">
                        Advanced distribution management system designed for efficiency, reliability, and scale.
                    </p>
                    
                    <!-- Feature Grid -->
                    <div class="grid grid-cols-2 gap-6 pt-8">
                        <div class="flex items-start gap-4 p-4 rounded-xl bg-white/5 border border-white/5 backdrop-blur-sm">
                            <div class="p-2 rounded-lg bg-emerald-500/20 text-emerald-300">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-white">Real-time</h3>
                                <p class="text-xs text-emerald-50/60 mt-1">Instant updates across all modules</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4 p-4 rounded-xl bg-white/5 border border-white/5 backdrop-blur-sm">
                            <div class="p-2 rounded-lg bg-teal-500/20 text-teal-300">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-white">Secure</h3>
                                <p class="text-xs text-emerald-50/60 mt-1">Enterprise-grade security standards</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 text-sm text-white/40">
                    <span>Powering distribution since 2025</span>
                </div>
            </div>
        </div>
    </div>
</template>
