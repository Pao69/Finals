<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button default-href="/tabs/tasks"></ion-back-button>
        </ion-buttons>
        <ion-title>Task Details</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true">
      <div v-if="task" class="task-details">
        <div class="task-header">
          <ion-checkbox 
            :modelValue="task.completed === 1"
            @update:modelValue="toggleTaskCompletion"
            class="task-checkbox"
          ></ion-checkbox>
          <h1>{{ task.title }}</h1>
        </div>

        <div class="task-info">
          <div class="info-section">
            <ion-icon :icon="calendarOutline"></ion-icon>
            <div class="info-content">
              <h3>Due Date</h3>
              <p :class="getDueDateClass(task)">{{ formatDate(task.due_date) }}</p>
            </div>
          </div>

          <div class="info-section">
            <ion-icon :icon="documentTextOutline"></ion-icon>
            <div class="info-content">
              <h3>Description</h3>
              <p class="task-description">{{ task.description || 'No description provided' }}</p>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="loading-state">
        <ion-spinner name="crescent"></ion-spinner>
        <p>Loading task details...</p>
      </div>

      <div class="action-buttons">
        <ion-button expand="block" @click="editTask" class="edit-button">
          <ion-icon :icon="createOutline" slot="start"></ion-icon>
          Edit Task
        </ion-button>
        <ion-button expand="block" @click="confirmDelete" color="danger" class="delete-button">
          <ion-icon :icon="trashOutline" slot="start"></ion-icon>
          Delete Task
        </ion-button>
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
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import {
  IonPage, IonHeader, IonToolbar, IonTitle, IonContent,
  IonButtons, IonBackButton, IonButton, IonIcon,
  IonCheckbox, IonAlert, IonSpinner, toastController
} from '@ionic/vue';
import { 
  createOutline, trashOutline, calendarOutline,
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

const route = useRoute();
const router = useRouter();
const task = ref<Task | null>(null);
const showDeleteAlert = ref(false);

// Fetch task details
const fetchTaskDetails = async () => {
  try {
    const token = localStorage.getItem('token');
    if (!token) {
      router.push('/login');
      return;
    }

    const response = await axios.get(`http://localhost/codes/PROJ/dbConnect/tasks.php?taskId=${route.params.id}`, {
      headers: { 'Authorization': `Bearer ${token}` }
    });

    if (response.data.success) {
      task.value = response.data.task;
    } else {
      throw new Error(response.data.message || 'Failed to fetch task details');
    }
  } catch (error: any) {
    console.error('Error fetching task:', error);
    const toast = await toastController.create({
      message: 'Failed to fetch task details',
      duration: 2000,
      color: 'danger'
    });
    await toast.present();
    router.back();
  }
};

// Toggle task completion
const toggleTaskCompletion = async (completed: boolean) => {
  if (!task.value) return;

  try {
    const token = localStorage.getItem('token');
    const response = await axios.post(
      'http://localhost/codes/PROJ/dbConnect/tasks.php',
      {
        id: task.value.id,
        completed: completed ? 1 : 0
      },
      {
        headers: { 'Authorization': `Bearer ${token}` }
      }
    );

    if (response.data.success) {
      task.value.completed = completed ? 1 : 0;
      const toast = await toastController.create({
        message: 'Task updated successfully',
        duration: 2000,
        color: 'success'
      });
      await toast.present();
    }
  } catch (error: any) {
    console.error('Error updating task:', error);
    const toast = await toastController.create({
      message: 'Failed to update task',
      duration: 2000,
      color: 'danger'
    });
    await toast.present();
  }
};

// Delete task
const confirmDelete = () => {
  showDeleteAlert.value = true;
};

const deleteTask = async () => {
  if (!task.value) return;

  try {
    const token = localStorage.getItem('token');
    const response = await axios.delete('http://localhost/codes/PROJ/dbConnect/tasks.php', {
      headers: { 'Authorization': `Bearer ${token}` },
      data: { taskId: task.value.id }
    });

    if (response.data.success) {
      const toast = await toastController.create({
        message: 'Task deleted successfully',
        duration: 2000,
        color: 'success'
      });
      await toast.present();
      router.back();
    }
  } catch (error: any) {
    console.error('Error deleting task:', error);
    const toast = await toastController.create({
      message: 'Failed to delete task',
      duration: 2000,
      color: 'danger'
    });
    await toast.present();
  }

  showDeleteAlert.value = false;
};

// Edit task
const editTask = () => {
  if (task.value) {
    router.push(`/tabs/tasks/edit/${task.value.id}`);
  }
};

// Format date
const formatDate = (dateString: string): string => {
  const date = new Date(dateString);
  return date.toLocaleString();
};

// Get due date class
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

onMounted(() => {
  fetchTaskDetails();
});
</script>

<style scoped>
.task-details {
  max-width: 800px;
  margin: 0 auto;
  padding: 24px 16px 140px;
}

.task-header {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 32px;
  padding: 16px;
  background: var(--ion-color-light);
  border-radius: 12px;
}

.task-checkbox {
  --size: 28px;
  --checkbox-background-checked: var(--ion-color-primary);
  --border-color: var(--ion-color-medium);
  --border-color-checked: var(--ion-color-primary);
}

.task-header h1 {
  font-size: 1.5rem;
  font-weight: 600;
  margin: 0;
  color: var(--ion-color-dark);
}

.task-info {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.info-section {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  padding: 16px;
  background: var(--ion-color-light);
  border-radius: 12px;
}

.info-section ion-icon {
  font-size: 24px;
  color: var(--ion-color-primary);
  margin-top: 2px;
}

.info-content {
  flex: 1;
}

.info-content h3 {
  font-size: 0.9rem;
  font-weight: 500;
  color: var(--ion-color-medium);
  margin: 0 0 4px 0;
}

.info-content p {
  font-size: 1rem;
  color: var(--ion-color-dark);
  margin: 0;
}

.task-description {
  white-space: pre-wrap;
  line-height: 1.5;
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

.action-buttons {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 16px;
  background: var(--ion-background-color);
  box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
  display: flex;
  flex-direction: column;
  gap: 12px;
  z-index: 100;
}

.edit-button, .delete-button {
  margin: 0;
  height: 48px;
  font-weight: 500;
  --border-radius: 12px;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: var(--ion-color-medium);
}

.loading-state p {
  margin-top: 16px;
}

@media (min-width: 768px) {
  .task-details {
    padding: 32px 24px 140px;
  }

  .task-header {
    padding: 24px;
  }

  .info-section {
    padding: 24px;
  }

  .task-header h1 {
    font-size: 1.8rem;
  }

  .info-content h3 {
    font-size: 1rem;
  }

  .info-content p {
    font-size: 1.1rem;
  }
}
</style> 