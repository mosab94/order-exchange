<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import LimitOrderForm from '@/components/LimitOrderForm.vue';
import OrdersWalletOverview from '@/components/OrdersWalletOverview.vue';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const page = usePage();
const ordersWalletOverview = ref<InstanceType<typeof OrdersWalletOverview> | null>(null);
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-y-auto p-4">
            <div class="grid gap-4 md:grid-cols-3">
                <div class="md:col-span-1">
                    <LimitOrderForm @order-placed="ordersWalletOverview?.refresh()" />
                </div>
                <div class="md:col-span-2">
                    <OrdersWalletOverview ref="ordersWalletOverview" :user_id="page.props.auth.user.id" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
