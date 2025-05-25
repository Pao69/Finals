<template>
  <ion-page>
    <ion-tabs>
      <ion-router-outlet :aria-hidden="false"></ion-router-outlet>
      <ion-tab-bar slot="bottom">
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

        <ion-tab-button v-if="isAdmin" tab="admin" href="/tabs/admin">
          <ion-icon :icon="shieldOutline" />
          <ion-label>Admin</ion-label>
        </ion-tab-button>

        <ion-tab-button tab="settings" href="/tabs/settings">
          <ion-icon :icon="settingsOutline" />
          <ion-label>Settings</ion-label>
        </ion-tab-button>
      </ion-tab-bar>
    </ion-tabs>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { IonTabBar, IonTabButton, IonTabs, IonLabel, IonIcon, IonPage, IonRouterOutlet } from '@ionic/vue';
import { gridOutline, checkboxOutline, documentsOutline, settingsOutline, shieldOutline } from 'ionicons/icons';

const isAdmin = ref(false);

onMounted(() => {
  const userData = localStorage.getItem('user') || sessionStorage.getItem('user');
  if (userData) {
    const user = JSON.parse(userData);
    isAdmin.value = user.role === 'admin';
  }
});
</script>
