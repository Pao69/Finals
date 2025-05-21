<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button default-href="/tabs/tasks"></ion-back-button>
        </ion-buttons>
        <ion-title>Add Task</ion-title>
      </ion-toolbar>
    </ion-header>

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
  </ion-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
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
  IonNote,
  IonText,
  toastController
} from '@ionic/vue';
import { addOutline } from 'ionicons/icons';

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

const handleSubmit = async () => {
  if (!validateForm()) return;

  try {
    const token = localStorage.getItem('token');
    if (!token) {
      router.push('/login');
      return;
    }
    
    // Format the date to YYYY-MM-DD as required by the backend
    const formattedDate = new Date(taskForm.value.due_date)
      .toISOString()
      .split('T')[0];

    // Prepare the request data
    const requestData = {
      title: taskForm.value.title.trim(),
      description: taskForm.value.description.trim(),
      due_date: formattedDate,
      completed: taskForm.value.completed ? 1 : 0
    };

    console.log('Sending request with data:', requestData); // Debug log

    const response = await axios.post(
      'http://localhost/codes/PROJ/dbConnect/tasks.php',
      requestData,
      {
        headers: { 
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      }
    );

    console.log('Response:', response.data); // Debug log

    if (response.data.success) {
      const toast = await toastController.create({
        message: 'Task added successfully',
        duration: 2000,
        color: 'success'
      });
      await toast.present();
      router.push('/tabs/tasks');
    } else {
      throw new Error(response.data.message || 'Failed to add task');
    }
  } catch (error: any) {
    let errorMessage = error.response?.data?.message || error.message || 'Failed to add task';
    console.error('Error details:', error.response || error); // Debug log
    
    if (error.response?.status === 401) {
      localStorage.removeItem('token');
      router.push('/login');
      errorMessage = 'Session expired. Please login again.';
    }
    
    errors.value.general = errorMessage;
    
    const toast = await toastController.create({
      message: errorMessage,
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
