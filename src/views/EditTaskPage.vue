<template>
  <page-layout title="Edit Task" :showBackButton="true" backHref="/tabs/tasks">
    <ion-content class="ion-padding">
      <div class="edit-task-container">
        <form @submit.prevent="handleSubmit" class="task-form" v-if="taskForm">
          <div class="status-section">
            <ion-chip :color="isCompleted ? 'success' : 'warning'" class="status-chip">
              <ion-icon :icon="isCompleted ? checkmarkCircleOutline : timeOutline"></ion-icon>
              <ion-label>{{ isCompleted ? 'Completed' : 'Pending' }}</ion-label>
            </ion-chip>
          </div>

          <div class="form-section">
            <div class="section-header">
              <h2>Task Details</h2>
            </div>
            
            <ion-item class="form-item" :class="{ 'ion-valid': taskForm.title.trim() !== '' }">
              <ion-label position="stacked">Title <ion-text color="danger">*</ion-text></ion-label>
              <ion-input
                v-model="taskForm.title"
                type="text"
                placeholder="Enter task title"
                required
                class="custom-input"
              ></ion-input>
            </ion-item>

            <ion-item class="form-item">
              <ion-label position="stacked">Description</ion-label>
              <ion-textarea
                v-model="taskForm.description"
                placeholder="Enter task description"
                :rows="4"
                class="custom-textarea"
              ></ion-textarea>
            </ion-item>

            <ion-item class="form-item">
              <ion-label position="stacked">Priority <ion-text color="danger">*</ion-text></ion-label>
              <ion-select
                v-model="taskForm.priority"
                placeholder="Select priority"
                class="custom-select"
                required>
                <ion-select-option value="high">High</ion-select-option>
                <ion-select-option value="medium">Medium</ion-select-option>
                <ion-select-option value="low">Low</ion-select-option>
              </ion-select>
            </ion-item>
          </div>

          <div class="form-section">
            <div class="section-header">
              <h2>Schedule</h2>
            </div>

            <ion-item class="form-item" :class="{ 'ion-valid': taskForm.due_date !== '' }">
              <ion-label position="stacked">Due Date <ion-text color="danger">*</ion-text></ion-label>
              <input
                type="datetime-local"
                v-model="taskForm.due_date"
                :min="minDateTime"
                required
                class="custom-datetime"
              >
            </ion-item>
          </div>

          <div class="form-section">
            <div class="section-header">
              <h2>Status</h2>
            </div>

            <ion-item class="form-item toggle-item">
              <ion-label>Mark as Completed</ion-label>
              <ion-toggle
                :model-value="isCompleted"
                @ion-change="e => handleCompletedChange(e.detail.checked)"
                class="custom-toggle"
              ></ion-toggle>
            </ion-item>
          </div>

          <div class="form-actions">
            <ion-button expand="block" type="submit" :disabled="!isFormValid" class="submit-button">
              <ion-icon :icon="saveOutline" slot="start"></ion-icon>
              Save Changes
            </ion-button>
            <ion-button expand="block" fill="outline" @click="router.back()" class="cancel-button">
              <ion-icon :icon="closeOutline" slot="start"></ion-icon>
              Cancel
            </ion-button>
          </div>
        </form>

        <div v-else class="loading-state">
          <ion-spinner name="crescent"></ion-spinner>
          <p>Loading task...</p>
        </div>
      </div>
    </ion-content>
  </page-layout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';
import api from '@/utils/api';
import {
  IonContent,
  IonItem,
  IonLabel,
  IonInput,
  IonTextarea,
  IonButton,
  IonButtons,
  IonDatetime,
  IonToggle,
  IonIcon,
  IonBadge,
  IonText,
  IonSelect,
  IonSelectOption,
  toastController
} from '@ionic/vue';
import {
  saveOutline,
  closeOutline,
  checkmarkCircleOutline,
  timeOutline,
  checkmarkCircle,
  closeCircle
} from 'ionicons/icons';
import PageLayout from '@/components/PageLayout.vue';

interface TaskForm {
  id: number;
  user_id: number;
  title: string;
  description: string;
  due_date: string;
  completed: number;
  priority: 'low' | 'medium' | 'high';
  created_at?: string;
  updated_at?: string;
}

interface TaskResponse {
  success: boolean;
  task: {
    id: number;
    user_id: number;
    title: string;
    description: string;
    due_date: string;
    completed: string;
    priority: 'low' | 'medium' | 'high';
    created_at: string;
    updated_at: string;
  };
}

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
const taskForm = ref<TaskForm | null>(null);

// Compute minimum date time (current time)
const minDateTime = new Date().toISOString().split('T')[0];

// Form validation
const isFormValid = computed(() => {
  return taskForm.value?.title.trim() !== '' && taskForm.value?.due_date !== '';
});

const isCompleted = computed(() => taskForm.value?.completed === 1);

const handleCompletedChange = (checked: boolean) => {
  if (taskForm.value) {
    taskForm.value.completed = checked ? 1 : 0;
  }
};

// Add onMounted to fetch task data when component loads
onMounted(async () => {
  await fetchTask();
});

// Fetch task data
const fetchTask = async () => {
  try {
    const taskId = route.params.id;
    const response = await api.get<TaskResponse>(`/tasks.php?taskId=${taskId}`);

    if (response.data.success && response.data.task) {
      const task = response.data.task;
      const completed = parseInt(task.completed, 10);
      if (isNaN(completed)) {
        throw new Error('Invalid completed status');
      }

      // Create a Date object from the due_date string
      const dueDate = new Date(task.due_date);
      
      // Format the date as YYYY-MM-DDTHH:mm (format required by datetime-local input)
      const formattedDueDate = dueDate.toISOString().slice(0, 16);

      taskForm.value = {
        id: task.id,
        user_id: task.user_id,
        title: task.title,
        description: task.description || '',
        due_date: formattedDueDate,
        completed: completed,
        priority: task.priority || 'medium',
        created_at: task.created_at,
        updated_at: task.updated_at
      };
    } else {
      throw new Error('Failed to fetch task');
    }
  } catch (error: any) {
    console.error('Error fetching task:', error);
    const toast = await toastController.create({
      message: error.response?.data?.message || 'Failed to fetch task',
      duration: 2000,
      color: 'danger',
      position: 'top',
      icon: closeCircle
    });
    await toast.present();
    router.back();
  }
};

const handleSubmit = async () => {
  if (!taskForm.value || !isFormValid.value) return;

  try {
    // Convert the input datetime to a Date object
    const dueDate = new Date(taskForm.value.due_date);
    
    // Format the date as MM/DD/YYYY HH:MM AM/PM
    const month = String(dueDate.getMonth() + 1).padStart(2, '0');
    const day = String(dueDate.getDate()).padStart(2, '0');
    const year = dueDate.getFullYear();
    
    let hours = dueDate.getHours();
    const minutes = String(dueDate.getMinutes()).padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    
    // Convert hours to 12-hour format
    hours = hours % 12;
    hours = hours ? hours : 12; // the hour '0' should be '12'
    const formattedHours = String(hours).padStart(2, '0');

    const formattedDate = `${month}/${day}/${year} ${formattedHours}:${minutes} ${ampm}`;

    // Prepare the request data
    const requestData = {
      id: taskForm.value.id,
      title: taskForm.value.title.trim(),
      description: taskForm.value.description.trim(),
      due_date: formattedDate,
      completed: taskForm.value.completed,
      priority: taskForm.value.priority
    };

    console.log('Sending update request with data:', requestData); // Debug log

    const response = await api.post('/tasks.php', requestData);

    console.log('Server response:', response.data); // Debug log

    if (response.data.success) {
      const toast = await toastController.create({
        message: 'Task updated successfully',
        duration: 2000,
        color: 'success',
        position: 'top',
        icon: checkmarkCircle
      });
      await toast.present();

      // Navigate back to tasks page and trigger refresh
      router.push('/tabs/tasks').then(() => {
        // Use the global refresh function if available
        if (window.refreshTaskList) {
          window.refreshTaskList();
        }
      });
    } else {
      throw new Error(response.data.message || 'Failed to update task');
    }
  } catch (error: any) {
    console.error('Error updating task:', error);
    console.error('Error response:', error.response?.data); // Additional error logging
    
    let errorMessage = 'Failed to update task';
    if (error.response?.data?.message) {
      errorMessage = error.response.data.message;
    } else if (error.message) {
      errorMessage = error.message;
    }

    const toast = await toastController.create({
      message: errorMessage,
      duration: 3000,
      color: 'danger',
      position: 'top',
      icon: closeCircle
    });
    await toast.present();
  }
};

// Add cleanup for modal and focus
onBeforeUnmount(async () => {
  // Close any open modals before unmounting
  const modal = document.querySelector('ion-modal');
  if (modal) {
    await modal.dismiss();
  }
  
  // Remove focus from any focused elements
  if (document.activeElement instanceof HTMLElement) {
    document.activeElement.blur();
  }

  // Remove aria-hidden from parent elements
  const hiddenElements = document.querySelectorAll('[aria-hidden="true"]');
  hiddenElements.forEach(el => {
    el.removeAttribute('aria-hidden');
  });
});
</script>

<style scoped>
.edit-task-container {
  max-width: 600px;
  margin: 0.5rem auto;
  padding: 1rem;
}

.task-form {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.status-section {
  display: flex;
  justify-content: center;
  margin-bottom: 0.5rem;
}

.status-chip {
  --background: transparent;
  --color: inherit;
  height: 32px;
  padding: 0 16px;
  font-weight: 500;
}

.status-chip ion-icon {
  font-size: 18px;
}

.form-section {
  background: var(--ion-color-light);
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.section-header {
  margin-bottom: 1.25rem;
}

.section-header h2 {
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--ion-color-dark);
  margin: 0;
}

.form-item {
  --background: var(--ion-background-color);
  --border-radius: 12px;
  --padding-start: 16px;
  --padding-end: 16px;
  margin: 0 0 1rem;
  border: 1px solid var(--ion-color-medium-shade);
}

.form-item:last-child {
  margin-bottom: 0;
}

.form-item ion-label {
  font-size: 0.9rem;
  font-weight: 500;
  color: var(--ion-color-medium);
  margin-bottom: 0.5rem;
}

.custom-input,
.custom-textarea {
  --padding-start: 12px;
  --padding-end: 12px;
  --background: var(--ion-background-color);
  border-radius: 8px;
  font-size: 1rem;
  --color: var(--ion-color-dark);
}

.custom-textarea {
  min-height: 120px;
  line-height: 1.5;
}

.custom-datetime {
  width: 100%;
  padding: 12px;
  border: 1px solid var(--ion-color-medium-shade);
  border-radius: 8px;
  background: var(--ion-background-color);
  color: var(--ion-text-color);
  font-size: 16px;
  margin-top: 8px;
}

.custom-datetime:focus {
  outline: none;
  border-color: var(--ion-color-primary);
  box-shadow: 0 0 0 2px rgba(var(--ion-color-primary-rgb), 0.2);
}

.toggle-item {
  --padding-start: 16px;
  --padding-end: 16px;
  border: none;
  background: var(--ion-background-color);
}

.toggle-item ion-label {
  font-size: 0.95rem;
  color: var(--ion-color-dark);
}

.custom-toggle {
  --background: var(--ion-color-medium);
  --background-checked: var(--ion-color-success);
  --handle-background: var(--ion-color-light);
  --handle-background-checked: var(--ion-color-light);
  --handle-width: 20px;
  --handle-height: 20px;
  --handle-spacing: 2px;
  --handle-box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.form-actions {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.submit-button {
  --border-radius: 12px;
  --box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  font-weight: 500;
  --background: var(--ion-color-primary);
  height: 48px;
  font-size: 1rem;
  letter-spacing: 0.5px;
}

.cancel-button {
  --border-radius: 12px;
  --border-color: var(--ion-color-medium);
  --color: var(--ion-color-medium);
  font-weight: 500;
  height: 48px;
  font-size: 1rem;
  letter-spacing: 0.5px;
}

.cancel-button:hover {
  --background: var(--ion-color-light-shade);
}

/* Loading state */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 2rem;
  text-align: center;
}

.loading-state ion-spinner {
  width: 40px;
  height: 40px;
  margin-bottom: 1rem;
  --color: var(--ion-color-primary);
}

.loading-state p {
  color: var(--ion-color-medium);
  margin: 0;
  font-size: 0.9rem;
}

/* Responsive adjustments */
@media (min-width: 768px) {
  .edit-task-container {
    margin: 1.5rem auto;
    padding: 1.5rem;
  }

  .form-section {
    padding: 2rem;
  }
}
</style>
