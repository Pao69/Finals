<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start" v-if="showBackButton">
          <ion-back-button :default-href="backHref"></ion-back-button>
        </ion-buttons>
        <ion-title>{{ title }}</ion-title>
      </ion-toolbar>
      <!-- Additional toolbar slot -->
      <slot name="additional-toolbar"></slot>
    </ion-header>
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
  IonBackButton
} from '@ionic/vue';

defineProps<{
  title: string;
  showBackButton?: boolean;
  backHref?: string;
}>();
</script>

<style scoped>
ion-toolbar {
  --background: var(--ion-color-light);
  --border-color: var(--ion-color-light-shade);
}

ion-title {
  font-size: 1.2rem;
  font-weight: 600;
  color: var(--ion-color-dark);
}

@media (prefers-color-scheme: dark) {
  ion-toolbar {
    --background: var(--ion-color-dark);
    --border-color: var(--ion-color-dark-shade);
  }

  ion-title {
    color: var(--ion-color-light);
  }
}
</style> 