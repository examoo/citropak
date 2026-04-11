<script setup>
import { onMounted, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

const props = defineProps({
    bookers: Array,
});

const mapContainer = ref(null);
let map = null;

onMounted(() => {
    if (typeof L === 'undefined') {
        console.error('Leaflet is not loaded');
        return;
    }

    // Initialize map
    map = L.map(mapContainer.value).setView([31.5204, 74.3587], 13); // Default to Lahore coordinates (common for Citropak)

    // Fix for Leaflet default icons when using CDN
    delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.mergeOptions({
        iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
        iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
        shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    const markers = [];

    props.bookers.forEach(booker => {
        if (booker.lat && booker.lng) {
            const marker = L.marker([booker.lat, booker.lng])
                .addTo(map)
                .bindPopup(`
                    <div class="p-1">
                        <h3 class="font-bold border-b mb-1">${booker.name} (${booker.code})</h3>
                        <p class="text-xs"><b>Last Shop:</b> ${booker.last_shop}</p>
                        <p class="text-xs"><b>Van:</b> ${booker.van_code || 'N/A'}</p>
                        <p class="text-xs"><b>Updated:</b> ${booker.updated_at}</p>
                    </div>
                `);
            markers.push(marker);
        }
    });

    // Auto-fit bounds if we have markers
    if (markers.length > 0) {
        const group = new L.featureGroup(markers);
        map.fitBounds(group.getBounds().pad(0.1));
    }
});
</script>

<template>
    <Head title="Live Tracking" />

    <DashboardLayout>
        <div class="space-y-6">
            <div class="flex justify-between items-center no-print">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Order Booker Tracking</h1>
                    <p class="text-gray-500 text-sm mt-1">Live locations based on last shop visits</p>
                </div>
                <Link href="/dashboard" class="text-blue-600 hover:text-blue-800 text-sm">
                    ← Back to Dashboard
                </Link>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Data List -->
                <div class="lg:col-span-1 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col h-[70vh]">
                    <div class="p-4 border-b bg-gray-50/50">
                        <h2 class="font-semibold text-gray-700">Order Bookers</h2>
                    </div>
                    <div class="flex-1 overflow-y-auto divide-y divide-gray-100">
                        <div v-for="booker in bookers" :key="booker.id" 
                            class="p-4 hover:bg-emerald-50 cursor-pointer transition-colors"
                            @click="map?.setView([booker.lat, booker.lng], 16)"
                        >
                            <div class="flex justify-between items-start mb-1">
                                <span class="font-bold text-gray-900 text-sm">{{ booker.name }}</span>
                                <span class="text-[10px] bg-emerald-100 text-emerald-700 px-1.5 py-0.5 rounded-full font-medium">{{ booker.code }}</span>
                            </div>
                            <p class="text-[11px] text-gray-500">Last: <span class="text-gray-700 font-medium">{{ booker.last_shop }}</span></p>
                            <p class="text-[10px] text-gray-400 mt-1">{{ booker.updated_at }}</p>
                        </div>
                        <div v-if="bookers.length === 0" class="p-8 text-center text-gray-400 text-sm">
                            No bookers with active tracking found.
                        </div>
                    </div>
                </div>

                <!-- Map View -->
                <div class="lg:col-span-3 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden relative min-h-[500px] h-[70vh]">
                    <div ref="mapContainer" class="w-full h-full z-10"></div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<style>
/* Ensure map is visible */
.leaflet-container {
    width: 100%;
    height: 100%;
}
</style>
