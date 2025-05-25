<template>
  <page-layout title="Resources">
    <template #additional-toolbar>
      <ion-toolbar>
        <ion-searchbar
          v-model="searchQuery"
          placeholder="Search resources..."
          @ionInput="handleSearch">
        </ion-searchbar>
      </ion-toolbar>
    </template>

    <ion-content :fullscreen="true" class="ion-padding">
      <!-- Upload Section -->
      <ion-card class="upload-card">
        <ion-card-header>
          <ion-card-title>Upload New Resource</ion-card-title>
        </ion-card-header>
        <ion-card-content>
          <form @submit.prevent="handleUpload" class="upload-form">
            <ion-item>
              <ion-label position="stacked">Description</ion-label>
              <ion-input v-model="uploadForm.description" placeholder="Enter resource description"></ion-input>
            </ion-item>
            
            <ion-item>
              <ion-label position="stacked">Link to Task (Optional)</ion-label>
              <ion-select v-model="uploadForm.task_id" placeholder="Select a task">
                <ion-select-option v-for="task in tasks" :key="task.id" :value="task.id">
                  {{ task.title }}
                </ion-select-option>
              </ion-select>
            </ion-item>

            <ion-item>
              <ion-label position="stacked">File</ion-label>
              <input type="file" ref="fileInput" @change="handleFileSelect" class="file-input" />
              <ion-note>Supported formats: Images, PDF, Word documents, text files</ion-note>
            </ion-item>

            <div class="upload-actions">
              <ion-button type="submit" expand="block" :disabled="!selectedFile">
                <ion-icon :icon="cloudUploadOutline" slot="start"></ion-icon>
                Upload Resource
              </ion-button>
            </div>
          </form>
        </ion-card-content>
      </ion-card>

      <!-- Resources List -->
      <ion-list v-if="filteredResources.length > 0">
        <ion-item-group>
          <ion-item v-for="resource in filteredResources" :key="resource.id" class="resource-item">
            <ion-thumbnail slot="start" class="resource-thumbnail">
              <img v-if="isImage(resource.file_type)" :src="getResourceUrl(resource.filename)" alt="Resource thumbnail" />
              <ion-icon v-else :icon="getFileIcon(resource.file_type)" size="large"></ion-icon>
            </ion-thumbnail>
            
            <ion-label class="ion-text-wrap">
              <h2>{{ resource.original_filename }}</h2>
              <p>{{ resource.description }}</p>
              <p class="resource-meta">
                <ion-text color="medium">
                  {{ formatFileSize(resource.file_size) }} • 
                  {{ formatDate(resource.upload_date) }}
                </ion-text>
              </p>
              <ion-chip 
                v-if="resource.task_title && resource.task_id" 
                color="primary" 
                outline 
                @click="viewTask(resource.task_id)"
              >
                <ion-icon :icon="linkOutline"></ion-icon>
                <ion-label>{{ resource.task_title }}</ion-label>
              </ion-chip>
            </ion-label>

            <ion-buttons slot="end">
              <ion-button @click="downloadResource(resource)">
                <ion-icon :icon="downloadOutline" slot="icon-only"></ion-icon>
              </ion-button>
              <ion-button color="danger" @click="confirmDelete(resource)" v-if="canModifyResource(resource)">
                <ion-icon :icon="trashOutline" slot="icon-only"></ion-icon>
              </ion-button>
            </ion-buttons>
          </ion-item>
        </ion-item-group>
      </ion-list>

      <!-- Empty State -->
      <div v-else class="empty-state ion-text-center">
        <ion-icon :icon="documentsOutline" class="empty-icon"></ion-icon>
        <h2>No Resources Found</h2>
        <p>Upload your first resource using the form above</p>
      </div>

      <!-- Delete Confirmation Alert -->
      <ion-alert
        :is-open="showDeleteAlert"
        header="Confirm Delete"
        message="Are you sure you want to delete this resource?"
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
              deleteResource();
            },
          },
        ]">
      </ion-alert>
    </ion-content>
  </page-layout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, onBeforeUnmount } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';
import {
  IonContent, IonList, IonItem, IonLabel, IonButton, IonButtons,
  IonIcon, IonSearchbar, IonCard, IonCardHeader, IonCardTitle,
  IonCardContent, IonInput, IonSelect, IonSelectOption, IonNote,
  IonThumbnail, IonChip, IonAlert, IonToolbar, toastController
} from '@ionic/vue';
import {
  cloudUploadOutline, downloadOutline, trashOutline,
  documentsOutline, documentOutline, imageOutline,
  documentTextOutline, linkOutline
} from 'ionicons/icons';
import PageLayout from '@/components/PageLayout.vue';

interface Resource {
  id: number;
  user_id: number;
  task_id: number | null;
  filename: string;
  original_filename: string;
  file_type: string;
  file_size: number;
  description: string;
  upload_date: string;
  task_title: string | null;
  owner_username: string;
}

interface Task {
  id: number;
  title: string;
}

const router = useRouter();
const route = useRoute();
const resources = ref<Resource[]>([]);
const tasks = ref<Task[]>([]);
const searchQuery = ref('');
const showDeleteAlert = ref(false);
const resourceToDelete = ref<Resource | null>(null);
const selectedFile = ref<File | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);

const uploadForm = ref({
  description: '',
  task_id: null as number | null
});

const currentUser = ref(JSON.parse(localStorage.getItem('user') || '{}'));
const canModifyResource = (resource: Resource) => {
  return resource.user_id === currentUser.value.id || currentUser.value.role === 'admin';
};

// Fetch resources
const fetchResources = async () => {
  try {
    const token = localStorage.getItem('token') || sessionStorage.getItem('token');
    if (!token) {
      router.push('/login');
      return;
    }

    const response = await axios.get('http://localhost/codes/PROJ/dbConnect/resources.php', {
      headers: { 'Authorization': `Bearer ${token}` }
    });

    if (response.data.success) {
      resources.value = response.data.resources || [];
    }
  } catch (error: any) {
    console.error('Error fetching resources:', error);
    const toast = await toastController.create({
      message: 'Failed to fetch resources',
      duration: 2000,
      color: 'danger'
    });
    await toast.present();
  }
};

// Fetch tasks for the select dropdown
const fetchTasks = async () => {
  try {
    const token = localStorage.getItem('token') || sessionStorage.getItem('token');
    const response = await axios.get('http://localhost/codes/PROJ/dbConnect/tasks.php', {
      headers: { 'Authorization': `Bearer ${token}` }
    });

    if (response.data.success) {
      tasks.value = response.data.tasks || [];
    }
  } catch (error) {
    console.error('Error fetching tasks:', error);
  }
};

// Filter resources based on search
const filteredResources = computed(() => {
  if (!searchQuery.value) return resources.value;
  
  const query = searchQuery.value.toLowerCase();
  return resources.value.filter(resource => 
    resource.original_filename.toLowerCase().includes(query) ||
    resource.description.toLowerCase().includes(query) ||
    resource.owner_username.toLowerCase().includes(query) ||
    (resource.task_title && resource.task_title.toLowerCase().includes(query))
  );
});

// Handle file selection
const handleFileSelect = (event: Event) => {
  const input = event.target as HTMLInputElement;
  if (input.files && input.files.length > 0) {
    selectedFile.value = input.files[0];
  }
};

// Handle resource upload
const handleUpload = async () => {
  if (!selectedFile.value) return;

  try {
    const token = localStorage.getItem('token');
    const formData = new FormData();
    formData.append('file', selectedFile.value);
    formData.append('description', uploadForm.value.description);
    if (uploadForm.value.task_id) {
      formData.append('task_id', uploadForm.value.task_id.toString());
    }

    const response = await axios.post(
      'http://localhost/codes/PROJ/dbConnect/resources.php',
      formData,
      {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'multipart/form-data'
        }
      }
    );

    if (response.data.success) {
      const toast = await toastController.create({
        message: 'Resource uploaded successfully',
        duration: 2000,
        color: 'success'
      });
      await toast.present();

      // Reset form
      uploadForm.value.description = '';
      uploadForm.value.task_id = null;
      selectedFile.value = null;
      if (fileInput.value) {
        fileInput.value.value = '';
      }

      // Refresh resources list
      await fetchResources();
    }
  } catch (error: any) {
    console.error('Error uploading resource:', error);
    const toast = await toastController.create({
      message: 'Failed to upload resource',
      duration: 2000,
      color: 'danger'
    });
    await toast.present();
  }
};

// Handle resource deletion
const confirmDelete = (resource: Resource) => {
  resourceToDelete.value = resource;
  showDeleteAlert.value = true;
};

const deleteResource = async () => {
  if (!resourceToDelete.value) return;

  try {
    const token = localStorage.getItem('token');
    const response = await axios.delete('http://localhost/codes/PROJ/dbConnect/resources.php', {
      headers: { 'Authorization': `Bearer ${token}` },
      data: { resource_id: resourceToDelete.value.id }
    });

    if (response.data.success) {
      const toast = await toastController.create({
        message: 'Resource deleted successfully',
        duration: 2000,
        color: 'success'
      });
      await toast.present();
      await fetchResources();
    }
  } catch (error: any) {
    console.error('Error deleting resource:', error);
    const toast = await toastController.create({
      message: 'Failed to delete resource',
      duration: 2000,
      color: 'danger'
    });
    await toast.present();
  }

  resourceToDelete.value = null;
  showDeleteAlert.value = false;
};

// Utility functions
const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const formatDate = (dateString: string): string => {
  const date = new Date(dateString);
  return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
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

// Handle search
const handleSearch = (event: CustomEvent) => {
  searchQuery.value = event.detail.value;
};

// Add this to the script section after the downloadResource function
const viewTask = (taskId: number) => {
  router.push(`/tabs/tasks/view/${taskId}`);
};

// Add watch effect for route changes
watch(
  () => route.fullPath,
  async () => {
    await fetchTasks();
  }
);

// Add onMounted to fetch data when component loads
onMounted(async () => {
  await Promise.all([fetchResources(), fetchTasks()]);
});

// Add global refresh function
window.refreshResourceList = async () => {
  await Promise.all([fetchResources(), fetchTasks()]);
};

// Update global function type declaration
declare global {
  interface Window {
    refreshResourceList?: () => Promise<void>;
  }
}

// Add cleanup on unmount
onBeforeUnmount(() => {
  if (window.refreshResourceList) {
    window.refreshResourceList = undefined;
  }
});
</script>

<style scoped>
.upload-card {
  margin-bottom: 20px;
  border-radius: 16px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  border: 1px solid var(--ion-color-light-shade);
  overflow: hidden;
  background: var(--ion-background-color);
}

ion-card-header {
  padding: 16px;
  border-bottom: 1px solid var(--ion-color-light-shade);
  background: var(--ion-color-light-tint);
}

ion-card-title {
  font-size: 1.2rem;
  font-weight: 600;
  color: var(--ion-color-dark);
}

.upload-form {
  padding: 16px;
}

.upload-actions {
  margin-top: 20px;
}

ion-item {
  --background: var(--ion-color-light);
  --border-radius: 12px;
  margin-bottom: 12px;
  --padding-start: 16px;
  --padding-end: 16px;
  border: 1px solid var(--ion-color-light-shade);
}

ion-input, ion-textarea, ion-select {
  --padding-start: 0;
  --padding-end: 0;
  margin-top: 8px;
}

.file-input {
  margin: 10px 0;
  padding: 8px 0;
}

ion-button {
  --border-radius: 12px;
  height: 48px;
  font-weight: 500;
}

.resource-item {
  --padding-start: 16px;
  --padding-end: 16px;
  margin: 8px 0;
  --background: var(--ion-color-light);
  border-radius: 12px;
  border: 1px solid var(--ion-color-light-shade);
}

.resource-thumbnail {
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--ion-color-light);
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid var(--ion-color-light-shade);
}

.resource-thumbnail img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.resource-thumbnail ion-icon {
  font-size: 32px;
  color: var(--ion-color-medium);
}

ion-label h2 {
  font-size: 1rem;
  font-weight: 600;
  color: var(--ion-color-dark);
  margin-bottom: 4px;
}

ion-label p {
  font-size: 0.9rem;
  color: var(--ion-color-medium);
  margin: 4px 0;
}

.resource-meta {
  font-size: 0.85em;
  margin-top: 4px;
  color: var(--ion-color-medium);
}

ion-chip {
  margin-top: 8px;
  --background: transparent;
  border: 1px solid var(--ion-color-primary);
}

.empty-state {
  padding: 40px 20px;
  text-align: center;
}

.empty-icon {
  font-size: 64px;
  color: var(--ion-color-medium);
  margin-bottom: 16px;
}

.empty-state h2 {
  font-size: 1.2rem;
  color: var(--ion-color-dark);
  margin: 0 0 8px 0;
}

.empty-state p {
  font-size: 0.9rem;
  color: var(--ion-color-medium);
  margin: 0;
}

@media (max-width: 768px) {
  ion-content {
    --padding: 8px;
  }

  .upload-card {
    border-radius: 12px;
    margin-bottom: 16px;
  }

  .resource-item {
    margin: 6px 0;
  }

  ion-button {
    height: 44px;
  }
}

@media (min-width: 768px) {
  ion-content {
    --padding: 24px;
  }

  .upload-card {
    max-width: 800px;
    margin: 0 auto 24px;
  }

  ion-list {
    max-width: 800px;
    margin: 0 auto;
  }

  .resource-item:hover {
    transform: none;
    box-shadow: none;
  }
}
</style> 