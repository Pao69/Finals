<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-title>Dashboard</ion-title>
      </ion-toolbar>
    </ion-header>
    <ion-content :fullscreen="true" class="ion-padding-horizontal">
      <ion-header collapse="condense">
        <ion-toolbar>
          <ion-title size="large">Dashboard</ion-title>
        </ion-toolbar>
      </ion-header>
      
      <ion-grid class="ion-no-padding">
        <ion-row>
          <ion-col size="12" size-md="6" class="ion-padding-bottom">
            <ion-card class="dashboard-card">
              <ion-card-header>
                <ion-card-title class="ion-text-center">Tasks Overview</ion-card-title>
              </ion-card-header>
              <ion-card-content>
                <ion-list lines="none">
                  <ion-item button class="dashboard-item" @click="filterTasks('today')">
                    <ion-label class="ion-text-wrap">
                      <h2>Tasks Due Today</h2>
                    </ion-label>
                    <ion-badge slot="end" color="warning" class="task-badge">{{ taskCounts.today }}</ion-badge>
                  </ion-item>
                  <ion-item button class="dashboard-item" @click="filterTasks('upcoming')">
                    <ion-label class="ion-text-wrap">
                      <h2>Upcoming Tasks</h2>
                    </ion-label>
                    <ion-badge slot="end" color="success" class="task-badge">{{ taskCounts.upcoming }}</ion-badge>
                  </ion-item>
                  <ion-item button class="dashboard-item" @click="filterTasks('complete')">
                    <ion-label class="ion-text-wrap">
                      <h2>Completed Tasks</h2>
                    </ion-label>
                    <ion-badge slot="end" color="medium" class="task-badge">{{ taskCounts.complete }}</ion-badge>
                  </ion-item>
                  <ion-item button class="dashboard-item" @click="filterTasks('all')">
                    <ion-label class="ion-text-wrap">
                      <h2>Total Tasks</h2>
                    </ion-label>
                    <ion-badge slot="end" color="primary" class="task-badge">{{ taskCounts.all }}</ion-badge>
                  </ion-item>
                </ion-list>
              </ion-card-content>
            </ion-card>
          </ion-col>
        </ion-row>

        <ion-row>
          <ion-col size="12">
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
                </ion-list>
              </ion-card-content>
            </ion-card>
          </ion-col>
        </ion-row>
      </ion-grid>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonGrid, IonRow, IonCol, 
         IonCard, IonCardHeader, IonCardTitle, IonCardContent, IonList, IonItem, IonLabel, 
         IonBadge, IonIcon } from '@ionic/vue';
import { checkmarkCircle, timeOutline } from 'ionicons/icons';

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
    const token = localStorage.getItem('token');
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

// Fetch tasks on component mount
onMounted(() => {
  fetchTasks();
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
}

ion-card-header {
  padding: 16px;
  border-bottom: 1px solid var(--ion-color-light);
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
  --min-height: 56px;
  --background-hover: var(--ion-color-light);
}

.task-item {
  --padding-start: 16px;
  --padding-end: 16px;
  --min-height: 72px;
  --background-hover: var(--ion-color-light);
}

.task-badge {
  font-size: 1rem;
  padding: 6px 12px;
  border-radius: 12px;
  min-width: 32px;
  text-align: center;
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
  font-size: 0.8rem;
  font-weight: 500;
  text-align: right;
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

@media (max-width: 768px) {
  ion-content {
    --padding-top: 8px;
    --padding-bottom: 8px;
  }

  .dashboard-card {
    margin-bottom: 16px;
  }

  .dashboard-item, .task-item {
    --min-height: 64px;
  }

  .task-badge {
    font-size: 0.9rem;
    padding: 4px 8px;
  }

  .task-title {
    font-size: 0.95rem;
  }

  .task-description {
    font-size: 0.85rem;
  }

  .task-due-date {
    font-size: 0.75rem;
  }
}

@media (min-width: 768px) {
  ion-grid {
    padding: 0;
  }
  
  ion-row {
    margin: 0 -8px;
  }
  
  ion-col {
    padding: 0 8px;
  }
}
</style>