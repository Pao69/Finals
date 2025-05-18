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

        <ion-tab-button tab="projects" href="/tabs/projects">
          <ion-icon :icon="folderOutline" />
          <ion-label>Projects</ion-label>
        </ion-tab-button>

        <ion-tab-button tab="settings" href="/tabs/settings">
          <ion-icon :icon="settingsOutline" />
          <ion-label>Settings</ion-label>
        </ion-tab-button>
      </ion-tab-bar>
    </ion-tabs>
  </ion-page>
</template>s

<script setup lang="ts">
import { IonTabBar, IonTabButton, IonTabs, IonLabel, IonIcon, IonPage, IonRouterOutlet } from '@ionic/vue';
import { gridOutline, checkboxOutline, folderOutline, settingsOutline } from 'ionicons/icons';
</script>
