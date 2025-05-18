<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-title>Projects</ion-title>
      </ion-toolbar>
    </ion-header>
    <ion-content :fullscreen="true" class="ion-padding-bottom">
      <ion-header collapse="condense">
        <ion-toolbar>
          <ion-title size="large">Projects</ion-title>
        </ion-toolbar>
      </ion-header>
      
      <div class="ion-padding">
        <ion-grid>
          <ion-row>
            <ion-col size="12" size-md="6" v-for="project in projects" :key="project.id">
              <ion-card>
                <ion-card-header>
                  <ion-card-subtitle>{{ project.status }}</ion-card-subtitle>
                  <ion-card-title>{{ project.name }}</ion-card-title>
                </ion-card-header>
                <ion-card-content>
                  <p>{{ project.description }}</p>
                  <ion-progress-bar :value="project.progress" :color="project.color"></ion-progress-bar>
                  <div class="project-stats ion-margin-top">
                    <ion-chip>
                      <ion-icon :icon="peopleOutline"></ion-icon>
                      <ion-label>{{ project.teamSize }} members</ion-label>
                    </ion-chip>
                    <ion-chip>
                      <ion-icon :icon="calendarOutline"></ion-icon>
                      <ion-label>Due {{ project.dueDate }}</ion-label>
                    </ion-chip>
                  </div>
                </ion-card-content>
              </ion-card>
            </ion-col>
          </ion-row>
        </ion-grid>

        <ion-fab vertical="bottom" horizontal="end" slot="fixed" class="ion-margin-bottom ion-margin-end">
          <ion-fab-button>
            <ion-icon :icon="addOutline"></ion-icon>
          </ion-fab-button>
        </ion-fab>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonGrid, IonRow, IonCol,
         IonCard, IonCardHeader, IonCardTitle, IonCardSubtitle, IonCardContent, 
         IonProgressBar, IonChip, IonIcon, IonLabel, IonFab, IonFabButton } from '@ionic/vue';
import { peopleOutline, calendarOutline, addOutline } from 'ionicons/icons';
import { ref } from 'vue';

const projects = ref([
  {
    id: 1,
    name: 'Project Alpha',
    description: 'A comprehensive web application for task management',
    status: 'In Progress',
    progress: 0.7,
    color: 'primary',
    teamSize: 5,
    dueDate: 'Dec 31'
  },
  {
    id: 2,
    name: 'Project Beta',
    description: 'Mobile app development for client tracking',
    status: 'Planning',
    progress: 0.3,
    color: 'secondary',
    teamSize: 4,
    dueDate: 'Jan 15'
  },
  {
    id: 3,
    name: 'Project Gamma',
    description: 'Database optimization and restructuring',
    status: 'Review',
    progress: 0.9,
    color: 'tertiary',
    teamSize: 3,
    dueDate: 'Dec 20'
  }
]);
</script>

<style scoped>
.ion-padding-bottom {
  padding-bottom: 100px; /* Increased padding to ensure visibility of the FAB button */
}

/* Additional styles to ensure FAB button visibility */
ion-fab {
  position: fixed;
  bottom: 20px; /* Adjusted to be lower */
  right: 20px;
}
</style>