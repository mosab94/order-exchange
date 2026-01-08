<script setup lang="ts">
import { ref, onMounted, computed, watch, onUnmounted } from 'vue';
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';

interface Asset {
    symbol: string;
    amount: string;
    locked_amount: string;
}

interface Order {
    id: number;
    symbol: { name: string };
    side: { name: string; value: number };
    status: { name: string; value: number };
    price: string;
    amount: string;
    created_at: string;
}

const props = defineProps<{
    user_id: number;
}>();

const balance = ref(0);
const assets = ref<Asset[]>([]);
const orders = ref<Order[]>([]);
const orderbook = ref<{ buy: Order[]; sell: Order[] }>({ buy: [], sell: [] });
const selectedSymbol = ref('BTC');
const symbolMap: Record<string, number> = { 'BTC': 1, 'ETH': 2 };
let activeChannel: string | null = null;

const fetchProfile = async () => {
    const response = await axios.get('/api/profile');
    balance.value = response.data.balance;
    assets.value = response.data.assets;
};

const fetchOrders = async () => {
    const response = await axios.get('/api/orders/history');
    orders.value = response.data;
};

const fetchOrderbook = async () => {
    const response = await axios.get('/api/orders', {
        params: { symbol: selectedSymbol.value },
    });
    orderbook.value = response.data;
};

const refresh = () => {
    fetchProfile();
    fetchOrders();
    fetchOrderbook();
};

const subscribeToOrderbook = () => {
    const symbolId = symbolMap[selectedSymbol.value];
    const channelName = `orderbook.${symbolId}`;

    if (activeChannel && activeChannel !== channelName) {
        window.Echo.leave(activeChannel);
    }

    if (activeChannel !== channelName) {
        activeChannel = channelName;
        window.Echo.channel(channelName)
            .listen('OrderbookUpdated', () => {
                fetchOrderbook();
            });
    }
};

watch(selectedSymbol, () => {
    fetchOrderbook();
    subscribeToOrderbook();
});

defineExpose({ refresh });

onMounted(() => {
    refresh();
    subscribeToOrderbook();

    // Listen for OrderMatched event
    window.Echo.private(`user.${props.user_id}`)
        .listen('OrderMatched', (e: any) => {
            console.log('OrderMatched', e);
            // Refresh data
            fetchProfile();
            fetchOrders();
            fetchOrderbook();
        });
});

onUnmounted(() => {
    if (activeChannel) {
        window.Echo.leave(activeChannel);
    }
});

const formatMoney = (value: string | number) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(Number(value));
};
</script>

<template>
    <div class="space-y-6">
        <!-- Balances -->
        <div class="bg-white dark:bg-zinc-900 overflow-hidden shadow-xl sm:rounded-lg border border-gray-200 dark:border-zinc-800 p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Wallet</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-4 bg-gray-50 dark:bg-zinc-800 rounded-lg">
                    <div class="text-sm text-gray-500 dark:text-gray-400">USD Balance</div>
                    <div class="text-xl font-bold text-gray-900 dark:text-white">{{ formatMoney(balance) }}</div>
                </div>
                <div v-for="asset in assets" :key="asset.symbol" class="p-4 bg-gray-50 dark:bg-zinc-800 rounded-lg">
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ asset.symbol }} Balance</div>
                    <div class="text-xl font-bold text-gray-900 dark:text-white">{{ asset.amount }} <span class="text-sm font-normal text-gray-500">Locked: {{ asset.locked_amount }}</span></div>
                </div>
            </div>
        </div>

        <!-- Orderbook -->
        <div class="bg-white dark:bg-zinc-900 overflow-hidden shadow-xl sm:rounded-lg border border-gray-200 dark:border-zinc-800 p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Orderbook</h3>
                <select v-model="selectedSymbol" class="text-sm border-gray-300 dark:border-zinc-700 rounded-md shadow-sm dark:bg-zinc-800 dark:text-white">
                    <option value="BTC">BTC</option>
                    <option value="ETH">ETH</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-8">
                <div>
                    <h4 class="text-md font-medium text-green-600 mb-2">Buy Orders</h4>
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                        <thead>
                            <tr>
                                <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Price</th>
                                <th class="text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                            <tr v-for="order in orderbook.buy" :key="order.id">
                                <td class="text-sm text-gray-900 dark:text-white">{{ formatMoney(order.price) }}</td>
                                <td class="text-sm text-right text-gray-900 dark:text-white">{{ order.amount }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div>
                    <h4 class="text-md font-medium text-red-600 mb-2">Sell Orders</h4>
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                        <thead>
                            <tr>
                                <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Price</th>
                                <th class="text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                            <tr v-for="order in orderbook.sell" :key="order.id">
                                <td class="text-sm text-gray-900 dark:text-white">{{ formatMoney(order.price) }}</td>
                                <td class="text-sm text-right text-gray-900 dark:text-white">{{ order.amount }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Orders -->
        <div class="bg-white dark:bg-zinc-900 overflow-hidden shadow-xl sm:rounded-lg border border-gray-200 dark:border-zinc-800 p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Your Orders</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                    <thead class="bg-gray-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Symbol</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Side</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-gray-200 dark:divide-zinc-700">
                        <tr v-for="order in orders" :key="order.id">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ order.symbol.name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm" :class="order.side.value === 1 ? 'text-green-600' : 'text-red-600'">
                                {{ order.side.name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ formatMoney(order.price) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ order.amount }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                    :class="{
                                        'bg-green-100 text-green-800': order.status.name === 'Filled',
                                        'bg-yellow-100 text-yellow-800': order.status.name === 'Open',
                                        'bg-red-100 text-red-800': order.status.name === 'Cancelled'
                                    }">
                                    {{ order.status.name }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ new Date(order.created_at).toLocaleString() }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
