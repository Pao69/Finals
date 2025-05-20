<template>
  <ion-page :key="String(route.params.id)">
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button default-href="/tabs/tasks"></ion-back-button>
        </ion-buttons>
        <ion-title>Task Details</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      <div v-if="task" class="task-details">
        <div class="task-header">
          <h1>{{ task.title }}</h1>
          <ion-badge :color="task.completed === 1 ? 'success' : 'warning'" class="status-badge">
            {{ task.completed === 1 ? 'Completed' : 'Pending' }}
          </ion-badge>
        </div>

        <ion-item lines="none" class="detail-item" v-if="task.description">
          <ion-icon :icon="documentTextOutline" slot="start" color="primary"></ion-icon>
          <ion-label class="ion-text-wrap">
            <h2>Description</h2>
            <p>{{ task.description }}</p>
          </ion-label>
        </ion-item>

        <ion-item lines="none" class="detail-item">
          <ion-icon :icon="calendarOutline" slot="start" color="primary"></ion-icon>
          <ion-label>
            <h2>Due Date</h2>
            <p>{{ formatDate(task.due_date) }}</p>
          </ion-label>
        </ion-item>

        <div class="action-buttons">
          <ion-button expand="block" :color="task.completed === 1 ? 'warning' : 'success'" @click="toggleCompletion">
            <ion-icon :icon="task.completed === 1 ? closeCircleOutline : checkmarkCircleOutline" slot="start"></ion-icon>
            {{ task.completed === 1 ? 'Mark as Pending' : 'Mark as Complete' }}
          </ion-button>
        </div>

        <div class="bottom-actions">
          <ion-button @click="editTask" color="primary" class="action-button">
            <ion-icon :icon="createOutline" slot="start"></ion-icon>
            Edit
          </ion-button>
          <ion-button @click="confirmDelete" color="danger" class="action-button">
            <ion-icon :icon="trashOutline" slot="start"></ion-icon>
            Delete
          </ion-button>
        </div>
      </div>

      <div v-else class="loading-state">
        <ion-spinner name="crescent"></ion-spinner>
        <p>Loading task details...</p>
      </div>
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
import { ref, onMounted, watch, onBeforeUnmount } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';
import { useTaskStore } from '../stores/taskStore';
import {
  IonPage,
  IonHeader,
  IonToolbar,
  IonTitle,
  IonContent,
  IonItem,
  IonLabel,
  IonButton,
  IonButtons,
  IonBackButton,
  IonIcon,
  IonBadge,
  IonSpinner,
  IonAlert,
  toastController
} from '@ionic/vue';
import {
  createOutline,
  calendarOutline,
  documentTextOutline,
  timeOutline,
  refreshOutline,
  checkmarkCircleOutline,
  closeCircleOutline,
  trashOutline
} from 'ionicons/icons';

interface Task {
  id: number;
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
    refreshTaskList: () => void;
  }
}

const router = useRouter();
const route = useRoute();
const taskStore = useTaskStore();
const task = ref<Task | null>(null);
const showDeleteAlert = ref(false);

// Function to update task data
const updateTaskData = (updatedTask: Task) => {
  task.value = { ...updatedTask };
  // Also refresh the task list in the parent component
  if (window.refreshTaskList) {
    window.refreshTaskList();
  }
};

// Make the update function available globally
window.updateTaskData = updateTaskData;

const formatDate = (dateString: string): string => {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

const formatDateTime = (dateString: string): string => {
  const date = new Date(dateString);
  return date.toLocaleString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const fetchTask = async () => {
  try {
    const token = localStorage.getItem('token');
    if (!token) {
      router.push('/login');
      return;
    }

    const taskId = route.params.id;
    const response = await axios.get<{success: boolean; task: Task}>(
      `http://localhost/codes/PROJ/dbConnect/tasks.php?taskId=${taskId}`,
      {
        headers: { 'Authorization': `Bearer ${token}` }
      }
    );

    if (response.data.success && response.data.task) {
      task.value = response.data.task;
    } else {
      throw new Error('Task not found');
    }
  } catch (error: any) {
    const toast = await toastController.create({
      message: error.response?.data?.message || error.message || 'Failed to fetch task',
      duration: 2000,
      color: 'danger'
    });
    await toast.present();
    router.back();
  }
};

// Watch for route changes to refresh task data
watch(() => route.params.id, (newId) => {
  if (newId) {
    fetchTask();
  }
}, { immediate: true });

// Cleanup on unmount
onBeforeUnmount(() => {
  if (showDeleteAlert.value) {
    showDeleteAlert.value = false;
  }
  // Remove focus from any focused elements
  if (document.activeElement instanceof HTMLElement) {
    document.activeElement.blur();
  }
  // Remove the global function
  delete window.updateTaskData;
});

const toggleCompletion = async () => {
  if (!task.value) return;

  try {
    const token = localStorage.getItem('token');
    if (!token) {
      router.push('/login');
      return;
    }

    const response = await axios.post(
      'http://localhost/codes/PROJ/dbConnect/tasks.php',
      {
        id: task.value.id,
        completed: task.value.completed === 1 ? 0 : 1
      },
      {
        headers: { 
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      }
    );

    if (response.data.success) {
      taskStore.setCurrentTask({ ...task.value, completed: task.value.completed === 1 ? 0 : 1 });
      const toast = await toastController.create({
        message: `Task marked as ${task.value.completed === 1 ? 'completed' : 'pending'}`,
        duration: 2000,
        color: 'success'
      });
      await toast.present();
    } else {
      throw new Error(response.data.message || 'Failed to update task');
    }
  } catch (error: any) {
    const toast = await toastController.create({
      message: error.response?.data?.message || error.message || 'Failed to update task',
      duration: 2000,
      color: 'danger'
    });
    await toast.present();
  }
};

const editTask = () => {
  if (task.value) {
    // Remove focus from any focused elements before navigation
    if (document.activeElement instanceof HTMLElement) {
      document.activeElement.blur();
    }
    router.push(`/tabs/tasks/edit/${task.value.id}`);
  }
};

const confirmDelete = () => {
  // Remove focus from any focused elements before showing alert
  if (document.activeElement instanceof HTMLElement) {
    document.activeElement.blur();
  }
  showDeleteAlert.value = true;
};

const deleteTask = async () => {
  if (!task.value) return;

  try {
    const token = localStorage.getItem('token');
    if (!token) {
      router.push('/login');
      return;
    }

    const response = await axios.delete(
      'http://localhost/codes/PROJ/dbConnect/tasks.php',
      {
        headers: { 'Authorization': `Bearer ${token}` },
        data: { taskId: task.value.id }
      }
    );

    if (response.data.success) {
      const toast = await toastController.create({
        message: 'Task deleted successfully',
        duration: 2000,
        color: 'success'
      });
      await toast.present();
      
      // Refresh the task list before navigating back
      if (window.refreshTaskList) {
        window.refreshTaskList();
      }
      
      router.back();
    } else {
      throw new Error(response.data.message || 'Failed to delete task');
    }
  } catch (error: any) {
    const toast = await toastController.create({
      message: error.response?.data?.message || error.message || 'Failed to delete task',
      duration: 2000,
      color: 'danger'
    });
    await toast.present();
  }

  showDeleteAlert.value = false;
};
</script>

<style scoped>
.task-details {
  max-width: 800px;
  margin: 0 auto;
  padding: 1rem;
  position: relative;
  min-height: calc(100vh - 120px);
}

.task-header {
  margin-bottom: 2rem;
  text-align: center;
}

.task-header h1 {
  font-size: 1.8rem;
  margin: 0 0 0.5rem 0;
  color: var(--ion-color-dark);
}

.status-badge {
  font-size: 0.9rem;
  padding: 0.5rem 1rem;
  border-radius: 20px;
}

.detail-item {
  --padding-start: 0;
  --inner-padding-end: 0;
  margin-bottom: 1rem;
}

.detail-item ion-icon {
  font-size: 1.5rem;
  margin-right: 1rem;
}

.detail-item h2 {
  font-size: 1rem;
  color: var(--ion-color-medium);
  margin: 0 0 0.25rem 0;
}

.detail-item p {
  font-size: 1.1rem;
  color: var(--ion-color-dark);
  margin: 0;
}

.action-buttons {
  margin: 2rem 0;
}

.bottom-actions {
  position: fixed;
  bottom: 2rem;
  right: 2rem;
  display: flex;
  gap: 1rem;
  z-index: 100;
}

.action-button {
  --padding-start: 1.5rem;
  --padding-end: 1.5rem;
  --padding-top: 0.75rem;
  --padding-bottom: 0.75rem;
  height: 48px;
  font-size: 1rem;
  font-weight: 500;
  --border-radius: 12px;
  --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.action-button ion-icon {
  font-size: 1.2rem;
  margin-right: 0.5rem;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  padding: 2rem;
}

.loading-state ion-spinner {
  width: 48px;
  height: 48px;
  margin-bottom: 1rem;
}

.loading-state p {
  color: var(--ion-color-medium);
  margin: 0;
  font-size: 1rem;
}

@media (max-width: 768px) {
  .bottom-actions {
    bottom: 1rem;
    right: 1rem;
  }

  .action-button {
    --padding-start: 1rem;
    --padding-end: 1rem;
    height: 40px;
    font-size: 0.9rem;
  }
}

@media (max-width: 480px) {
  .bottom-actions {
    bottom: 0.5rem;
    right: 0.5rem;
  }

  .action-button {
    --padding-start: 0.75rem;
    --padding-end: 0.75rem;
    height: 36px;
    font-size: 0.85rem;
  }

  .action-button ion-icon {
    font-size: 1rem;
    margin-right: 0.25rem;
  }
}
</style> 