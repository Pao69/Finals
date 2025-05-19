<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-title>My Tasks</ion-title>
      </ion-toolbar>
      <ion-toolbar>
        <ion-searchbar
          v-model="searchQuery"
          placeholder="Search tasks..."
          @ionInput="handleSearch"
        ></ion-searchbar>
      </ion-toolbar>
      <ion-toolbar>
        <ion-segment v-model="selectedFilter" @ionChange="handleFilterChange" scrollable>
          <ion-segment-button value="all">
            <ion-label class="segment-label">
              <div class="label-text">All</div>
              <ion-badge color="primary">{{ taskCounts.all }}</ion-badge>
            </ion-label>
          </ion-segment-button>
          <ion-segment-button value="today">
            <ion-label class="segment-label">
              <div class="label-text">Today</div>
              <ion-badge color="warning">{{ taskCounts.today }}</ion-badge>
            </ion-label>
          </ion-segment-button>
          <ion-segment-button value="upcoming">
            <ion-label class="segment-label">
              <div class="label-text">Upcoming</div>
              <ion-badge color="success">{{ taskCounts.upcoming }}</ion-badge>
            </ion-label>
          </ion-segment-button>
          <ion-segment-button value="complete">
            <ion-label class="segment-label">
              <div class="label-text">Complete</div>
              <ion-badge color="medium">{{ taskCounts.complete }}</ion-badge>
            </ion-label>
          </ion-segment-button>
        </ion-segment>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="ion-padding-bottom">
      
      <!-- Sort Option -->
      <ion-item lines="none" class="sort-option" v-if="filteredTasks.length > 0">
        <ion-button fill="clear" size="small" @click="toggleSortOrder">
          <ion-icon :icon="swapVerticalOutline" slot="start"></ion-icon>
          Sort by Due Date
        </ion-button>
      </ion-item>

      <!-- Tasks List -->
      <ion-list v-if="filteredTasks.length > 0">
        <ion-item-group>
          <template v-if="selectedFilter === 'upcoming'">
            <template v-for="group in groupedTasks" :key="group.date">
              <ion-item-divider sticky>
                <ion-label>{{ group.label }}</ion-label>
              </ion-item-divider>
              <ion-item v-for="task in group.tasks" :key="task.id" class="task-item" button @click="viewTask(task)">
                <ion-checkbox 
                  slot="start" 
                  :checked="task.completed === 1"
                  @click.stop="toggleTaskCompletion(task)"
                ></ion-checkbox>
                <ion-label>
                  <h2 :class="{ completed: task.completed === 1 }">{{ task.title }}</h2>
                  <p class="task-description">{{ task.description }}</p>
                  <p class="task-due" :class="getDueDateClass(task)">Due {{ formatDate(task.due_date) }}</p>
                </ion-label>
                <ion-buttons slot="end">
                  <ion-button @click.stop="editTask(task)">
                    <ion-icon :icon="createOutline" color="primary"></ion-icon>
                  </ion-button>
                  <ion-button @click.stop="confirmDelete(task)">
                    <ion-icon :icon="trashOutline" color="danger"></ion-icon>
                  </ion-button>
                </ion-buttons>
              </ion-item>
            </template>
          </template>
          
          <template v-else>
            <ion-item v-for="task in sortedTasks" :key="task.id" class="task-item" button @click="editTask(task)">
              <ion-checkbox 
                slot="start" 
                :checked="task.completed === 1"
                @click.stop="toggleTaskCompletion(task)"
              ></ion-checkbox>
              <ion-label>
                <h2 :class="{ completed: task.completed === 1 }">{{ task.title }}</h2>
                <p class="task-description">{{ task.description }}</p>
                <p class="task-due" :class="getDueDateClass(task)">Due {{ formatDate(task.due_date) }}</p>
              </ion-label>
              <ion-buttons slot="end">
                <ion-button @click.stop="confirmDelete(task)" expand="block" size="large" color="danger">
                  <ion-icon :icon="trashOutline"></ion-icon>
                </ion-button>
              </ion-buttons>
            </ion-item>
          </template>
        </ion-item-group>
      </ion-list>

      <!-- Empty State -->
      <div v-else class="empty-state ion-text-center">
        <ion-icon :icon="clipboardOutline" class="empty-icon"></ion-icon>
        <h2>No Tasks Found</h2>
        <p>Start by creating a new task</p>
      </div>

      <!-- Floating Action Button -->
      <ion-fab vertical="bottom" horizontal="end" slot="fixed">
        <ion-fab-button @click="goToAddTask" class="custom-fab">
          <ion-icon :icon="addOutline"></ion-icon>
        </ion-fab-button>
      </ion-fab>
    </ion-content>

    <!-- Delete Confirmation Alert -->
    <ion-alert
      :is-open="showDeleteAlert"
      header="Confirm Delete"
      message="Are you sure you want to delete this task?"
      :buttons="[
        {
          text: 'Cancel',
          role: 'cancel',
          handler: () => {
            showDeleteAlert = false;
          },
        },
        {
          text: 'Delete',
          role: 'confirm',
          handler: () => {
            deleteTask();
          },
        },
      ]"
    ></ion-alert>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import {
  IonPage, IonHeader, IonToolbar, IonTitle, IonContent,
  IonList, IonItem, IonItemGroup, IonItemDivider,
  IonLabel, IonCheckbox, IonFab, IonFabButton,
  IonIcon, IonButton, IonButtons, IonAlert,
  IonSearchbar, IonSegment, IonSegmentButton,
  IonChip, toastController, IonBadge,
  IonItemSliding,
  IonItemOptions,
  IonItemOption,
  IonModal,
} from '@ionic/vue';
import { 
  addOutline, createOutline, trashOutline,
  swapVerticalOutline, clipboardOutline,
  calendarOutline,
  checkmarkCircleOutline,
  documentTextOutline
} from 'ionicons/icons';

interface Task {
  id: number;
  user_id: number;
  title: string;
  description: string;
  due_date: string;
  completed: number;
  created_at: string;
  updated_at: string;
}

const router = useRouter();
const tasks = ref<Task[]>([]);
const showDeleteAlert = ref(false);
const taskToDelete = ref<Task | null>(null);
const searchQuery = ref('');
const selectedFilter = ref('all');
const sortAscending = ref(true);
const selectedTask = ref<Task | null>(null);

// Fetch tasks from the database
const fetchTasks = async () => {
  try {
    const token = localStorage.getItem('token');
    if (!token) {
      console.error('No token found');
      router.push('/login');
      return;
    }

    console.log('Fetching tasks with token:', token);
    const response = await axios.get('http://localhost/codes/PROJ/dbConnect/tasks.php', {
      headers: { 'Authorization': `Bearer ${token}` }
    });

    console.log('API Response:', response.data);

    if (response.data.success) {
      tasks.value = response.data.tasks || [];
      console.log('Tasks loaded:', tasks.value);
    } else {
      throw new Error(response.data.message || 'Failed to fetch tasks');
    }
  } catch (error: any) {
    console.error('Error fetching tasks:', error);
    console.error('Error details:', error.response || error);
    
    const errorMessage = error.response?.data?.message || error.message || 'Failed to fetch tasks';
    
    if (error.response?.status === 401) {
      localStorage.removeItem('token');
      router.push('/login');
    }

    const toast = await toastController.create({
      message: errorMessage,
      duration: 2000,
      color: 'danger'
    });
    await toast.present();
  }
};

// Filter tasks based on search and selected filter
const filteredTasks = computed(() => {
  let filtered = tasks.value;

  // Apply search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(task => 
      task.title.toLowerCase().includes(query) ||
      task.description.toLowerCase().includes(query)
    );
  }

  // Apply tab filter
  const now = new Date();
  now.setHours(0, 0, 0, 0);

  switch (selectedFilter.value) {
    case 'today':
      filtered = filtered.filter(task => {
        const taskDate = new Date(task.due_date);
        taskDate.setHours(0, 0, 0, 0);
        return taskDate.getTime() === now.getTime();
      });
      break;
    case 'upcoming':
      filtered = filtered.filter(task => {
        const taskDate = new Date(task.due_date);
        taskDate.setHours(0, 0, 0, 0);
        return taskDate.getTime() > now.getTime();
      });
      break;
    case 'complete':
      filtered = filtered.filter(task => task.completed === 1);
      break;
  }

  return filtered;
});

// Sort tasks by due date
const sortedTasks = computed(() => {
  return [...filteredTasks.value].sort((a, b) => {
    const dateA = new Date(a.due_date).getTime();
    const dateB = new Date(b.due_date).getTime();
    return sortAscending.value ? dateA - dateB : dateB - dateA;
  });
});

const toggleSortOrder = () => {
  sortAscending.value = !sortAscending.value;
};

const handleSearch = (event: CustomEvent) => {
  searchQuery.value = event.detail.value || '';
};

const handleFilterChange = (event: CustomEvent) => {
  selectedFilter.value = event.detail.value;
};

// Toggle task completion
const toggleTaskCompletion = async (task: Task) => {
  try {
    const token = localStorage.getItem('token');
    await axios.post('http://localhost/codes/PROJ/dbConnect/update_task.php', {
      id: task.id,
      completed: task.completed === 1 ? 0 : 1
    }, {
      headers: { 'Authorization': `Bearer ${token}` }
    });

    await fetchTasks();
  } catch (error) {
    const toast = await toastController.create({
      message: 'Failed to update task',
      duration: 2000,
      color: 'danger'
    });
    await toast.present();
  }
};

// Navigation functions
const goToAddTask = () => {
  router.push('/tabs/tasks/add');
};

const editTask = (task: Task) => {
  router.push(`/tabs/tasks/edit/${task.id}`);
};

// Delete task functions
const confirmDelete = (task: Task) => {
  taskToDelete.value = task;
  showDeleteAlert.value = true;
};

const deleteTask = async () => {
  if (!taskToDelete.value) return;

  try {
    const token = localStorage.getItem('token');
    const response = await axios.delete('http://localhost/codes/PROJ/dbConnect/tasks.php', {
      headers: { 'Authorization': `Bearer ${token}` },
      data: { taskId: taskToDelete.value.id }
    });

    if (response.data.success) {
      await fetchTasks();
      const toast = await toastController.create({
        message: 'Task deleted successfully',
        duration: 2000,
        color: 'success'
      });
      await toast.present();
    } else {
      throw new Error(response.data.message || 'Failed to delete task');
    }
  } catch (error: any) {
    const toast = await toastController.create({
      message: error.message || 'Failed to delete task',
      duration: 2000,
      color: 'danger'
    });
    await toast.present();
  }

  taskToDelete.value = null;
  showDeleteAlert.value = false;
};

// Task counts for badges
const taskCounts = computed(() => {
  const now = new Date();
  now.setHours(0, 0, 0, 0);
  
  const tomorrow = new Date(now);
  tomorrow.setDate(tomorrow.getDate() + 1);

  return {
    all: tasks.value.length,
    today: tasks.value.filter(task => {
      const taskDate = new Date(task.due_date);
      taskDate.setHours(0, 0, 0, 0);
      return taskDate.getTime() === now.getTime();
    }).length,
    upcoming: tasks.value.filter(task => {
      const taskDate = new Date(task.due_date);
      taskDate.setHours(0, 0, 0, 0);
      return taskDate.getTime() > now.getTime();
    }).length,
    complete: tasks.value.filter(task => task.completed === 1).length
  };
});

// Group tasks by date for upcoming view
const groupedTasks = computed(() => {
  if (selectedFilter.value !== 'upcoming') return [];

  const groups: { date: string; label: string; tasks: Task[] }[] = [];
  const now = new Date();
  now.setHours(0, 0, 0, 0);

  filteredTasks.value.forEach(task => {
    const taskDate = new Date(task.due_date);
    taskDate.setHours(0, 0, 0, 0);
    const diffDays = Math.floor((taskDate.getTime() - now.getTime()) / (1000 * 60 * 60 * 24));
    
    let label = '';
    if (diffDays === 0) label = 'Today';
    else if (diffDays === 1) label = 'Tomorrow';
    else if (diffDays < 7) label = taskDate.toLocaleDateString('en-US', { weekday: 'long' });
    else if (diffDays < 30) label = 'This Month';
    else label = taskDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

    let group = groups.find(g => g.label === label);
    if (!group) {
      group = { date: taskDate.toISOString(), label, tasks: [] };
      groups.push(group);
    }
    group.tasks.push(task);
  });

  return groups.sort((a, b) => new Date(a.date).getTime() - new Date(b.date).getTime());
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

// Format time only
const formatTime = (dateString: string): string => {
  const date = new Date(dateString);
  return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

// Add viewTask function
const viewTask = (task: Task) => {
  selectedTask.value = task;
};

// Add getDueDateClass function
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

// Fetch tasks on component mount
onMounted(() => {
  fetchTasks();
});
</script>

<style scoped>
.completed {
  text-decoration: line-through;
  color: var(--ion-color-medium);
}

.task-due {
  font-size: 0.8rem;
  color: var(--ion-color-medium);
  font-weight: 500;
}

.task-due.completed-task {
  color: var(--ion-color-medium);
}

.task-due.due-today {
  color: var(--ion-color-warning);
}

.task-due.overdue {
  color: var(--ion-color-danger);
}

.task-due.upcoming {
  color: var(--ion-color-success);
}

.sort-option {
  --padding-start: 8px;
  --padding-end: 8px;
  --min-height: 40px;
}

.empty-state {
  padding: 2rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 60vh;
}

.empty-icon {
  font-size: 4rem;
  color: var(--ion-color-medium);
  margin-bottom: 1rem;
}

.empty-state h2 {
  margin: 0;
  color: var(--ion-color-dark);
  font-size: 1.5rem;
}

.empty-state p {
  margin: 0.5rem 0 2rem;
  color: var(--ion-color-medium);
}

.create-task-btn {
  max-width: 300px;
  margin: 0 auto;
}

.task-item {
  --padding-start: 16px;
  --padding-end: 16px;
  margin-bottom: 0.5rem;
}

ion-chip {
  text-transform: capitalize;
}

ion-fab {
  bottom: 72px !important; /* Height of tab bar (56px) + 16px margin */
  right: 16px !important;
  position: fixed !important;
}

ion-fab-button {
  --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  margin: 0;
}

.ion-padding-bottom {
  padding-bottom: 96px; /* Height of FAB (56px) + bottom margin to tab bar (16px) + extra padding (24px) */
}

ion-segment {
  --background: var(--ion-color-light);
}

ion-segment-button {
  --padding-start: 8px;
  --padding-end: 8px;
  --padding-top: 4px;
  --padding-bottom: 4px;
  min-width: auto;
}

ion-badge {
  --padding-start: 6px;
  --padding-end: 6px;
  --padding-top: 2px;
  --padding-bottom: 2px;
  font-size: 0.7rem;
}

.segment-label {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
}

.label-text {
  margin-bottom: 2px;
}

ion-item-divider {
  --background: var(--ion-color-light);
  --padding-start: 16px;
  font-weight: 600;
}

.task-description {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 250px;
  color: var(--ion-color-medium);
}

.task-actions {
  display: none;
}

.custom-fab {
  margin-bottom: 1rem;
  margin-right: 1rem;
}

ion-modal {
  --height: auto;
  --max-height: 90%;
}

ion-item-sliding {
  margin-bottom: 0.5rem;
  border-radius: 8px;
  overflow: hidden;
  --ion-item-sliding-transition-duration: 300ms;
  --ion-item-sliding-transition-timing: cubic-bezier(0.4, 0, 0.2, 1);
}

ion-item {
  --padding-start: 16px;
  --padding-end: 16px;
  margin-bottom: 0.5rem;
  --background: var(--ion-color-light);
  --border-radius: 8px;
  --transition: transform 300ms cubic-bezier(0.4, 0, 0.2, 1);
  --min-height: 72px; /* Set a consistent height for items */
}

ion-item-options {
  border-radius: 8px;
  overflow: hidden;
  background: transparent;
  width: 140px; /* Set a fixed width for the options container */
}

ion-item-option {
  --padding-start: 1rem;
  --padding-end: 1rem;
  --background: var(--ion-color-primary);
  --background-hover: var(--ion-color-primary-shade);
  --background-activated: var(--ion-color-primary-shade);
  --color: white;
  --color-hover: white;
  --color-activated: white;
  --ripple-color: rgba(255, 255, 255, 0.2);
  transition: transform 300ms cubic-bezier(0.4, 0, 0.2, 1);
  width: 70px; /* Set a fixed width for each option */
}

ion-item-option:last-child {
  --background: var(--ion-color-danger);
  --background-hover: var(--ion-color-danger-shade);
  --background-activated: var(--ion-color-danger-shade);
}

ion-item-option ion-icon {
  font-size: 1.2rem; /* Slightly smaller icon size */
  transition: transform 300ms cubic-bezier(0.4, 0, 0.2, 1);
  margin: 0; /* Remove any margin */
}

ion-item-option:hover ion-icon {
  transform: scale(1.1);
}

/* Improve the button appearance */
ion-item-option::part(native) {
  padding: 0;
  font-weight: 500;
  letter-spacing: 0.5px;
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
}

/* Add a subtle gradient to the buttons */
ion-item-option:first-child::part(native) {
  background: linear-gradient(45deg, var(--ion-color-primary), var(--ion-color-primary-shade));
}

ion-item-option:last-child::part(native) {
  background: linear-gradient(45deg, var(--ion-color-danger), var(--ion-color-danger-shade));
}

/* Ensure the sliding item has proper spacing */
ion-item-sliding {
  margin-bottom: 0.5rem;
  border-radius: 8px;
  overflow: hidden;
  --ion-item-sliding-transition-duration: 300ms;
  --ion-item-sliding-transition-timing: cubic-bezier(0.4, 0, 0.2, 1);
}

ion-item {
  --padding-start: 16px;
  --padding-end: 16px;
  margin-bottom: 0.5rem;
  --background: var(--ion-color-light);
  --border-radius: 8px;
  --transition: transform 300ms cubic-bezier(0.4, 0, 0.2, 1);
  --min-height: 72px; /* Set a consistent height for items */
}

ion-buttons {
  display: flex;
  gap: 4px;
}

ion-button {
  --padding-start: 8px;
  --padding-end: 8px;
}

ion-button ion-icon {
  font-size: 1.2rem;
}

/* Add hover effect for task items */
.task-item {
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.task-item:hover {
  --background: var(--ion-color-light-shade);
}

/* Style the task details alert */
:deep(.alert-wrapper) {
  max-width: 90%;
}

:deep(.alert-message) {
  max-height: 60vh;
  overflow-y: auto;
}

:deep(.alert-button) {
  font-weight: 500;
}

:deep(.alert-button[role="destructive"]) {
  color: var(--ion-color-danger);
}
</style>
