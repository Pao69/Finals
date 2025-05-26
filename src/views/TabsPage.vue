<template>
  <ion-page>
    <ion-tabs>
      <ion-router-outlet :aria-hidden="false"></ion-router-outlet>
      <ion-tab-bar slot="bottom">
        <!-- Admin View -->
        <template v-if="isAdmin">
          <ion-tab-button tab="admin" href="/tabs/admin">
            <ion-icon :icon="shieldOutline" />
            <ion-label>Admin</ion-label>
          </ion-tab-button>
          
          <ion-tab-button tab="report" href="/tabs/report">
            <ion-icon :icon="analyticsOutline" />
            <ion-label>Report</ion-label>
          </ion-tab-button>
          
          <ion-tab-button tab="settings" href="/tabs/settings">
            <ion-icon :icon="settingsOutline" />
            <ion-label>Settings</ion-label>
          </ion-tab-button>
        </template>

        <!-- Regular User View -->
        <template v-if="!isAdmin">
          <ion-tab-button tab="dashboard" href="/tabs/dashboard">
            <ion-icon :icon="gridOutline" />
            <ion-label>Dashboard</ion-label>
          </ion-tab-button>

          <ion-tab-button tab="tasks" href="/tabs/tasks">
            <ion-icon :icon="checkboxOutline" />
            <ion-label>Tasks</ion-label>
          </ion-tab-button>

          <ion-tab-button tab="resources" href="/tabs/resources">
            <ion-icon :icon="documentsOutline" />
            <ion-label>Resources</ion-label>
          </ion-tab-button>

          <ion-tab-button tab="settings" href="/tabs/settings">
            <ion-icon :icon="settingsOutline" />
            <ion-label>Settings</ion-label>
          </ion-tab-button>
        </template>

      </ion-tab-bar>
    </ion-tabs>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { IonTabBar, IonTabButton, IonTabs, IonLabel, IonIcon, IonPage, IonRouterOutlet } from '@ionic/vue';
import { 
  gridOutline, 
  checkboxOutline, 
  documentsOutline, 
  settingsOutline, 
  shieldOutline,
  analyticsOutline 
} from 'ionicons/icons';

const isAdmin = ref(false);

// Function to check admin status
const checkAdminStatus = () => {
  console.log('Checking admin status...');
  const userData = localStorage.getItem('user') || sessionStorage.getItem('user');
  console.log('User data from storage:', userData);
  
  if (userData) {
    const user = JSON.parse(userData);
    console.log('Parsed user data:', user);
    console.log('User role:', user.role);
    isAdmin.value = user.role === 'admin';
    console.log('Is admin?', isAdmin.value);
  } else {
    console.log('No user data found');
    isAdmin.value = false;
  }
};

// Add watch effect to update isAdmin when storage changes
watch(
  () => localStorage.getItem('user') || sessionStorage.getItem('user'),
  () => {
    console.log('Storage changed, rechecking admin status');
    checkAdminStatus();
  }
);

onMounted(() => {
  console.log('TabsPage mounted');
  checkAdminStatus();
});
</script>
