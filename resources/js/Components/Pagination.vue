<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <div v-if="links.length > 3" class="flex flex-wrap items-center justify-center gap-1 mt-6">
        <template v-for="(link, key) in links" :key="key">
            <div
                v-if="link.url === null"
                class="px-4 py-2 text-sm text-gray-400 bg-white border border-gray-100 rounded-lg"
                v-html="link.label"
            />
            <Link
                v-else
                :href="link.url"
                class="px-4 py-2 text-sm font-medium transition-all duration-200 border rounded-lg"
                :class="[
                    link.active
                        ? 'bg-emerald-600 text-white border-emerald-600 shadow-md shadow-emerald-500/20'
                        : 'bg-white text-gray-700 border-gray-100 hover:bg-gray-50 hover:text-emerald-600 hover:border-emerald-200'
                ]"
                v-html="link.label"
            />
        </template>
    </div>
</template>
