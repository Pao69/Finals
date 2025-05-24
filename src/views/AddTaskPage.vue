<template>
  <page-layout title="Add Task" :showBackButton="true" backHref="/tabs/tasks">
    <ion-content class="ion-padding">
      <form @submit.prevent="handleSubmit" class="task-form">
        <div class="form-section">
          <h2>Task Details</h2>
          
          <ion-item>
            <ion-label position="stacked">Title <ion-text color="danger">*</ion-text></ion-label>
            <ion-input
              v-model="taskForm.title"
              type="text"
              placeholder="Enter task title"
              required
              class="custom-input">
            </ion-input>
            <ion-note v-if="errors.title" color="danger">{{ errors.title }}</ion-note>
          </ion-item>

          <ion-item>
            <ion-label position="stacked">Description</ion-label>
            <ion-textarea
              v-model="taskForm.description"
              placeholder="Enter task description"
              :rows="4"
              class="custom-input">
            </ion-textarea>
          </ion-item>
        </div>

        <div class="form-section">
          <h2>Schedule</h2>
          
          <ion-item class="form-item" :class="{ 'ion-valid': taskForm.due_date !== '' }">
            <ion-label position="stacked">Due Date <ion-text color="danger">*</ion-text></ion-label>
            <ion-datetime
              v-model="taskForm.due_date"
              presentation="date-time"
              :min="minDateTime"
              required
              :buttons="true"
              button-text="Done"
              cancel-text="Cancel"
              class="custom-datetime">
            </ion-datetime>
          </ion-item>
        </div>

        <div class="form-section">
          <h2>Status</h2>
          
          <ion-item>
            <ion-label>Mark as completed</ion-label>
            <ion-toggle v-model="taskForm.completed" slot="end"></ion-toggle>
          </ion-item>
        </div>

        <div class="submit-section">
          <ion-button expand="block" type="submit" :disabled="!isFormValid" class="submit-button">
            <ion-icon :icon="addOutline" slot="start"></ion-icon>
            Add Task
          </ion-button>
        </div>
      </form>
    </ion-content>
  </page-layout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import {
  IonContent,
  IonItem,
  IonLabel,
  IonInput,
  IonTextarea,
  IonButton,
  IonDatetime,
  IonNote,
  IonText,
  toastController
} from '@ionic/vue';
import { addOutline } from 'ionicons/icons';
import PageLayout from '@/components/PageLayout.vue';

interface TaskForm {
  title: string;
  description: string;
  due_date: string;
  completed: number | boolean;
}

interface Errors {
  title?: string;
  due_date?: string;
  general?: string;
}

const router = useRouter();
const errors = ref<Errors>({});

const taskForm = ref<TaskForm>({
  title: '',
  description: '',
  due_date: new Date().toISOString().split('T')[0],
  completed: false
});

// Compute minimum date time (current time)
const minDateTime = new Date().toISOString().split('T')[0];

// Form validation
const isFormValid = computed(() => {
  return taskForm.value.title.trim() !== '' && taskForm.value.due_date !== '';
});

const validateForm = (): boolean => {
  errors.value = {};
  let isValid = true;

  if (!taskForm.value.title.trim()) {
    errors.value.title = 'Title is required';
    isValid = false;
  }

  if (!taskForm.value.due_date) {
    errors.value.due_date = 'Due date is required';
    isValid = false;
  }

  return isValid;
};

// Add token validation on component mount
onMounted(() => {
  validateToken();
});

const validateToken = async () => {
  const token = localStorage.getItem('token');
  if (!token) {
    const toast = await toastController.create({
      message: 'Please login to add tasks',
      duration: 2000,
      color: 'warning'
    });
    await toast.present();
    router.push('/login');
    return;
  }
};

const handleSubmit = async (event: Event) => {
  event.preventDefault();
  
  if (!validateForm()) return;

  try {
    const token = localStorage.getItem('token');
    const response = await axios.post(
      'http://localhost/codes/PROJ/dbConnect/tasks.php',
      taskForm.value,
      {
        headers: { 'Authorization': `Bearer ${token}` }
      }
    );

    if (response.data.success) {
      const toast = await toastController.create({
        message: 'Task created successfully',
        duration: 2000,
        color: 'success'
      });
      await toast.present();

      // Navigate back to tasks list
      router.replace('/tabs/tasks');
    }
  } catch (error: any) {
    console.error('Error creating task:', error);
    const toast = await toastController.create({
      message: error.response?.data?.message || 'Failed to create task',
      duration: 2000,
      color: 'danger'
    });
    await toast.present();
  }
};

const modal = ref(null);

const confirmDate = async () => {
  if (modal.value) {
    await (modal.value as any).$el.dismiss();
  }
};
</script>

<style scoped>
.task-form {
  max-width: 600px;
  margin: 0 auto;
  padding-bottom: 2rem;
}

.form-section {
  margin-bottom: 2rem;
}

.form-section h2 {
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--ion-color-dark);
  margin-bottom: 1rem;
  padding-left: 0.5rem;
}

ion-item {
  --padding-start: 0;
  margin-bottom: 1rem;
  border-radius: 8px;
  --background: var(--ion-color-light);
}

.custom-input {
  --padding-start: 1rem;
  --padding-end: 1rem;
  --padding-top: 0.5rem;
  --padding-bottom: 0.5rem;
  margin-top: 0.5rem;
}

ion-datetime {
  width: 100%;
}

.custom-datetime {
  width: 100%;
}

.submit-section {
  margin-top: 2rem;
  padding: 0 1rem;
}

.submit-button {
  --border-radius: 8px;
  font-weight: 600;
  height: 48px;
}

ion-note {
  font-size: 0.8rem;
  padding: 0.5rem 0;
}

.date-item {
  --background: var(--ion-color-light);
  border-radius: 8px;
  margin-bottom: 1rem;
}

.date-button-wrapper {
  width: 100%;
  margin-top: 8px;
}

.date-button-wrapper ion-datetime-button::part(native) {
  background: var(--ion-color-light);
  border-radius: 8px;
  padding: 12px;
  color: var(--ion-color-dark);
}

.custom-datetime {
  width: 100%;
}

:deep(.custom-datetime) {
  --background: var(--ion-color-light);
  --border-radius: 12px;
  --padding-start: 16px;
  --padding-end: 16px;
  --padding-top: 16px;
  --padding-bottom: 16px;
}

:deep(.custom-datetime .datetime-buttons) {
  display: flex;
  justify-content: space-between;
  padding: 8px 16px;
  border-top: 1px solid var(--ion-color-light-shade);
}

:deep(.custom-datetime .datetime-buttons button) {
  font-weight: 500;
  padding: 8px 16px;
  border-radius: 8px;
}

:deep(.custom-datetime .datetime-buttons button:first-child) {
  color: var(--ion-color-medium);
}

:deep(.custom-datetime .datetime-buttons button:last-child) {
  color: var(--ion-color-primary);
}

/* Make the date picker more prominent */
ion-datetime::part(calendar-day) {
  color: var(--ion-color-dark);
}

ion-datetime::part(calendar-day selected) {
  background: var(--ion-color-primary);
  color: white;
}

ion-datetime::part(calendar-day today) {
  border-color: var(--ion-color-primary);
}
</style>
