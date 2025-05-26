<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start" v-if="showBackButton">
          <ion-back-button :default-href="defaultBackHref"></ion-back-button>
        </ion-buttons>
        <ion-title>{{ title }}</ion-title>
        <ion-buttons slot="end">
          <slot name="toolbar-buttons"></slot>
        </ion-buttons>
      </ion-toolbar>
      <!-- Slot for additional toolbar content -->
      <slot name="additional-toolbar"></slot>
    </ion-header>

    <!-- Main content slot -->
    <slot></slot>
  </ion-page>
</template>

<script setup lang="ts">
import {
  IonPage,
  IonHeader,
  IonToolbar,
  IonTitle,
  IonButtons,
  IonBackButton,
} from '@ionic/vue';

defineProps<{
  title: string;
  showBackButton?: boolean;
  defaultBackHref?: string;
}>();
</script>

<style scoped>
ion-toolbar {
  --background: var(--ion-toolbar-background);
}

ion-title {
  font-weight: 600;
}
</style> 