<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import axios from 'axios';

const emit = defineEmits(['order-placed']);

const form = useForm({
    symbol: 'BTC',
    side: 'Buy',
    price: '',
    amount: '',
});

const submit = () => {
    form.processing = true;
    form.clearErrors();

    axios.post('/api/orders', form.data())
        .then(() => {
            form.reset('price', 'amount');
            form.processing = false;
            emit('order-placed');
        })
        .catch(error => {
            form.processing = false;
            if (error.response?.status === 422) {
                // Map array of errors to strings as expected by Inertia form helper
                const errors: Record<string, string> = {};
                for (const key in error.response.data.errors) {
                    errors[key] = error.response.data.errors[key][0];
                }
                form.setError(errors);
            }
        });
};
</script>

<template>
    <div class="p-6 bg-white dark:bg-zinc-900 overflow-hidden shadow-xl sm:rounded-lg border border-gray-200 dark:border-zinc-800">
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Limit Order</h3>
        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Symbol</label>
                <select
                    v-model="form.symbol"
                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-zinc-700 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-zinc-800 dark:text-white"
                >
                    <option value="BTC">BTC</option>
                    <option value="ETH">ETH</option>
                </select>
                <InputError :message="form.errors.symbol" class="mt-2" />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Side</label>
                <select
                    v-model="form.side"
                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-zinc-700 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-zinc-800 dark:text-white"
                >
                    <option value="Buy">Buy</option>
                    <option value="Sell">Sell</option>
                </select>
                <InputError :message="form.errors.side" class="mt-2" />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Price</label>
                <input
                    type="number"
                    step="0.01"
                    v-model="form.price"
                    class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-zinc-700 rounded-md dark:bg-zinc-800 dark:text-white"
                    placeholder="0.00"
                />
                <InputError :message="form.errors.price" class="mt-2" />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Amount</label>
                <input
                    type="number"
                    step="0.00000001"
                    v-model="form.amount"
                    class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-zinc-700 rounded-md dark:bg-zinc-800 dark:text-white"
                    placeholder="0.00"
                />
                <InputError :message="form.errors.amount" class="mt-2" />
            </div>

            <div>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                >
                    Place Order
                </button>
            </div>
        </form>
    </div>
</template>
