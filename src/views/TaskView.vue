<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button default-href="/tabs/tasks"></ion-back-button>
        </ion-buttons>
        <ion-title>Task Details</ion-title>
        <ion-buttons slot="end">
          <ion-button @click="editTask">
            <ion-icon :icon="createOutline" slot="icon-only"></ion-icon>
          </ion-button>
        </ion-buttons>
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

        <ion-item lines="none" class="detail-item">
          <ion-icon :icon="calendarOutline" slot="start" color="primary"></ion-icon>
          <ion-label>
            <h2>Due Date</h2>
            <p>{{ formatDate(task.due_date) }}</p>
          </ion-label>
        </ion-item>

        <ion-item lines="none" class="detail-item" v-if="task.description">
          <ion-icon :icon="documentTextOutline" slot="start" color="primary"></ion-icon>
          <ion-label class="ion-text-wrap">
            <h2>Description</h2>
            <p>{{ task.description }}</p>
          </ion-label>
        </ion-item>

        <ion-item lines="none" class="detail-item">
          <ion-icon :icon="timeOutline" slot="start" color="primary"></ion-icon>
          <ion-label>
            <h2>Created</h2>
            <p>{{ formatDateTime(task.created_at) }}</p>
          </ion-label>
        </ion-item>

        <ion-item lines="none" class="detail-item" v-if="task.updated_at">
          <ion-icon :icon="refreshOutline" slot="start" color="primary"></ion-icon>
          <ion-label>
            <h2>Last Updated</h2>
            <p>{{ formatDateTime(task.updated_at) }}</p>
          </ion-label>
        </ion-item>

        <div class="task-actions ion-padding-top">
          <ion-button expand="block" @click="toggleCompletion" :color="task.completed === 1 ? 'warning' : 'success'">
            <ion-icon :icon="task.completed === 1 ? closeCircleOutline : checkmarkCircleOutline" slot="start"></ion-icon>
            {{ task.completed === 1 ? 'Mark as Pending' : 'Mark as Completed' }}
          </ion-button>
          <ion-button expand="block" color="danger" @click="confirmDelete">
            <ion-icon :icon="trashOutline" slot="start"></ion-icon>
            Delete Task
          </ion-button>
        </div>
      </div>

      <div v-else class="ion-padding ion-text-center">
        <ion-spinner></ion-spinner>
        <p>Loading task...</p>
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
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';
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

const router = useRouter();
const route = useRoute();
const task = ref<Task | null>(null);
const showDeleteAlert = ref(false);

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

const toggleCompletion = async () => {
  if (!task.value) return;

  try {
    const token = localStorage.getItem('token');
    if (!token) {
      router.push('/login');
      return;
    }

    const response = await axios.put(
      'http://localhost/codes/PROJ/dbConnect/tasks.php',
      {
        taskId: task.value.id,
        title: task.value.title,
        description: task.value.description,
        due_date: task.value.due_date,
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
      task.value.completed = task.value.completed === 1 ? 0 : 1;
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
    router.push(`/tabs/tasks/edit/${task.value.id}`);
  }
};

const confirmDelete = () => {
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

onMounted(() => {
  fetchTask();
});
</script>

<style scoped>
.task-details {
  max-width: 600px;
  margin: 0 auto;
}

.task-header {
  margin-bottom: 2rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.task-header h1 {
  margin: 0;
  font-size: 1.8rem;
  font-weight: 600;
}

.status-badge {
  font-size: 0.9rem;
  padding: 0.5rem 1rem;
}

.detail-item {
  --padding-start: 0;
  margin-bottom: 1rem;
}

.detail-item ion-icon {
  font-size: 1.5rem;
  margin-right: 1rem;
}

.detail-item h2 {
  font-size: 1rem;
  font-weight: 600;
  color: var(--ion-color-medium);
  margin-bottom: 0.25rem;
}

.detail-item p {
  font-size: 1.1rem;
  color: var(--ion-color-dark);
}

.task-actions {
  margin-top: 2rem;
}

.task-actions ion-button {
  margin-bottom: 1rem;
}
</style> 