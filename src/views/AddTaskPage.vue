<template>
  <page-layout title="Add Task" :showBackButton="true" backHref="/tabs/tasks">
    <ion-content class="ion-padding">
      <form @submit.prevent="handleSubmit" class="task-form">
        <div class="form-section">
          <div class="section-header">
            <h2>Task Details</h2>
          </div>
          
          <ion-item class="form-item">
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

          <ion-item class="form-item">
            <ion-label position="stacked">Description</ion-label>
            <ion-textarea
              v-model="taskForm.description"
              placeholder="Enter task description"
              :rows="4"
              class="custom-textarea">
            </ion-textarea>
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
              class="custom-datetime">
          </ion-item>
        </div>

        <div class="form-section">
          <div class="section-header">
            <h2>Status</h2>
          </div>
          
          <ion-item class="form-item toggle-item">
            <ion-label>Mark as completed</ion-label>
            <ion-toggle v-model="taskForm.completed" slot="end" class="custom-toggle"></ion-toggle>
          </ion-item>
        </div>

        <div class="form-actions">
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
  const token = localStorage.getItem('token') || sessionStorage.getItem('token');
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
    const token = localStorage.getItem('token') || sessionStorage.getItem('token');
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
  margin: 0.5rem auto;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 2rem;
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

ion-note {
  font-size: 0.8rem;
  padding: 0.5rem 0;
  color: var(--ion-color-danger);
}

/* Responsive adjustments */
@media (min-width: 768px) {
  .task-form {
    margin: 1.5rem auto;
    padding: 1.5rem;
  }

  .form-section {
    padding: 2rem;
  }
}
</style>
