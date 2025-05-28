<!--
  NOTE: The following comments are for educational/debugging purposes and may not cover all edge cases.
  TasksPage.vue - Main tasks listing page with filtering, sorting, and task management functionality.
-->
<template>
  
  <page-layout title="My Tasks" :show-back-button="false">
    <template #additional-toolbar>
      <ion-toolbar>
        <div class="search-container">
          <ion-searchbar
            v-model="searchQuery"
            placeholder="Search tasks..."
            @ionInput="handleSearch"
            class="custom-searchbar"
            inputmode="search"
            clear-icon="close-circle">
          </ion-searchbar>
        </div>
      </ion-toolbar>
      <ion-segment v-model="selectedFilter" @ionChange="handleFilterChange">
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
    </template>

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
                  <div class="task-meta">
                    <ion-badge :color="getStatusColor(task.completed)">
                      {{ task.completed === 1 ? 'Completed' : 'Pending' }}
                    </ion-badge>
                    <ion-badge :color="getPriorityColor(task.priority)">
                      {{ task.priority }}
                    </ion-badge>
                    <p class="task-due" :class="getDueDateClass(task)">Due {{ formatDate(task.due_date) }}</p>
                  </div>
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
                <div class="task-meta">
                  <ion-badge :color="getStatusColor(task.completed)">
                    {{ task.completed === 1 ? 'Completed' : 'Pending' }}
                  </ion-badge>
                  <ion-badge :color="getPriorityColor(task.priority)">
                    {{ task.priority }}
                  </ion-badge>
                  <p class="task-due" :class="getDueDateClass(task)">Due {{ formatDate(task.due_date) }}</p>
                </div>
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

    </ion-content>
    <!-- Floating Action Button -->
    <ion-fab vertical="bottom" horizontal="end" slot="fixed">
        <ion-fab-button @click="goToAddTask" class="custom-fab">
          <ion-icon :icon="addOutline"></ion-icon>
        </ion-fab-button>
      </ion-fab>

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
  </page-layout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, onBeforeUnmount } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';
import api from '@/utils/api';
import {
  IonContent,
  IonList,
  IonItem,
  IonItemGroup,
  IonItemDivider,
  IonLabel,
  IonCheckbox,
  IonFab,
  IonFabButton,
  IonIcon,
  IonButton,
  IonAlert,
  IonSearchbar,
  IonSegment,
  IonSegmentButton,
  IonBadge,
  IonToolbar,
  popoverController,
  toastController
} from '@ionic/vue';
import { 
  addOutline,
  swapVerticalOutline, 
  clipboardOutline,
  checkmarkCircle,
  closeCircle,
  checkmarkOutline
} from 'ionicons/icons';
import PageLayout from '@/components/PageLayout.vue';

interface Task {
  id: number;
  user_id: number;
  title: string;
  description: string;
  due_date: string;
  completed: number;
  priority: 'low' | 'medium' | 'high';
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
const loading = ref(false);

// Add sort options
const sortOptions = [
  { label: 'Due Date (Earliest)', value: 'due_date_asc' },
  { label: 'Due Date (Latest)', value: 'due_date_desc' },
  { label: 'Title (A-Z)', value: 'title_asc' },
  { label: 'Title (Z-A)', value: 'title_desc' },
  { label: 'Created Date (Newest)', value: 'created_desc' },
  { label: 'Created Date (Oldest)', value: 'created_asc' }
];

onMounted(async () => {
  await fetchTasks();
});

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
    loading.value = true;
    const response = await api.get('/tasks.php');
    if (response.data.success) {
      tasks.value = response.data.tasks;
    }
  } catch (error) {
    console.error('Error fetching tasks:', error);
    const toast = await toastController.create({
      message: 'Failed to fetch tasks',
      duration: 2000,
      color: 'danger',
      position: 'top'
    });
    await toast.present();
  } finally {
    loading.value = false;
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
    const response = await api.post('/tasks.php', {
      id: task.id,
      completed: completed ? 1 : 0
    });

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
    const response = await api.delete('/tasks.php', {
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

// Add navigation watcher
watch(
  () => route.fullPath,
  async (newPath, oldPath) => {
    // Check if we're coming back from the add task page
    if (oldPath?.includes('/tasks/add') && !newPath.includes('/tasks/add')) {
      await fetchTasks();
    }
  }
);

// Update onMounted to also watch for navigation events
onMounted(() => {
  fetchTasks();
  // Set initial filter from URL if present
  if (route.query.filter) {
    selectedFilter.value = route.query.filter as string;
  }
});

// Add priority color function
const getPriorityColor = (priority: string) => {
  switch (priority) {
    case 'high': return 'danger';
    case 'medium': return 'warning';
    case 'low': return 'success';
    default: return 'medium';
  }
};

const getStatusColor = (completed: number) => completed === 1 ? 'success' : 'warning';
</script>

<style scoped>
/* Base styles */
ion-content {
  --padding-top: 16px;
  --padding-bottom: 80px;
}

/* Custom searchbar and toolbar */
ion-toolbar {
  --min-height: 56px;
  --padding-top: 0;
  --padding-bottom: 0;
  position: relative;
  background: var(--ion-background-color);
}

.search-container {
  padding: 8px 16px;
  margin: 0;
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--ion-background-color);
}

.custom-searchbar {
  --background: var(--ion-color-light);
  --border-radius: 10px;
  --box-shadow: none;
  --placeholder-color: var(--ion-color-medium);
  --icon-color: var(--ion-color-primary);
  --padding-top: 0;
  --padding-bottom: 0;
  --min-height: 44px;
  margin: 0;
  max-width: 100%;
  width: auto;
}

ion-segment {
  padding: 8px 16px;
  background: var(--ion-background-color);
  position: relative;
  margin-bottom: 8px;
}

/* Add spacing after the search container */
.search-container + * {
  margin-top: 8px;
}

.hide-sm {
  @media (max-width: 360px) {
    display: none;
  }
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

ion-badge {
  --padding-start: 8px;
  --padding-end: 8px;
  --padding-top: 3px;
  --padding-bottom: 3px;
  font-size: 0.8rem;
}

@media (max-width: 360px) {
  .custom-searchbar {
    --min-height: 40px;
  }
  
  ion-toolbar {
    --min-height: 48px;
  }

  ion-segment {
    top: 48px;
  }
}

@media (min-width: 768px) {
  .custom-searchbar {
    max-width: 800px;
    margin: 0 auto;
  }

  ion-segment {
    max-width: 800px;
    margin: 0 auto;
  }
}

/* Task items */
.task-item {
  --background: var(--ion-card-background);
  margin: 8px 12px;
  border-radius: 12px;
  border: 1px solid var(--ion-border-color);
  --padding-start: 12px;
  --padding-end: 12px;
}

.task-description {
  color: var(--ion-color-medium);
  margin: 4px 0;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  font-size: 0.9rem;
  line-height: 1.4;
}

.task-meta {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 8px;
}

.task-meta ion-badge {
  text-transform: capitalize;
  font-weight: 500;
}

.task-due {
  margin: 0;
  font-size: 0.9rem;
  color: var(--ion-color-medium);
}

/* Empty state */
.empty-state {
  text-align: center;
  padding: 24px 16px;
  color: var(--ion-color-medium);
  
  .empty-icon {
    font-size: 48px;
    margin-bottom: 12px;
  }
  
  h2 {
    font-size: 1.1rem;
    margin-bottom: 8px;
  }
  
  p {
    font-size: 0.9rem;
    margin: 0;
    padding: 0 16px;
  }
}

/* FAB button */
.custom-fab {
  margin: 0 16px 16px 0;
}

/* Responsive styles */
@media (min-width: 768px) {
  ion-content {
    --padding-bottom: 100px;
  }

  ion-segment {
    max-width: 800px;
    margin: 16px auto;
  }

  ion-segment-button {
    font-size: 1rem;
    min-height: 48px;
    
    .label-text {
      font-size: 1rem;
    }
    
    ion-badge {
      font-size: 0.85rem;
      --padding-start: 8px;
      --padding-end: 8px;
    }
  }

  .task-item {
    max-width: 800px;
    margin: 8px auto;
    --padding-start: 16px;
    --padding-end: 16px;
  }

  .task-description {
    font-size: 1rem;
  }

  .task-due {
    font-size: 0.9rem;
  }

  .empty-state {
    max-width: 800px;
    margin: 32px auto;
    padding: 32px 16px;
    
    .empty-icon {
      font-size: 64px;
    }
    
    h2 {
      font-size: 1.2rem;
    }
  }
}

/* Small screen adjustments */
@media (max-width: 360px) {
  ion-segment-button {
    font-size: 0.8rem;
    padding: 2px;
    
    .label-text {
      margin-bottom: 2px;
    }
    
    ion-badge {
      --padding-start: 4px;
      --padding-end: 4px;
      font-size: 0.7rem;
    }
  }

  .task-item {
    margin: 6px 8px;
    --padding-start: 8px;
    --padding-end: 8px;
  }
}
</style>
