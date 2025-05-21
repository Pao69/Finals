<template>
  <ion-page>
    <ion-header>
      <ion-toolbar :class="{ 'dark-toolbar': isDark }">
        <ion-buttons slot="start" v-if="showBackButton">
          <ion-back-button :default-href="backHref"></ion-back-button>
        </ion-buttons>
        <ion-title>{{ title }}</ion-title>
        <ion-buttons slot="end" v-if="title !== 'Settings'">
          <profile-avatar></profile-avatar>
        </ion-buttons>
      </ion-toolbar>
      <slot name="additional-toolbar"></slot>
    </ion-header>

    <ion-content :class="{ 'custom-content': isDark, 'dark-theme': isDark }">
      <slot></slot>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton } from '@ionic/vue';
import ProfileAvatar from './ProfileAvatar.vue';

interface Props {
  title: string;
  showBackButton?: boolean;
  backHref?: string;
  isDark?: boolean;
}

defineProps<Props>();
</script>

<style scoped>
.dark-toolbar {
  --background: #1a1a1a;
  --color: #ffffff;
}

.custom-content {
  --background: #121212;
}

.dark-theme {
  color: #ffffff;
}
</style> 