// NOTE: The following comments are for educational/debugging purposes and may not cover all edge cases.
// main.ts - Entry point for the Vue/Ionic application. Sets up plugins, router, and mounts the app.
import { createApp } from 'vue'
import App from './App.vue'
import router from './router';
import { createPinia } from 'pinia';

import { IonicVue } from '@ionic/vue';
import { addIcons } from 'ionicons';
import { closeCircle, closeOutline, checkmarkCircle, checkmarkOutline, timeOutline, calendarOutline, 
         listOutline, documentTextOutline, clipboardOutline, addOutline, swapVerticalOutline,
         createOutline, trashOutline, documentsOutline, downloadOutline, imageOutline, documentOutline,
         gridOutline, checkboxOutline, settingsOutline, shieldOutline, personOutline, logOutOutline,
         saveOutline, cameraOutline, checkmarkCircleOutline, warningOutline, refreshOutline, linkOutline,
         analyticsOutline, filterOutline, shareOutline, statsChartOutline } from 'ionicons/icons';

/* Core CSS required for Ionic components to work properly */
import '@ionic/vue/css/core.css';

/* Basic CSS for apps built with Ionic */
import '@ionic/vue/css/normalize.css';
import '@ionic/vue/css/structure.css';
import '@ionic/vue/css/typography.css';

/* Optional CSS utils that can be commented out */
import '@ionic/vue/css/padding.css';
import '@ionic/vue/css/float-elements.css';
import '@ionic/vue/css/text-alignment.css';
import '@ionic/vue/css/text-transformation.css';
import '@ionic/vue/css/flex-utils.css';
import '@ionic/vue/css/display.css';

/**
 * Ionic Dark Mode
 * -----------------------------------------------------
 * For more info, please see:
 * https://ionicframework.com/docs/theming/dark-mode
 */

/* @import '@ionic/vue/css/palettes/dark.always.css'; */
/* @import '@ionic/vue/css/palettes/dark.class.css'; */
import '@ionic/vue/css/palettes/dark.system.css';

/* Theme variables */
import './theme/variables.css';

// Register Ionicons
// NOTE: Icon registration for use throughout the app.
addIcons({
  closeCircle, closeOutline, checkmarkCircle, checkmarkOutline, timeOutline, calendarOutline,
  listOutline, documentTextOutline, clipboardOutline, addOutline, swapVerticalOutline,
  createOutline, trashOutline, documentsOutline, downloadOutline, imageOutline, documentOutline,
  gridOutline, checkboxOutline, settingsOutline, shieldOutline, personOutline, logOutOutline,
  saveOutline, cameraOutline, checkmarkCircleOutline, warningOutline, refreshOutline, linkOutline,
  analyticsOutline, filterOutline, shareOutline, statsChartOutline
});

// NOTE: Pinia (state management), IonicVue, and router are registered here.
const pinia = createPinia();
const app = createApp(App)
  .use(IonicVue)
  .use(router)
  .use(pinia);

// NOTE: Wait for router to be ready before mounting the app.
router.isReady().then(() => {
  app.mount('#app');
});
