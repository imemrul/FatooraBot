<template>
    <div class="min-h-[60vh] flex items-center justify-center">
        <div class="text-center">
            <p class="text-7xl font-bold text-slate-200 dark:text-slate-700">403</p>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white mt-4">Access Denied</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-2">You don't have permission to view this page.</p>
            <router-link to="/">
                <SButton class="mt-6">Back to Dashboard</SButton>
            </router-link>
        </div>
    </div>
</template>
