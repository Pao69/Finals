<!--
  NOTE: The following comments are for educational/debugging purposes and may not cover all edge cases.
  App.vue - Root component for the Vue/Ionic application. Renders the router outlet.
-->
<template>
  <ion-app>
    <ion-router-outlet :aria-hidden="false" />
  </ion-app>
</template>

<script setup lang="ts">
// NOTE: Imports for Ionic components.
import { IonApp, IonRouterOutlet } from '@ionic/vue';
</script>
