<template>
  <page-layout title="Task Details" :showBackButton="true" backHref="/tabs/tasks">
    <ion-content :fullscreen="true">
      <div v-if="task" class="task-details">
        <div class="task-header">
          <ion-checkbox 
            :modelValue="task.completed === 1"
            @update:modelValue="toggleTaskCompletion"
            class="task-checkbox"
          ></ion-checkbox>
          <h1 :class="{ completed: task.completed === 1 }">{{ task.title }}</h1>
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

        <!-- Linked Resources Section -->
        <div class="task-resources" v-if="resources.length > 0">
          <h2>
            <ion-icon :icon="documentsOutline"></ion-icon>
            Linked Resources
          </h2>
          <ion-list>
            <ion-item v-for="resource in resources" :key="resource.id" class="resource-item">
              <ion-thumbnail slot="start" class="resource-thumbnail">
                <img v-if="isImage(resource.file_type)" :src="getResourceUrl(resource.filename)" alt="Resource thumbnail" />
                <ion-icon v-else :icon="getFileIcon(resource.file_type)" size="large"></ion-icon>
              </ion-thumbnail>
              
              <ion-label class="ion-text-wrap">
                <h3>{{ resource.original_filename }}</h3>
                <p>{{ resource.description }}</p>
                <p class="resource-meta">
                  <ion-text color="medium">
                    {{ formatFileSize(resource.file_size) }} • 
                    {{ formatDate(resource.upload_date) }}
                  </ion-text>
                </p>
              </ion-label>

              <ion-buttons slot="end">
                <ion-button @click="downloadResource(resource)">
                  <ion-icon :icon="downloadOutline" slot="icon-only"></ion-icon>
                </ion-button>
              </ion-buttons>
            </ion-item>
          </ion-list>
        </div>
      </div>

      <div v-else class="loading-state">
        <ion-spinner name="crescent"></ion-spinner>
        <p>Loading task details...</p>
      </div>

      <div class="action-buttons">
        <ion-button expand="block" @click="editTask" class="edit-button" v-if="canModifyTask">
          <ion-icon :icon="createOutline" slot="start"></ion-icon>
          Edit Task
        </ion-button>
        <ion-button expand="block" @click="confirmDelete" color="danger" class="delete-button" v-if="canModifyTask">
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
  </page-layout>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import {
  IonContent, IonButton, IonIcon,
  IonCheckbox, IonAlert, IonSpinner, toastController,
  IonChip, IonLabel, IonThumbnail, IonList, IonItem, IonButtons
} from '@ionic/vue';
import { 
  createOutline, trashOutline, calendarOutline,
  documentTextOutline, checkmarkCircle, closeCircle,
  documentsOutline, downloadOutline, imageOutline,
  documentOutline
} from 'ionicons/icons';
import PageLayout from '@/components/PageLayout.vue';

interface Task {
  id: number;
  user_id: number;
  title: string;
  description: string;
  due_date: string;
  completed: number;
  created_at: string;
  updated_at: string;
  owner_username: string;
}

interface Resource {
  id: number;
  user_id: number;
  task_id: number;
  filename: string;
  original_filename: string;
  file_type: string;
  file_size: number;
  description: string;
  upload_date: string;
}

const route = useRoute();
const router = useRouter();
const task = ref<Task | null>(null);
const resources = ref<Resource[]>([]);
const showDeleteAlert = ref(false);

// Add computed property to check if user can edit/delete
const currentUser = ref(JSON.parse(localStorage.getItem('user') || '{}'));
const canModifyTask = computed(() => {
  return task.value && (
    task.value.user_id === currentUser.value.id || 
    currentUser.value.role === 'admin'
  );
});

// Add onMounted to fetch task details when component loads
onMounted(async () => {
  await fetchTaskDetails();
});

// Fetch task details
const fetchTaskDetails = async () => {
  try {
    const token = localStorage.getItem('token') || sessionStorage.getItem('token');
    if (!token) {
      router.push('/login');
      return;
    }

    const response = await axios.get(`http://localhost/codes/PROJ/dbConnect/tasks.php?taskId=${route.params.id}`, {
      headers: { 'Authorization': `Bearer ${token}` }
    });

    if (response.data.success) {
      task.value = response.data.task;
      await fetchResources();
    } else {
      throw new Error(response.data.message || 'Failed to fetch task details');
    }
  } catch (error: any) {
    console.error('Error fetching task:', error);
    const toast = await toastController.create({
      message: error.response?.data?.message || 'Failed to fetch task details',
      duration: 2000,
      color: 'danger'
    });
    await toast.present();
    router.back();
  }
};

// Fetch linked resources
const fetchResources = async () => {
  try {
    const token = localStorage.getItem('token') || sessionStorage.getItem('token');
    const response = await axios.get(`http://localhost/codes/PROJ/dbConnect/resources.php?task_id=${task.value?.id}`, {
      headers: { 'Authorization': `Bearer ${token}` }
    });

    if (response.data.success) {
      resources.value = response.data.resources || [];
    }
  } catch (error) {
    console.error('Error fetching resources:', error);
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
        color: 'success',
        position: 'top',
        icon: checkmarkCircle
      });
      await toast.present();

      // Refresh the page after a shorter delay
      setTimeout(() => {
        window.location.reload();
      }, 200);
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
        color: 'success',
        position: 'top',
        icon: checkmarkCircle
      });
      await toast.present();

      // Navigate back after a shorter delay
      setTimeout(() => {
        router.push('/tabs/tasks');
      }, 200);
    }
  } catch (error: any) {
    console.error('Error deleting task:', error);
    const toast = await toastController.create({
      message: 'Failed to delete task',
      duration: 2000,
      color: 'danger',
      position: 'top',
      icon: closeCircle
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

// Resource utility functions
const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const isImage = (fileType: string): boolean => {
  return fileType.startsWith('image/');
};

const getFileIcon = (fileType: string) => {
  if (fileType.startsWith('image/')) return imageOutline;
  if (fileType.includes('pdf')) return documentOutline;
  if (fileType.includes('word')) return documentTextOutline;
  return documentOutline;
};

const getResourceUrl = (filename: string): string => {
  return `http://localhost/codes/PROJ/uploads/${filename}`;
};

const downloadResource = (resource: Resource) => {
  const link = document.createElement('a');
  link.href = getResourceUrl(resource.filename);
  link.download = resource.original_filename;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};
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

.task-header h1.completed {
  text-decoration: line-through;
  color: var(--ion-color-medium);
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

.task-resources {
  margin: 24px 0;
  padding-top: 16px;
  border-top: 1px solid var(--ion-color-light);
}

.task-resources h2 {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 1.2rem;
  color: var(--ion-color-dark);
  margin-bottom: 16px;
}

.task-resources ion-icon {
  font-size: 24px;
  color: var(--ion-color-primary);
}

.resource-thumbnail {
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--ion-color-light);
  border-radius: 8px;
}

.resource-thumbnail img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 8px;
}

.resource-thumbnail ion-icon {
  font-size: 24px;
  color: var(--ion-color-medium);
}

.resource-meta {
  font-size: 0.85em;
  margin-top: 4px;
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