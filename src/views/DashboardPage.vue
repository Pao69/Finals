<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-title>Dashboard</ion-title>
      </ion-toolbar>
    </ion-header>
    <ion-content :fullscreen="true">
      <ion-header collapse="condense">
        <ion-toolbar>
          <ion-title size="large">Dashboard</ion-title>
        </ion-toolbar>
      </ion-header>
      
      <div class="ion-padding">
        <ion-grid>
          <ion-row>
            <ion-col size="12" size-md="6">
              <ion-card>
                <ion-card-header>
                  <ion-card-title>Tasks Overview</ion-card-title>
                </ion-card-header>
                <ion-card-content>
                  <ion-list>
                    <ion-item>
                      <ion-label>Tasks Due Today</ion-label>
                      <ion-badge slot="end" color="danger">3</ion-badge>
                    </ion-item>
                    <ion-item>
                      <ion-label>Tasks In Progress</ion-label>
                      <ion-badge slot="end" color="warning">5</ion-badge>
                    </ion-item>
                    <ion-item>
                      <ion-label>Completed Tasks</ion-label>
                      <ion-badge slot="end" color="success">12</ion-badge>
                    </ion-item>
                  </ion-list>
                </ion-card-content>
              </ion-card>
            </ion-col>
            
            <ion-col size="12" size-md="6">
              <ion-card>
                <ion-card-header>
                  <ion-card-title>Active Projects</ion-card-title>
                </ion-card-header>
                <ion-card-content>
                  <ion-list>
                    <ion-item>
                      <ion-label>Project Alpha</ion-label>
                      <ion-progress-bar :value="0.7" color="primary"></ion-progress-bar>
                    </ion-item>
                    <ion-item>
                      <ion-label>Project Beta</ion-label>
                      <ion-progress-bar :value="0.4" color="secondary"></ion-progress-bar>
                    </ion-item>
                    <ion-item>
                      <ion-label>Project Gamma</ion-label>
                      <ion-progress-bar :value="0.2" color="tertiary"></ion-progress-bar>
                    </ion-item>
                  </ion-list>
                </ion-card-content>
              </ion-card>
            </ion-col>
          </ion-row>

          <ion-row>
            <ion-col size="12">
              <ion-card>
                <ion-card-header>
                  <ion-card-title>Recent Activity</ion-card-title>
                </ion-card-header>
                <ion-card-content>
                  <ion-list>
                    <ion-item>
                      <ion-icon :icon="checkmarkCircle" slot="start" color="success"></ion-icon>
                      <ion-label>
                        <h3>Task Completed</h3>
                        <p>Project documentation updated</p>
                        <p class="ion-text-end">2 hours ago</p>
                      </ion-label>
                    </ion-item>
                    <ion-item>
                      <ion-icon :icon="addCircle" slot="start" color="primary"></ion-icon>
                      <ion-label>
                        <h3>New Task Added</h3>
                        <p>Client meeting scheduled</p>
                        <p class="ion-text-end">4 hours ago</p>
                      </ion-label>
                    </ion-item>
                  </ion-list>
                </ion-card-content>
              </ion-card>
            </ion-col>
          </ion-row>
        </ion-grid>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonGrid, IonRow, IonCol, 
         IonCard, IonCardHeader, IonCardTitle, IonCardContent, IonList, IonItem, IonLabel, 
         IonBadge, IonProgressBar, IonIcon } from '@ionic/vue';
import { checkmarkCircle, addCircle } from 'ionicons/icons';
</script>