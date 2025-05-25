<template>
  <page-layout title="Dashboard" :show-back-button="false">
    <ion-content :fullscreen="true" class="ion-padding-horizontal">
      <ion-header collapse="condense">
        <ion-toolbar>
          <ion-title size="large">Dashboard</ion-title>
        </ion-toolbar>
      </ion-header>
      
      <ion-grid class="ion-no-padding">
        <ion-row class="dashboard-row">
          <ion-col size="12" size-md="6" class="ion-padding-bottom">
            <ion-card class="dashboard-card animate-card">
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
            <ion-card class="dashboard-card animate-card">
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
                    <div class="task-status-indicator" :class="{ 'completed': task.completed === 1 }"></div>
                    <ion-label class="ion-text-wrap">
                      <h2 class="task-title">{{ task.title }}</h2>
                      <p class="task-description">{{ task.description }}</p>
                      <div class="task-meta">
                        <span class="task-due-date" :class="getDueDateClass(task)">
                          <ion-icon :icon="calendarOutline" class="meta-icon"></ion-icon>
                          {{ formatDate(task.due_date) }}
                        </span>
                        <ion-chip :color="task.completed === 1 ? 'success' : 'warning'" class="status-chip">
                          {{ task.completed === 1 ? 'Completed' : 'Pending' }}
                        </ion-chip>
                      </div>
                    </ion-label>
                  </ion-item>

                  <div v-if="recentTasks.length === 0" class="empty-state animate-fade">
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
         IonBadge, IonIcon, IonChip } from '@ionic/vue';
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
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  background: var(--ion-card-background);
  overflow: hidden;
}

ion-card-header {
  padding: 20px;
  border-bottom: 1px solid var(--ion-color-light-shade);
  background: var(--ion-background-color);
}

ion-card-title {
  font-size: 1.4rem;
  font-weight: 700;
  color: var(--ion-text-color);
  letter-spacing: -0.02em;
}

ion-card-content {
  padding: 0;
}

.dashboard-item {
  --padding-start: 16px;
  --padding-end: 16px;
  --min-height: 72px;
  margin: 8px;
  border-radius: 8px;
  background: var(--ion-background-color);
}

.dashboard-item ion-icon {
  font-size: 24px;
  margin-right: 16px;
}

.dashboard-item h2 {
  font-size: 1.15rem;
  font-weight: 600;
  color: var(--ion-text-color);
  margin: 0 0 6px 0;
  line-height: 1.3;
}

.dashboard-item p {
  font-size: 0.95rem;
  color: var(--ion-text-color);
  opacity: 0.8;
  margin: 0;
  line-height: 1.4;
}

.task-badge {
  font-size: 0.95rem;
  padding: 6px 12px;
  border-radius: 12px;
  min-width: 32px;
  text-align: center;
  font-weight: 600;
}

.task-item {
  --padding-start: 16px;
  --padding-end: 16px;
  --min-height: 84px;
  margin: 8px;
  border-radius: 8px;
  position: relative;
  background: var(--ion-background-color);
}

.task-status-indicator {
  width: 4px;
  height: 48px;
  background-color: var(--ion-color-warning);
  border-radius: 2px;
  margin-right: 16px;
}

.task-status-indicator.completed {
  background-color: var(--ion-color-success);
}

.task-title {
  font-size: 1.15rem;
  font-weight: 600;
  color: var(--ion-text-color);
  margin-bottom: 6px;
  line-height: 1.3;
}

.task-description {
  font-size: 0.95rem;
  color: var(--ion-text-color);
  opacity: 0.8;
  margin-bottom: 10px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-height: 1.4;
}

.task-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.task-due-date {
  font-size: 0.9rem;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 6px;
  color: var(--ion-text-color);
  opacity: 0.8;
}

.meta-icon {
  font-size: 16px;
}

.status-chip {
  height: 26px;
  font-size: 0.85rem;
  font-weight: 600;
  margin: 0;
  --background: transparent;
  --color: inherit;
}

.empty-state {
  padding: 48px 16px;
  text-align: center;
  color: var(--ion-text-color);
  opacity: 0.8;
}

.empty-icon {
  font-size: 48px;
  margin-bottom: 16px;
  opacity: 0.7;
}

.empty-state p {
  font-size: 1.1rem;
  margin: 0;
  font-weight: 500;
  line-height: 1.4;
}

@media (max-width: 768px) {
  ion-content {
    --padding-top: 12px;
    --padding-bottom: 12px;
    --padding-start: 12px;
    --padding-end: 12px;
  }

  .dashboard-card {
    margin-bottom: 16px;
  }

  .dashboard-item {
    --min-height: 68px;
  }

  .task-badge {
    font-size: 0.9rem;
    padding: 4px 10px;
  }

  .dashboard-item h2,
  .task-title {
    font-size: 1.1rem;
  }

  .dashboard-item p,
  .task-description {
    font-size: 0.9rem;
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

  .dashboard-item:active,
  .task-item:active {
    --background: var(--ion-color-light);
  }
}

/* Dark mode adjustments */
@media (prefers-color-scheme: dark) {
  .dashboard-card {
    background: var(--ion-card-background);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
  }

  ion-card-header {
    background: var(--ion-card-background);
    border-color: rgba(255, 255, 255, 0.1);
  }

  ion-card-title,
  .dashboard-item h2,
  .task-title,
  .task-description,
  .dashboard-item p,
  .task-due-date,
  .empty-state {
    color: #ffffff;
  }

  .dashboard-item,
  .task-item {
    background: var(--ion-card-background);
  }

  .task-description,
  .dashboard-item p,
  .task-due-date,
  .empty-state {
    opacity: 0.9;
  }

  .task-status-indicator {
    opacity: 1;
  }

  .dashboard-item:active,
  .task-item:active {
    --background: rgba(255, 255, 255, 0.05);
  }
}
</style>