<template>
  <page-layout title="Admin Dashboard">
    <ion-content class="ion-padding">
      <!-- Segment for switching between users, tasks, and resources -->
      <ion-segment v-model="selectedTab" @ionChange="handleTabChange">
        <ion-segment-button value="users">
          <ion-label>Users</ion-label>
        </ion-segment-button>
        <ion-segment-button value="tasks">
          <ion-label>Tasks</ion-label>
        </ion-segment-button>
        <ion-segment-button value="resources">
          <ion-label>Resources</ion-label>
        </ion-segment-button>
      </ion-segment>

      <!-- Search Bar -->
      <ion-searchbar
        v-model="searchQuery"
        placeholder="Search..."
        @ionInput="handleSearch">
      </ion-searchbar>

      <!-- Users Tab -->
      <div v-if="selectedTab === 'users'" class="tab-content">
        <ion-list>
          <ion-item v-for="user in filteredUsers" :key="user.id" class="list-item">
            <ion-label>
              <h2>{{ user.username }}</h2>
              <p>{{ user.email }}</p>
              <p class="metadata">
                Role: {{ user.role || 'user' }} | 
                Last Login: {{ formatDate(user.last_login) }}
              </p>
            </ion-label>
            <ion-select 
              v-model="user.role" 
              interface="popover"
              @ionChange="updateUserRole(user.id, $event.detail.value)"
              :disabled="user.id === currentUser.id">
              <ion-select-option value="user">User</ion-select-option>
              <ion-select-option value="admin">Admin</ion-select-option>
            </ion-select>
            <ion-button 
              fill="clear" 
              color="danger"
              @click="confirmDeleteUser(user)"
              :disabled="user.id === currentUser.id">
              <ion-icon :icon="trashOutline" slot="icon-only"></ion-icon>
            </ion-button>
          </ion-item>
        </ion-list>
      </div>

      <!-- Tasks Tab -->
      <div v-if="selectedTab === 'tasks'" class="tab-content">
        <ion-list>
          <ion-item v-for="task in filteredTasks" :key="task.id" class="list-item">
            <ion-label>
              <h2>{{ task.title }}</h2>
              <p>{{ task.description }}</p>
              <p class="metadata">
                Assigned to: {{ task.assigned_to }} | 
                Due: {{ formatDate(task.due_date) }} |
                Status: {{ task.completed ? 'Completed' : 'Pending' }}
              </p>
            </ion-label>
            <ion-button 
              fill="clear" 
              color="danger"
              @click="confirmDeleteTask(task)">
              <ion-icon :icon="trashOutline" slot="icon-only"></ion-icon>
            </ion-button>
          </ion-item>
        </ion-list>
      </div>

      <!-- Resources Tab -->
      <div v-if="selectedTab === 'resources'" class="tab-content">
        <ion-list>
          <ion-item v-for="resource in filteredResources" :key="resource.id" class="list-item">
            <ion-thumbnail slot="start">
              <img 
                v-if="isImage(resource.file_type)" 
                :src="getResourceUrl(resource.filename)" 
                alt="Resource thumbnail"
              />
              <ion-icon 
                v-else 
                :icon="documentOutline" 
                size="large">
              </ion-icon>
            </ion-thumbnail>
            <ion-label>
              <h2>{{ resource.original_filename }}</h2>
              <p>{{ resource.description }}</p>
              <p class="metadata">
                Uploaded by: {{ resource.uploaded_by }} | 
                Size: {{ formatFileSize(resource.file_size) }} |
                Date: {{ formatDate(resource.upload_date) }}
              </p>
            </ion-label>
            <ion-button 
              fill="clear" 
              color="danger"
              @click="confirmDeleteResource(resource)">
              <ion-icon :icon="trashOutline" slot="icon-only"></ion-icon>
            </ion-button>
          </ion-item>
        </ion-list>
      </div>
    </ion-content>
  </page-layout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { 
  IonContent, IonSegment, IonSegmentButton, IonLabel,
  IonSearchbar, IonList, IonItem, IonButton, IonIcon,
  IonSelect, IonSelectOption, IonThumbnail,
  alertController, toastController
} from '@ionic/vue';
import { 
  trashOutline, 
  documentOutline 
} from 'ionicons/icons';
import PageLayout from '@/components/PageLayout.vue';
import api from '@/utils/api';

// Interfaces
interface User {
  id: number;
  username: string;
  email: string;
  phone?: string;
  role: string;
  last_login: string;
}

interface Task {
  id: number;
  title: string;
  description: string;
  due_date: string;
  completed: number;
  assigned_to: string;
}

interface Resource {
  id: number;
  original_filename: string;
  filename: string;
  file_type: string;
  file_size: number;
  description: string;
  upload_date: string;
  uploaded_by: string;
}

// State
const selectedTab = ref('users');
const searchQuery = ref('');
const users = ref<User[]>([]);
const tasks = ref<Task[]>([]);
const resources = ref<Resource[]>([]);
const currentUser = ref<User>(JSON.parse(localStorage.getItem('user') || '{}'));

// Computed properties for filtered data
const filteredUsers = computed(() => {
  if (!searchQuery.value) return users.value;
  const query = searchQuery.value.toLowerCase();
  return users.value.filter(user => 
    user.username.toLowerCase().includes(query) ||
    user.email.toLowerCase().includes(query)
  );
});

const filteredTasks = computed(() => {
  if (!searchQuery.value) return tasks.value;
  const query = searchQuery.value.toLowerCase();
  return tasks.value.filter(task => 
    task.title.toLowerCase().includes(query) ||
    task.description.toLowerCase().includes(query) ||
    task.assigned_to.toLowerCase().includes(query)
  );
});

const filteredResources = computed(() => {
  if (!searchQuery.value) return resources.value;
  const query = searchQuery.value.toLowerCase();
  return resources.value.filter(resource => 
    resource.original_filename.toLowerCase().includes(query) ||
    resource.description.toLowerCase().includes(query) ||
    resource.uploaded_by.toLowerCase().includes(query)
  );
});

// Event handlers
const handleTabChange = () => {
  fetchData();
};

const handleSearch = (event: CustomEvent) => {
  searchQuery.value = event.detail.value || '';
};

// Data fetching
const fetchData = async () => {
  try {
    const response = await api.get(`/admin.php?type=${selectedTab.value}`);
    if (response.data.success) {
      switch (selectedTab.value) {
        case 'users':
          users.value = response.data.users;
          break;
        case 'tasks':
          tasks.value = response.data.tasks;
          break;
        case 'resources':
          resources.value = response.data.resources;
          break;
      }
    }
  } catch (error: any) {
    const toast = await toastController.create({
      message: error.response?.data?.message || 'Failed to fetch data',
      duration: 3000,
      color: 'danger'
    });
    await toast.present();
  }
};

// User management
const updateUserRole = async (userId: number, newRole: string) => {
  try {
    const response = await api.post('/admin.php', {
      action: 'update_user_role',
      user_id: userId,
      role: newRole
    });

    if (response.data.success) {
      const toast = await toastController.create({
        message: 'User role updated successfully',
        duration: 2000,
        color: 'success'
      });
      await toast.present();
    }
  } catch (error: any) {
    const toast = await toastController.create({
      message: error.response?.data?.message || 'Failed to update user role',
      duration: 3000,
      color: 'danger'
    });
    await toast.present();
  }
};

const confirmDeleteUser = async (user: any) => {
  const alert = await alertController.create({
    header: 'Confirm Delete',
    message: `Are you sure you want to delete user "${user.username}"?`,
    buttons: [
      {
        text: 'Cancel',
        role: 'cancel'
      },
      {
        text: 'Delete',
        role: 'destructive',
        handler: () => deleteUser(user.id)
      }
    ]
  });
  await alert.present();
};

const deleteUser = async (userId: number) => {
  try {
    const response = await api.post('/admin.php', {
      action: 'delete_user',
      user_id: userId
    });

    if (response.data.success) {
      users.value = users.value.filter(user => user.id !== userId);
      const toast = await toastController.create({
        message: 'User deleted successfully',
        duration: 2000,
        color: 'success'
      });
      await toast.present();
    }
  } catch (error: any) {
    const toast = await toastController.create({
      message: error.response?.data?.message || 'Failed to delete user',
      duration: 3000,
      color: 'danger'
    });
    await toast.present();
  }
};

// Task management
const confirmDeleteTask = async (task: any) => {
  const alert = await alertController.create({
    header: 'Confirm Delete',
    message: `Are you sure you want to delete task "${task.title}"?`,
    buttons: [
      {
        text: 'Cancel',
        role: 'cancel'
      },
      {
        text: 'Delete',
        role: 'destructive',
        handler: () => deleteTask(task.id)
      }
    ]
  });
  await alert.present();
};

const deleteTask = async (taskId: number) => {
  try {
    const response = await api.post('/admin.php', {
      action: 'delete_task',
      task_id: taskId
    });

    if (response.data.success) {
      tasks.value = tasks.value.filter(task => task.id !== taskId);
      const toast = await toastController.create({
        message: 'Task deleted successfully',
        duration: 2000,
        color: 'success'
      });
      await toast.present();
    }
  } catch (error: any) {
    const toast = await toastController.create({
      message: error.response?.data?.message || 'Failed to delete task',
      duration: 3000,
      color: 'danger'
    });
    await toast.present();
  }
};

// Resource management
const confirmDeleteResource = async (resource: any) => {
  const alert = await alertController.create({
    header: 'Confirm Delete',
    message: `Are you sure you want to delete resource "${resource.original_filename}"?`,
    buttons: [
      {
        text: 'Cancel',
        role: 'cancel'
      },
      {
        text: 'Delete',
        role: 'destructive',
        handler: () => deleteResource(resource.id)
      }
    ]
  });
  await alert.present();
};

const deleteResource = async (resourceId: number) => {
  try {
    const response = await api.post('/admin.php', {
      action: 'delete_resource',
      resource_id: resourceId
    });

    if (response.data.success) {
      resources.value = resources.value.filter(resource => resource.id !== resourceId);
      const toast = await toastController.create({
        message: 'Resource deleted successfully',
        duration: 2000,
        color: 'success'
      });
      await toast.present();
    }
  } catch (error: any) {
    const toast = await toastController.create({
      message: error.response?.data?.message || 'Failed to delete resource',
      duration: 3000,
      color: 'danger'
    });
    await toast.present();
  }
};

// Utility functions
const formatDate = (dateString: string): string => {
  if (!dateString) return 'Never';
  const date = new Date(dateString);
  return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
};

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

const getResourceUrl = (filename: string): string => {
  return `http://localhost/codes/PROJ/uploads/${filename}`;
};

// Initialize
onMounted(() => {
  fetchData();
});
</script>

<style scoped>
ion-segment {
  margin-bottom: 16px;
  --background: var(--ion-color-light);
  border-radius: 12px;
  padding: 4px;
}

ion-segment-button {
  --background-checked: var(--ion-color-primary);
  --color-checked: var(--ion-color-primary-contrast);
  --indicator-color: transparent;
  border-radius: 8px;
}

ion-searchbar {
  --background: var(--ion-color-light);
  --border-radius: 12px;
  --box-shadow: none;
  margin-bottom: 16px;
}

.tab-content {
  margin-top: 16px;
}

.list-item {
  --padding-start: 16px;
  --padding-end: 16px;
  --inner-padding-end: 0;
  margin-bottom: 8px;
  border-radius: 12px;
  --background: var(--ion-color-light);
}

ion-label h2 {
  font-weight: 600;
  font-size: 1.1rem;
  margin-bottom: 4px;
}

.metadata {
  font-size: 0.85rem;
  color: var(--ion-color-medium);
  margin-top: 4px;
}

ion-thumbnail {
  --size: 60px;
  margin-right: 16px;
}

ion-select {
  max-width: 120px;
  margin-right: 8px;
}

@media (min-width: 768px) {
  .tab-content {
    max-width: 800px;
    margin: 16px auto;
  }

  ion-segment,
  ion-searchbar {
    max-width: 800px;
    margin-left: auto;
    margin-right: auto;
  }
}
</style> 