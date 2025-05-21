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
          @ionInput="handleSearch">
        </ion-searchbar>
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
        <ion-button fill="clear" size="small" @click="presentSortPopover">
          <ion-icon :icon="swapVerticalOutline" slot="start"></ion-icon>
          {{ sortOptions.find(opt => opt.value === currentSort)?.label || 'Sort by' }}
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
                  :modelValue="task.completed === 1"
                  @update:modelValue="toggleTaskCompletion(task, $event)"
                  @click.stop>
                </ion-checkbox>
                <ion-label>
                  <h2 :class="{ completed: task.completed === 1 }">{{ task.title }}</h2>
                  <p class="task-description">{{ task.description }}</p>
                  <p class="task-due" :class="getDueDateClass(task)">Due {{ formatDate(task.due_date) }}</p>
                </ion-label>
              </ion-item>
            </template>
          </template>
          
          <template v-else>
            <ion-item v-for="task in sortedTasks" :key="task.id" class="task-item" button @click="viewTask(task)">
              <ion-checkbox 
                slot="start" 
                :modelValue="task.completed === 1"
                @update:modelValue="toggleTaskCompletion(task, $event)"
                @click.stop>
              </ion-checkbox>
              <ion-label>
                <h2 :class="{ completed: task.completed === 1 }">{{ task.title }}</h2>
                <p class="task-description">{{ task.description }}</p>
                <p class="task-due" :class="getDueDateClass(task)">Due {{ formatDate(task.due_date) }}</p>
              </ion-label>
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
      ]">
      </ion-alert>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, onBeforeUnmount } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';
import {
  IonPage, IonHeader, IonToolbar, IonTitle, IonContent,
  IonList, IonItem, IonItemGroup, IonItemDivider,
  IonLabel, IonCheckbox, IonFab, IonFabButton,
  IonIcon, IonButton, IonButtons, IonAlert,
  IonSearchbar, IonSegment, IonSegmentButton,
  toastController, IonBadge,
 
  popoverController,
} from '@ionic/vue';
import { 
  addOutline,
  swapVerticalOutline, 
  clipboardOutline,
  checkmarkCircle,
  closeCircle
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

// Declare global function type
declare global {
  interface Window {
    updateTaskData: (task: Task) => void;
    refreshTaskList: (() => void) | undefined;
  }
}

const router = useRouter();
const route = useRoute();
const tasks = ref<Task[]>([]);
const showDeleteAlert = ref(false);
const taskToDelete = ref<Task | null>(null);
const searchQuery = ref('');
const selectedFilter = ref('all');
const sortAscending = ref(true);
const selectedTask = ref<Task | null>(null);

// Add sort options
const sortOptions = [
  { label: 'Due Date (Earliest)', value: 'due_date_asc' },
  { label: 'Due Date (Latest)', value: 'due_date_desc' },
  { label: 'Title (A-Z)', value: 'title_asc' },
  { label: 'Title (Z-A)', value: 'title_desc' },
  { label: 'Created Date (Newest)', value: 'created_desc' },
  { label: 'Created Date (Oldest)', value: 'created_asc' }
];

const currentSort = ref('due_date_asc');

// Function to refresh task list
const refreshTaskList = async () => {
  await fetchTasks();
};

// Make the refresh function available globally
window.refreshTaskList = refreshTaskList;

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
    const toast = await toastController.create({
      message: 'Failed to fetch tasks',
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
        return taskDate.getTime() === now.getTime() && task.completed === 0;
      });
      break;
    case 'upcoming':
      filtered = filtered.filter(task => {
        const taskDate = new Date(task.due_date);
        taskDate.setHours(0, 0, 0, 0);
        return taskDate.getTime() > now.getTime() && task.completed === 0;
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
    switch (currentSort.value) {
      case 'due_date_asc':
        return new Date(a.due_date).getTime() - new Date(b.due_date).getTime();
      case 'due_date_desc':
        return new Date(b.due_date).getTime() - new Date(a.due_date).getTime();
      case 'title_asc':
        return a.title.localeCompare(b.title);
      case 'title_desc':
        return b.title.localeCompare(a.title);
      case 'created_desc':
        return new Date(b.created_at).getTime() - new Date(a.created_at).getTime();
      case 'created_asc':
        return new Date(a.created_at).getTime() - new Date(b.created_at).getTime();
      default:
        return 0;
    }
  });
});

const handleSearch = (event: CustomEvent) => {
  searchQuery.value = event.detail.value || '';
};

const handleFilterChange = (event: CustomEvent) => {
  selectedFilter.value = event.detail.value;
};

// Toggle task completion
const toggleTaskCompletion = async (task: Task, completed: boolean) => {
  try {
    const token = localStorage.getItem('token');
    const response = await axios.post(
      'http://localhost/codes/PROJ/dbConnect/tasks.php',
      {
        id: task.id,
        completed: completed ? 1 : 0
      },
      {
        headers: { 'Authorization': `Bearer ${token}` }
      }
    );

    if (response.data.success) {
      // Show success toast
      const toast = await toastController.create({
        message: 'Task updated successfully',
        duration: 2000,
        color: 'success',
        position: 'top',
        icon: checkmarkCircle
      });
      await toast.present();

      // Refresh the page after a short delay
      setTimeout(() => {
        window.location.reload();
      }, 500);
    }
  } catch (error: any) {
    console.error('Error updating task:', error);
    const toast = await toastController.create({
      message: 'Failed to update task',
      duration: 2000,
      color: 'danger',
      position: 'top',
      icon: closeCircle
    });
    await toast.present();
  }
};

// Navigation functions
const goToAddTask = () => {
  router.push('/tabs/tasks/add');
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
  router.push(`/tabs/tasks/view/${task.id}`);
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

// Present sort popover
const presentSortPopover = async (ev: Event) => {
  const popover = await popoverController.create({
    component: 'ion-list',
    event: ev,
    translucent: true,
    cssClass: 'sort-popover'
  });

  // Create list items for each sort option
  const list = document.createElement('ion-list');
  sortOptions.forEach(option => {
    const item = document.createElement('ion-item');
    item.button = true;
    item.innerHTML = `
      <ion-label>${option.label}</ion-label>
      <ion-icon name="checkmark" color="primary" slot="end" style="display: ${currentSort.value === option.value ? 'block' : 'none'}"></ion-icon>
    `;
    item.addEventListener('click', () => {
      currentSort.value = option.value;
      popover.dismiss();
    });
    list.appendChild(item);
  });

  popover.appendChild(list);
  await popover.present();
};

// Cleanup on unmount
onBeforeUnmount(() => {
  // Remove the global function
  if (window.refreshTaskList) {
    window.refreshTaskList = undefined;
  }
});

// Watch for route changes to update the filter
watch(
  () => route.query.filter,
  (newFilter) => {
    if (newFilter) {
      selectedFilter.value = newFilter as string;
    }
  }
);

// Update onMounted to set initial filter from URL
onMounted(() => {
  fetchTasks();
  // Set initial filter from URL if present
  if (route.query.filter) {
    selectedFilter.value = route.query.filter as string;
  }
});
</script>

<style scoped>
.completed {
  text-decoration: line-through;
  color: var(--ion-color-medium);
}

.task-item {
  --padding-start: 16px;
  --padding-end: 16px;
  margin: 8px 0;
  --background: var(--ion-color-light);
  transition: all 0.3s ease;
  min-height: 72px;
}

.task-item .completed {
  transition: all 0.3s ease;
}

.task-description {
  font-size: 0.9rem;
  color: var(--ion-color-medium);
  margin: 4px 0;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.task-due {
  font-size: 0.8rem;
  color: var(--ion-color-medium);
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

.segment-label {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
}

.label-text {
  font-size: 0.9rem;
}

ion-segment {
  --background: var(--ion-color-light);
  margin: 8px 16px;
}

ion-segment-button {
  --indicator-color: var(--ion-color-primary);
  --color: var(--ion-color-medium);
  --color-checked: var(--ion-color-primary);
  min-height: 48px;
}

ion-searchbar {
  --background: var(--ion-color-light);
  margin: 8px 16px;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 32px 16px;
  text-align: center;
}

.empty-icon {
  font-size: 48px;
  color: var(--ion-color-medium);
  margin-bottom: 16px;
}

.empty-state h2 {
  font-size: 1.2rem;
  color: var(--ion-color-dark);
  margin: 0 0 8px 0;
}

.empty-state p {
  font-size: 0.9rem;
  color: var(--ion-color-medium);
  margin: 0;
}

.custom-fab {
  --background: var(--ion-color-primary);
  margin: 16px;
}

ion-item-divider {
  --background: transparent;
  --color: var(--ion-color-medium);
  font-size: 0.9rem;
  padding: 8px 16px;
}

ion-checkbox {
  --size: 20px;
  --checkbox-background-checked: var(--ion-color-primary);
  --border-color: var(--ion-color-medium);
  --border-color-checked: var(--ion-color-primary);
  margin-right: 12px;
  transition: all 0.2s ease;
}

ion-checkbox::part(container) {
  border-radius: 4px;
  border-width: 2px;
}

ion-checkbox.ion-checked {
  animation: checkmark 0.2s ease-in-out;
}

@keyframes checkmark {
  0% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.2);
  }
  100% {
    transform: scale(1);
  }
}

ion-badge {
  font-size: 0.7rem;
  padding: 4px 8px;
}

ion-button {
  --padding-start: 8px;
  --padding-end: 8px;
}

ion-button ion-icon {
  font-size: 18px;
}

/* Basic responsive adjustments */
@media (min-width: 768px) {
  ion-content {
    --padding-start: 24px;
    --padding-end: 24px;
  }

  ion-segment {
    margin: 12px 24px;
  }

  ion-searchbar {
    margin: 12px 24px;
  }
}

.sort-popover {
  --width: 250px;
}

.sort-option ion-button {
  --padding-start: 8px;
  --padding-end: 8px;
  font-size: 0.9rem;
  color: var(--ion-color-medium);
}

.sort-option ion-button:hover {
  --color: var(--ion-color-primary);
}

/* Add styles for the popover */
:deep(.sort-popover) {
  --background: var(--ion-color-light);
  --border-radius: 8px;
  --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

:deep(.sort-popover ion-item) {
  --padding-start: 16px;
  --padding-end: 16px;
  --min-height: 48px;
  font-size: 0.9rem;
}

:deep(.sort-popover ion-item:hover) {
  --background: var(--ion-color-light-shade);
}

:deep(.sort-popover ion-icon) {
  font-size: 18px;
}

/* Toast styling */
:deep(.custom-toast) {
  --background: var(--ion-color-success);
  --color: white;
  --border-radius: 8px;
  --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  --min-height: 48px;
  --min-width: 200px;
  --max-width: 300px;
  text-align: center;
  font-weight: 500;
}

.action-button {
  --padding-start: 12px;
  --padding-end: 12px;
  --padding-top: 8px;
  --padding-bottom: 8px;
  margin: 0 4px;
  height: 36px;
  font-size: 0.9rem;
}

.action-button ion-icon {
  font-size: 18px;
  margin-right: 4px;
}

/* Update task item to accommodate larger buttons */
.task-item {
  --padding-start: 16px;
  --padding-end: 16px;
  margin: 8px 0;
  --background: var(--ion-color-light);
  transition: all 0.3s ease;
  min-height: 72px;
}

/* Add responsive styles for buttons */
@media (max-width: 360px) {
  .action-button {
    --padding-start: 8px;
    --padding-end: 8px;
  }
  
  .action-button ion-icon {
    margin-right: 0;
  }
  
  .action-button span {
    display: none;
  }
}
</style>
