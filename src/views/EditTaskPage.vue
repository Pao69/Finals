<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button default-href="/tabs/tasks"></ion-back-button>
        </ion-buttons>
        <ion-title>Edit Task</ion-title>
        <ion-buttons slot="end">
          <ion-button @click="handleSubmit" :disabled="!isFormValid" class="save-button">
            <ion-icon :icon="saveOutline" slot="start"></ion-icon>
            Save
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      <div class="edit-task-container">
        <form @submit.prevent="handleSubmit" class="task-form" v-if="taskForm">
          <div class="status-section">
            <ion-badge :color="isCompleted ? 'success' : 'warning'" class="status-badge">
              <ion-icon :icon="isCompleted ? checkmarkCircleOutline : timeOutline" slot="start"></ion-icon>
              {{ isCompleted ? 'Completed' : 'Pending' }}
            </ion-badge>
          </div>

          <div class="form-section">
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

            <ion-item class="form-item" :class="{ 'ion-valid': taskForm.due_date !== '' }">
              <ion-label position="stacked">Due Date <ion-text color="danger">*</ion-text></ion-label>
              <ion-datetime-button datetime="due-datetime"></ion-datetime-button>
              <ion-modal :keep-contents-mounted="true">
                <ion-datetime
                  id="due-datetime"
                  v-model="taskForm.due_date"
                  presentation="date"
                  :min="minDateTime"
                  locale="en-US"
                  :first-day-of-week="0"
                  class="custom-datetime"
                ></ion-datetime>
              </ion-modal>
            </ion-item>

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
  </ion-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
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
  IonInput,
  IonTextarea,
  IonButton,
  IonButtons,
  IonBackButton,
  IonDatetime,
  IonDatetimeButton,
  IonModal,
  IonToggle,
  IonSpinner,
  IonSelect,
  IonSelectOption,
  toastController,
  IonBadge,
  IonIcon,
  IonText
} from '@ionic/vue';
import {
  saveOutline,
  closeOutline,
  checkmarkCircleOutline,
  timeOutline
} from 'ionicons/icons';

interface TaskForm {
  id: number;
  user_id: number;
  title: string;
  description: string;
  due_date: string;
  completed: number;
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
const taskStore = useTaskStore();
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

// Fetch task data
const fetchTask = async () => {
  try {
    const token = localStorage.getItem('token');
    if (!token) {
      router.push('/login');
      return;
    }

    const taskId = route.params.id;
    const response = await axios.get<TaskResponse>(
      `http://localhost/codes/PROJ/dbConnect/tasks.php?taskId=${taskId}`,
      {
        headers: { 'Authorization': `Bearer ${token}` }
      }
    );

    if (response.data.success && response.data.task) {
      const task = response.data.task;
      const completed = parseInt(task.completed, 10);
      if (isNaN(completed)) {
        throw new Error('Invalid completed status');
      }
      taskForm.value = {
        id: task.id,
        user_id: task.user_id,
        title: task.title,
        description: task.description,
        due_date: task.due_date.split('T')[0],
        completed,
        created_at: task.created_at,
        updated_at: task.updated_at
      };
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

const handleSubmit = async () => {
  if (!taskForm.value) return;

  try {
    const token = localStorage.getItem('token');
    if (!token) {
      router.push('/login');
      return;
    }

    const response = await axios.post(
      'http://localhost/codes/PROJ/dbConnect/tasks.php',
      {
        id: taskForm.value.id,
        user_id: taskForm.value.user_id,
        title: taskForm.value.title.trim(),
        description: taskForm.value.description.trim(),
        due_date: taskForm.value.due_date,
        completed: taskForm.value.completed
      },
      {
        headers: { 
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      }
    );

    if (response.data.success) {
      // Update the task data in the parent component
      const updatedTask = {
        id: taskForm.value.id,
        user_id: taskForm.value.user_id,
        title: taskForm.value.title.trim(),
        description: taskForm.value.description.trim(),
        due_date: taskForm.value.due_date,
        completed: taskForm.value.completed,
        created_at: taskForm.value.created_at || '',
        updated_at: new Date().toISOString()
      };
      
      // Call the global update function
      if (window.updateTaskData) {
        window.updateTaskData(updatedTask);
      }

      const toast = await toastController.create({
        message: 'Task updated successfully',
        duration: 2000,
        color: 'success'
      });
      await toast.present();
      
      // Close any open modals before navigation
      const modal = document.querySelector('ion-modal');
      if (modal) {
        await modal.dismiss();
      }

      // Remove focus from any focused elements
      if (document.activeElement instanceof HTMLElement) {
        document.activeElement.blur();
      }
      
      // Navigate back to task details page
      router.replace(`/tabs/tasks/view/${taskForm.value.id}`);
    } else {
      throw new Error(response.data.message || 'Failed to update task');
    }
  } catch (error: any) {
    console.error('Update error:', error);
    const errorMessage = error.response?.data?.message || error.message || 'Failed to update task';
    const toast = await toastController.create({
      message: errorMessage,
      duration: 3000,
      color: 'danger',
      position: 'top'
    });
    await toast.present();
  }
};

// Fetch task data on component mount
onMounted(() => {
  fetchTask();
});

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
});
</script>

<style scoped>
.edit-task-container {
  max-width: 600px;
  margin: 1rem auto;
  padding: 1.5rem;
  background: var(--ion-color-light);
  border-radius: 16px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
}

.task-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.status-section {
  display: flex;
  justify-content: center;
  margin-bottom: 1rem;
}

.status-badge {
  padding: 0.75rem 1.5rem;
  font-size: 0.9rem;
  font-weight: 500;
  border-radius: 20px;
  letter-spacing: 0.5px;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.status-badge ion-icon {
  font-size: 1.1rem;
}

.form-section {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.form-item {
  --background: var(--ion-color-light);
  --border-radius: 12px;
  --padding-start: 16px;
  --padding-end: 16px;
  margin: 0;
  border: 1px solid var(--ion-color-medium-shade);
  transition: all 0.2s ease;
}

.form-item:hover {
  border-color: var(--ion-color-primary);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.form-item.ion-valid {
  --border-color: var(--ion-color-medium-shade);
  --highlight-color: transparent;
  --highlight-color-focused: transparent;
}

.form-item.ion-valid:hover {
  border-color: var(--ion-color-primary);
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
  --background: var(--ion-color-light);
  border-radius: 8px;
  font-size: 1rem;
  --color: var(--ion-color-dark);
}

.custom-textarea {
  min-height: 120px;
  line-height: 1.5;
}

.toggle-item {
  --padding-start: 16px;
  --padding-end: 16px;
  margin-top: 0.5rem;
  border: none;
  background: transparent;
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
  margin-top: 2rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  padding: 0 0.5rem;
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

.save-button {
  --padding-start: 12px;
  --padding-end: 12px;
  font-weight: 500;
  font-size: 0.9rem;
}

.save-button ion-icon {
  margin-right: 4px;
}

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
    margin: 2rem auto;
    padding: 2rem;
  }

  .form-item {
    --padding-start: 20px;
    --padding-end: 20px;
  }
}

/* Custom datetime styling */
:deep(.custom-datetime) {
  --background: var(--ion-color-light);
  --border-radius: 16px;
  --box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
}

:deep(.custom-datetime ion-datetime) {
  --background: var(--ion-color-light);
  --background-rgb: var(--ion-color-light-rgb);
  --border-radius: 16px;
  --box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
  font-size: 1rem;
}

:deep(.custom-datetime ion-datetime-button) {
  --padding-start: 12px;
  --padding-end: 12px;
  font-size: 0.95rem;
  color: var(--ion-color-dark);
}
</style>
