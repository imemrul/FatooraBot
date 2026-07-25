import '../css/app.css';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import router from './router';
import App from './App.vue';
import { vCan, vRole } from './directives/permission';

// Components
import SPageHeader from './components/SPageHeader.vue';
import SCard from './components/SCard.vue';
import SButton from './components/SButton.vue';
import SBadge from './components/SBadge.vue';
import SModal from './components/SModal.vue';
import STable from './components/STable.vue';
import SInput from './components/SInput.vue';
import SSelect from './components/SSelect.vue';
import SStatCard from './components/SStatCard.vue';
import SAlert from './components/SAlert.vue';
import STabs from './components/STabs.vue';
import HelpTooltip from './components/HelpTooltip.vue';

const app = createApp(App);
app.use(createPinia());
app.use(router);

app.directive('can', vCan);
app.directive('role', vRole);

app.component('SPageHeader', SPageHeader);
app.component('SCard', SCard);
app.component('SButton', SButton);
app.component('SBadge', SBadge);
app.component('SModal', SModal);
app.component('STable', STable);
app.component('SInput', SInput);
app.component('SSelect', SSelect);
app.component('SStatCard', SStatCard);
app.component('SAlert', SAlert);
app.component('STabs', STabs);
app.component('HelpTooltip', HelpTooltip);

app.mount('#app');
