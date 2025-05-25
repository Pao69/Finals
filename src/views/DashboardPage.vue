<template>
  <page-layout title="Dashboard">
    <ion-content :fullscreen="true" class="ion-padding-horizontal">
      <ion-header collapse="condense">
        <ion-toolbar>
          <ion-title size="large">Dashboard</ion-title>
        </ion-toolbar>
      </ion-header>
      
      <ion-grid class="ion-no-padding">
        <ion-row class="dashboard-row">
          <ion-col size="12" size-md="6" class="ion-padding-bottom">
            <ion-card class="dashboard-card">
              <ion-card-header>
                <ion-card-title class="ion-text-center">Tasks Overview</ion-card-title>
              </ion-card-header>
              <ion-card-content>
                <ion-list lines="none">
                  <ion-item button class="dashboard-item" @click="filterTasks('today')">
                    <ion-icon :icon="timeOutline" slot="start" color="warning"></ion-icon>
                    <ion-label class="ion-text-wrap">
                      <h2>Tasks Due Today</h2>
                      <p>Tasks that need your attention today</p>
                    </ion-label>
                    <ion-badge slot="end" color="warning" class="task-badge">{{ taskCounts.today }}</ion-badge>
                  </ion-item>
                  <ion-item button class="dashboard-item" @click="filterTasks('upcoming')">
                    <ion-icon :icon="calendarOutline" slot="start" color="success"></ion-icon>
                    <ion-label class="ion-text-wrap">
                      <h2>Upcoming Tasks</h2>
                      <p>Tasks scheduled for future dates</p>
                    </ion-label>
                    <ion-badge slot="end" color="success" class="task-badge">{{ taskCounts.upcoming }}</ion-badge>
                  </ion-item>
                  <ion-item button class="dashboard-item" @click="filterTasks('complete')">
                    <ion-icon :icon="checkmarkCircle" slot="start" color="medium"></ion-icon>
                    <ion-label class="ion-text-wrap">
                      <h2>Completed Tasks</h2>
                      <p>Tasks you've already finished</p>
                    </ion-label>
                    <ion-badge slot="end" color="medium" class="task-badge">{{ taskCounts.complete }}</ion-badge>
                  </ion-item>
                  <ion-item button class="dashboard-item" @click="filterTasks('all')">
                    <ion-icon :icon="listOutline" slot="start" color="primary"></ion-icon>
                    <ion-label class="ion-text-wrap">
                      <h2>Total Tasks</h2>
                      <p>All your tasks</p>
                    </ion-label>
                    <ion-badge slot="end" color="primary" class="task-badge">{{ taskCounts.all }}</ion-badge>
                  </ion-item>
                </ion-list>
              </ion-card-content>
            </ion-card>
          </ion-col>

          <ion-col size="12" size-md="6" class="ion-padding-bottom">
            <ion-card class="dashboard-card">
              <ion-card-header>
                <ion-card-title class="ion-text-center">Recent Tasks</ion-card-title>
              </ion-card-header>
              <ion-card-content>
                <ion-list lines="none">
                  <ion-item v-for="task in recentTasks" 
                           :key="task.id" 
                           button 
                           class="task-item"
                           @click="viewTask(task)">
                    <ion-icon :icon="task.completed === 1 ? checkmarkCircle : timeOutline" 
                             slot="start" 
                             :color="task.completed === 1 ? 'success' : 'warning'"
                             class="task-icon">
                    </ion-icon>
                    <ion-label class="ion-text-wrap">
                      <h2 class="task-title">{{ task.title }}</h2>
                      <p class="task-description">{{ task.description }}</p>
                      <p class="task-due-date" :class="getDueDateClass(task)">
                        Due {{ formatDate(task.due_date) }}
                      </p>
                    </ion-label>
                  </ion-item>

                  <div v-if="recentTasks.length === 0" class="empty-state">
                    <ion-icon :icon="documentTextOutline" class="empty-icon"></ion-icon>
                    <p>No tasks yet</p>
                  </div>
                </ion-list>
              </ion-card-content>
            </ion-card>
          </ion-col>
        </ion-row>
      </ion-grid>
    </ion-content>
  </page-layout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonGrid, IonRow, IonCol, 
         IonCard, IonCardHeader, IonCardTitle, IonCardContent, IonList, IonItem, IonLabel, 
         IonBadge, IonIcon } from '@ionic/vue';
import { checkmarkCircle, timeOutline, calendarOutline, listOutline, documentTextOutline } from 'ionicons/icons';
import PageLayout from '@/components/PageLayout.vue';

interface Task {
  id: number;
  title: string;
  description: string;
  due_date: string;
  completed: number;
  created_at: string;
  updated_at: string;
}

const router = useRouter();
const tasks = ref<Task[]>([]);

// Fetch tasks from the database
const fetchTasks = async () => {
  try {
    const token = localStorage.getItem('token') || sessionStorage.getItem('token');
    if (!token) {
      router.push('/login');
      return;
    }

    const response = await axios.get('http://localhost/codes/PROJ/dbConnect/tasks.php', {
      headers: { 'Authorization': `Bearer ${token}` }
    });

    if (response.data.success) {
      tasks.value = response.data.tasks || [];
    }
  } catch (error: any) {
    console.error('Error fetching tasks:', error);
  }
};

// Task counts for badges
const taskCounts = computed(() => {
  const now = new Date();
  now.setHours(0, 0, 0, 0);
  
  return {
    all: tasks.value.length,
    today: tasks.value.filter(task => {
      const taskDate = new Date(task.due_date);
      taskDate.setHours(0, 0, 0, 0);
      return taskDate.getTime() === now.getTime() && task.completed === 0;
    }).length,
    upcoming: tasks.value.filter(task => {
      const taskDate = new Date(task.due_date);
      taskDate.setHours(0, 0, 0, 0);
      return taskDate.getTime() > now.getTime() && task.completed === 0;
    }).length,
    complete: tasks.value.filter(task => task.completed === 1).length
  };
});

// Get recent tasks
const recentTasks = computed(() => {
  return [...tasks.value]
    .sort((a, b) => new Date(b.updated_at).getTime() - new Date(a.updated_at).getTime())
    .slice(0, 5);
});

// Format date for display
const formatDate = (dateString: string): string => {
  const date = new Date(dateString);
  const today = new Date();
  const tomorrow = new Date(today);
  tomorrow.setDate(tomorrow.getDate() + 1);

  if (date.toDateString() === today.toDateString()) {
    return `Today at ${date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
  } else if (date.toDateString() === tomorrow.toDateString()) {
    return `Tomorrow at ${date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
  } else {
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  }
};

// Get due date class for styling
const getDueDateClass = (task: Task) => {
  if (task.completed === 1) return 'completed-task';
  
  const now = new Date();
  now.setHours(0, 0, 0, 0);
  const taskDate = new Date(task.due_date);
  taskDate.setHours(0, 0, 0, 0);
  
  if (taskDate.getTime() === now.getTime()) return 'due-today';
  if (taskDate.getTime() < now.getTime()) return 'overdue';
  if (taskDate.getTime() > now.getTime()) return 'upcoming';
  
  return '';
};

// Navigation functions
const viewTask = (task: Task) => {
  router.push(`/tabs/tasks/view/${task.id}`);
};

const filterTasks = (filter: string) => {
  router.push({
    path: '/tabs/tasks',
    query: { filter }
  });
};

// Add auto-refresh functionality
let refreshInterval: number | undefined;

onMounted(() => {
  fetchTasks();
  // Refresh tasks every minute
  refreshInterval = window.setInterval(fetchTasks, 60000);
});

onBeforeUnmount(() => {
  if (refreshInterval) {
    clearInterval(refreshInterval);
  }
});
</script>

<style scoped>
ion-content {
  --padding-top: 16px;
  --padding-bottom: 16px;
}

.dashboard-card {
  margin: 0;
  border-radius: 16px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  height: 100%;
  border: 1px solid var(--ion-color-light-shade);
  overflow: hidden;
  background: var(--ion-background-color);
}

ion-card-header {
  padding: 16px;
  border-bottom: 1px solid var(--ion-color-light-shade);
  background: var(--ion-color-light-tint);
}

ion-card-title {
  font-size: 1.2rem;
  font-weight: 600;
  color: var(--ion-color-dark);
}

ion-card-content {
  padding: 0;
}

.dashboard-item {
  --padding-start: 16px;
  --padding-end: 16px;
  --min-height: 72px;
  --background-hover: var(--ion-color-light);
  border-bottom: 1px solid var(--ion-color-light-shade);
  transition: background-color 0.2s ease;
}

.dashboard-item:last-child {
  border-bottom: none;
}

.dashboard-item ion-icon {
  font-size: 24px;
  margin-right: 16px;
}

.dashboard-item h2 {
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--ion-color-dark);
  margin: 0 0 4px 0;
}

.dashboard-item p {
  font-size: 0.9rem;
  color: var(--ion-color-medium);
  margin: 0;
}

.task-badge {
  font-size: 1rem;
  padding: 6px 12px;
  border-radius: 12px;
  min-width: 32px;
  text-align: center;
}

.task-item {
  --padding-start: 16px;
  --padding-end: 16px;
  --min-height: 72px;
  --background-hover: var(--ion-color-light);
  border-bottom: 1px solid var(--ion-color-light-shade);
}

.task-item:last-child {
  border-bottom: none;
}

.task-icon {
  font-size: 24px;
  margin-right: 12px;
}

.task-title {
  font-size: 1rem;
  font-weight: 600;
  color: var(--ion-color-dark);
  margin-bottom: 4px;
}

.task-description {
  font-size: 0.9rem;
  color: var(--ion-color-medium);
  margin-bottom: 4px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.task-due-date {
  font-size: 0.85rem;
  font-weight: 500;
  margin: 0;
}

.completed-task {
  color: var(--ion-color-medium);
}

.due-today {
  color: var(--ion-color-warning);
}

.overdue {
  color: var(--ion-color-danger);
}

.upcoming {
  color: var(--ion-color-success);
}

.empty-state {
  padding: 32px 16px;
  text-align: center;
  color: var(--ion-color-medium);
}

.empty-icon {
  font-size: 48px;
  margin-bottom: 12px;
}

.empty-state p {
  font-size: 1rem;
  margin: 0;
}

@media (max-width: 768px) {
  ion-content {
    --padding-top: 8px;
    --padding-bottom: 8px;
    --padding-start: 8px;
    --padding-end: 8px;
  }

  .dashboard-card {
    margin-bottom: 16px;
    border-radius: 12px;
  }

  .dashboard-item {
    --min-height: 64px;
  }

  .task-badge {
    font-size: 0.9rem;
    padding: 4px 8px;
  }

  .dashboard-item h2 {
    font-size: 1rem;
  }

  .dashboard-item p {
    font-size: 0.85rem;
  }
}

@media (min-width: 768px) {
  ion-content {
    --padding-start: 24px;
    --padding-end: 24px;
  }

  ion-grid {
    max-width: 1200px;
    margin: 0 auto;
  }
  
  .dashboard-row {
    display: flex;
    margin: 0 -12px;
  }
  
  ion-col {
    padding: 12px;
    display: flex;
    flex-direction: column;
  }

  .dashboard-card {
    flex: 1;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    display: flex;
    flex-direction: column;
  }

  ion-card-content {
    flex: 1;
    display: flex;
    flex-direction: column;
  }

  ion-list {
    flex: 1;
  }

  .dashboard-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
  }

  .dashboard-item:hover {
    --background: var(--ion-color-light);
  }
}

/* Dark mode adjustments */
@media (prefers-color-scheme: dark) {
  .dashboard-card {
    border-color: var(--ion-color-dark);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
  }

  ion-card-header {
    background: rgba(var(--ion-color-light-rgb), 0.05);
    border-color: var(--ion-color-dark);
  }

  .dashboard-item {
    border-color: var(--ion-color-dark);
  }

  .dashboard-item h2 {
    color: var(--ion-color-light);
  }

  .task-title {
    color: var(--ion-color-light);
  }

  .empty-state {
    color: var(--ion-color-medium-shade);
  }
}
</style>