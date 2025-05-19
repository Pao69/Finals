<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button default-href="/tabs/tasks"></ion-back-button>
        </ion-buttons>
        <ion-title>Edit Task</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      <div class="edit-task-container">
        <form @submit.prevent="handleSubmit" class="task-form" v-if="taskForm">
          <h2 class="form-title">Edit Task</h2>
          <div class="status-row">
            <ion-badge :color="isCompleted ? 'success' : 'medium'">
              {{ isCompleted ? 'Completed' : 'Pending' }}
            </ion-badge>
          </div>
          <ion-item class="form-item">
            <ion-label position="stacked">Title <ion-text color="danger">*</ion-text></ion-label>
            <ion-input
              v-model="taskForm.title"
              type="text"
              placeholder="Enter task title"
              required
            ></ion-input>
          </ion-item>
          <ion-item class="form-item">
            <ion-label position="stacked">Description</ion-label>
            <ion-textarea
              v-model="taskForm.description"
              placeholder="Enter task description"
              :rows="4"
            ></ion-textarea>
          </ion-item>
          <ion-item class="form-item">
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
              ></ion-datetime>
            </ion-modal>
          </ion-item>
          <ion-item class="form-item">
            <ion-label>Completed</ion-label>
            <ion-toggle
              :model-value="isCompleted"
              @ion-change="e => handleCompletedChange(e.detail.checked)"
            ></ion-toggle>
          </ion-item>
          <div class="ion-padding-top">
            <ion-button expand="block" size="large" type="submit" :disabled="!isFormValid">
              Update Task
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
import { ref, computed, onMounted } from 'vue';
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
  IonBadge
} from '@ionic/vue';

interface TaskForm {
  id: number;
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

    const response = await axios.put(
      'http://localhost/codes/PROJ/dbConnect/tasks.php',
      {
        taskId: taskForm.value.id,
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
      const toast = await toastController.create({
        message: 'Task updated successfully',
        duration: 2000,
        color: 'success'
      });
      await toast.present();
      router.back();
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

// Fetch task data on component mount
onMounted(() => {
  fetchTask();
});
</script>

<style scoped>
.edit-task-container {
  max-width: 420px;
  margin: 2.5rem auto 0 auto;
  background: var(--ion-color-light);
  border-radius: 18px;
  box-shadow: 0 4px 24px rgba(0,0,0,0.08);
  padding: 2rem 1.5rem 1.5rem 1.5rem;
  display: flex;
  flex-direction: column;
  align-items: stretch;
}
@media (max-width: 600px) {
  .edit-task-container {
    margin: 1rem 0 0 0;
    border-radius: 0;
    box-shadow: none;
    padding: 1.2rem 0.5rem 1.5rem 0.5rem;
  }
}
.form-title {
  text-align: center;
  font-size: 1.4rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
  color: var(--ion-color-primary);
}
.status-row {
  display: flex;
  justify-content: center;
  margin-bottom: 1.2rem;
}
ion-badge {
  font-size: 1rem;
  padding: 0.5em 1.2em;
  border-radius: 12px;
}
.form-item {
  --padding-start: 0;
  margin-bottom: 1.2rem;
  border-radius: 12px;
  background: var(--ion-color-step-50, #f8f9fa);
  box-shadow: 0 1px 4px rgba(0,0,0,0.03);
  text-align: left;
}
ion-label {
  font-size: 1.05rem;
  font-weight: 500;
  margin-bottom: 0.2rem;
  text-align: left;
  width: 100%;
  display: block;
}
ion-input, ion-textarea {
  font-size: 1.1rem;
  text-align: left;
}
ion-textarea {
  min-height: 80px;
}
ion-button {
  font-size: 1.1rem;
  height: 48px;
  --border-radius: 12px;
  --box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 200px;
  color: var(--ion-color-medium);
  text-align: center;
}
</style>
