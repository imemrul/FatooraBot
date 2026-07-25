<template>
    <div>
        <SPageHeader title="Help Center" description="Learn how to use FatooraBot — tutorials, guides, and tips" />

        <!-- Tutorial Progress -->
        <SCard class="mb-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-semibold dark:text-white">📚 Your Learning Progress</h3>
                    <p class="text-sm text-slate-500 mt-0.5">Complete tutorials to master FatooraBot</p>
                </div>
                <div class="text-right">
                    <p class="text-2xl font-bold text-indigo-600">{{ progress.earned_points || 0 }}<span class="text-sm text-slate-400 font-normal">/{{ progress.total_points || 0 }} pts</span></p>
                    <p class="text-xs text-slate-400">{{ progress.completion_pct || 0 }}% complete</p>
                </div>
            </div>

            <!-- Progress bar -->
            <div class="w-full bg-slate-100 dark:bg-slate-700 rounded-full h-2 mb-4">
                <div class="bg-indigo-600 h-2 rounded-full transition-all" :style="{ width: (progress.completion_pct || 0) + '%' }" />
            </div>

            <!-- Tutorial cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                <div v-for="t in progress.tutorials" :key="t.key"
                    @click="startTutorial(t)"
                    class="p-4 rounded-xl border cursor-pointer transition hover:shadow-md"
                    :class="t.completed ? 'border-green-200 dark:border-green-800 bg-green-50/50 dark:bg-green-900/10' : 'border-slate-200 dark:border-slate-700 hover:border-indigo-300'">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-xl">{{ t.icon }}</span>
                        <span v-if="t.completed" class="text-xs text-green-600 font-medium">✅ Done</span>
                        <span v-else class="text-xs text-slate-400">{{ t.current_step }}/{{ t.total_steps }}</span>
                    </div>
                    <p class="text-sm font-medium text-slate-900 dark:text-white">{{ t.title }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">{{ t.description }}</p>
                    <div class="mt-2 flex gap-1">
                        <div v-for="i in t.total_steps" :key="i" class="h-1 flex-1 rounded-full"
                            :class="i <= t.current_step ? 'bg-indigo-500' : 'bg-slate-200 dark:bg-slate-600'" />
                    </div>
                </div>
            </div>
        </SCard>

        <!-- Search -->
        <SCard class="mb-6">
            <div class="flex gap-3">
                <SInput v-model="search" placeholder="Search help articles..." class="flex-1" @input="debouncedSearch" />
                <SSelect v-model="category" :options="categoryOpts" placeholder="All Categories" class="w-48" @change="fetchArticles" />
            </div>
        </SCard>

        <!-- Articles -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-for="a in articles" :key="a.id" @click="openArticle(a)"
                class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5 cursor-pointer hover:shadow-md hover:border-indigo-300 transition">
                <div class="flex items-start justify-between">
                    <div>
                        <SBadge class="mb-2">{{ categoryLabel(a.category) }}</SBadge>
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ a.title }}</h3>
                        <p class="text-xs text-slate-500 mt-1 line-clamp-2">{{ a.summary }}</p>
                    </div>
                    <span v-if="a.video_url" class="text-lg">🎥</span>
                </div>
                <div v-if="a.tags" class="flex gap-1 mt-3 flex-wrap">
                    <span v-for="tag in a.tags" :key="tag" class="text-[10px] px-1.5 py-0.5 bg-slate-100 dark:bg-slate-700 text-slate-500 rounded">{{ tag }}</span>
                </div>
            </div>
        </div>

        <!-- Article Modal -->
        <SModal :show="!!selectedArticle" :title="selectedArticle?.title" @close="selectedArticle = null" size="lg">
            <div v-if="selectedArticle">
                <SBadge class="mb-3">{{ categoryLabel(selectedArticle.category) }}</SBadge>

                <div v-if="selectedArticle.video_url" class="mb-4 rounded-lg overflow-hidden bg-black aspect-video">
                    <iframe :src="selectedArticle.video_url" class="w-full h-full" frameborder="0" allowfullscreen />
                </div>

                <div class="prose prose-sm dark:prose-invert max-w-none" v-html="renderMarkdown(selectedArticle.content)" />
            </div>
        </SModal>

        <!-- Tutorial Spotlight -->
        <TutorialSpotlight
            :steps="activeTutorialSteps"
            :active="showTutorial"
            @complete="completeTutorial"
            @dismiss="showTutorial = false"
            @step-change="onStepChange"
        />
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import TutorialSpotlight from '@/components/TutorialSpotlight.vue';
import api from '@/utils/api';

const progress = ref({});
const articles = ref([]);
const categories = ref([]);
const search = ref('');
const category = ref('');
const selectedArticle = ref(null);
const showTutorial = ref(false);
const activeTutorialKey = ref('');
const activeTutorialSteps = ref([]);

const categoryOpts = computed(() => [
    { value: '', label: 'All Categories' },
    ...categories.value.map(c => ({ value: c.key, label: c.label })),
]);

const categoryLabels = { getting_started: '🚀 Getting Started', invoices: '📄 Invoices', inventory: '📦 Inventory', reports: '📊 Reports', whatsapp: '📱 WhatsApp' };
const categoryLabel = (key) => categoryLabels[key] || key;

let timer;
const debouncedSearch = () => { clearTimeout(timer); timer = setTimeout(fetchArticles, 300); };

async function fetchProgress() {
    try { const { data } = await api.get('/tutorials/progress'); progress.value = data; } catch {}
}

async function fetchArticles() {
    const params = {};
    if (search.value) params.q = search.value;
    if (category.value) params.category = category.value;
    const { data } = await api.get('/help', { params });
    articles.value = data.articles;
}

async function fetchCategories() {
    const { data } = await api.get('/help/categories');
    categories.value = data.categories;
}

function openArticle(a) { selectedArticle.value = a; }

async function startTutorial(t) {
    if (t.completed) {
        await api.post(`/tutorials/${t.key}/reset`);
        await fetchProgress();
    }
    const { data } = await api.get(`/tutorials/${t.key}`);
    activeTutorialKey.value = t.key;
    activeTutorialSteps.value = data.tutorial.steps;
    showTutorial.value = true;
}

async function onStepChange() {
    await api.post(`/tutorials/${activeTutorialKey.value}/advance`);
}

async function completeTutorial() {
    await api.post(`/tutorials/${activeTutorialKey.value}/advance`);
    showTutorial.value = false;
    fetchProgress();
}

function renderMarkdown(md) {
    if (!md) return '';
    return md
        .replace(/^### (.+)$/gm, '<h3 class="text-base font-semibold mt-4 mb-2">$1</h3>')
        .replace(/^## (.+)$/gm, '<h2 class="text-lg font-bold mt-5 mb-2">$1</h2>')
        .replace(/^# (.+)$/gm, '<h1 class="text-xl font-bold mt-6 mb-3">$1</h1>')
        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.+?)\*/g, '<em>$1</em>')
        .replace(/`(.+?)`/g, '<code class="px-1 py-0.5 bg-slate-100 dark:bg-slate-700 rounded text-xs">$1</code>')
        .replace(/^- (.+)$/gm, '<li class="ml-4 list-disc text-sm">$1</li>')
        .replace(/^\d+\. (.+)$/gm, '<li class="ml-4 list-decimal text-sm">$1</li>')
        .replace(/^> (.+)$/gm, '<blockquote class="border-l-4 border-indigo-300 pl-3 py-1 my-2 text-sm text-slate-600 dark:text-slate-400 bg-indigo-50/50 dark:bg-indigo-900/10 rounded-r">$1</blockquote>')
        .replace(/\n{2,}/g, '<br/><br/>')
        .replace(/\n/g, '<br/>');
}

onMounted(() => { fetchProgress(); fetchArticles(); fetchCategories(); });
</script>
