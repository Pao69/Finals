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
      <form @submit.prevent="handleSubmit" class="task-form" v-if="taskForm">
        <ion-item>
          <ion-label position="stacked">Title <ion-text color="danger">*</ion-text></ion-label>
          <ion-input
            v-model="taskForm.title"
            type="text"
            placeholder="Enter task title"
            required
          ></ion-input>
        </ion-item>

        <ion-item>
          <ion-label position="stacked">Description</ion-label>
          <ion-textarea
            v-model="taskForm.description"
            placeholder="Enter task description"
            :rows="4"
          ></ion-textarea>
        </ion-item>

        <ion-item>
          <ion-label position="stacked">Due Date <ion-text color="danger">*</ion-text></ion-label>
          <ion-datetime-button datetime="due-datetime"></ion-datetime-button>
          <ion-modal :keep-contents-mounted="true">
            <ion-datetime
              id="due-datetime"
              v-model="taskForm.due_date"
              presentation="date"
              :min="minDateTime"
              locale="en-US"
              first-day-of-week="0"
            ></ion-datetime>
          </ion-modal>
        </ion-item>

        <ion-item>
          <ion-label>Completed</ion-label>
          <ion-toggle
            :model-value="isCompleted"
            @ion-change="e => handleCompletedChange(e.detail.checked)"
          ></ion-toggle>
        </ion-item>

        <div class="ion-padding-top">
          <ion-button expand="block" type="submit" :disabled="!isFormValid">
            Update Task
          </ion-button>
        </div>
      </form>

      <div v-else class="ion-padding ion-text-center">
        <ion-spinner></ion-spinner>
        <p>Loading task...</p>
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
  toastController
} from '@ionic/vue';

interface TaskForm {
  id: number;
  title: string;
  description: string;
  due_date: string;
  completed: number;
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
    const taskId = route.params.id;
    const response = await axios.get<{success: boolean; task: TaskForm}>(
      `http://localhost/codes/PROJ/dbConnect/tasks.php?taskId=${taskId}`,
      {
        headers: { 'Authorization': `Bearer ${token}` }
      }
    );

    if (response.data.success && response.data.task) {
      taskForm.value = {
        ...response.data.task,
        due_date: response.data.task.due_date.split('T')[0]
      };
    } else {
      throw new Error('Task not found');
    }
  } catch (error: any) {
    const toast = await toastController.create({
      message: error.message || 'Failed to fetch task',
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
    const response = await axios.put(
      'http://localhost/codes/PROJ/dbConnect/tasks.php',
      {
        taskId: taskForm.value.id,
        title: taskForm.value.title,
        description: taskForm.value.description,
        due_date: taskForm.value.due_date,
        completed: taskForm.value.completed
      },
      {
        headers: { 'Authorization': `Bearer ${token}` }
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
.task-form {
  max-width: 600px;
  margin: 0 auto;
}

ion-item {
  --padding-start: 0;
  margin-bottom: 1rem;
}

ion-datetime {
  width: 100%;
}
</style>
