<!--
  NOTE: The following comments are for educational/debugging purposes and may not cover all edge cases.
  AdminDashboard.vue - Administrative dashboard with user management, system statistics, and global task oversight.
-->
<template>
  
  <page-layout title="Admin Dashboard" :show-back-button="false">
    <ion-content class="ion-padding">
      <!-- Segment Tabs -->
       <div>
        <ion-segment 
          v-model="selectedTab" 
          @ionChange="handleTabChange" 
          class="custom-segment ion-margin-vertical">
          <ion-segment-button value="users" class="segment-button">
            <ion-icon :icon="peopleOutline" size="small"></ion-icon>
            <ion-label>Users</ion-label>
          </ion-segment-button>
          <ion-segment-button value="tasks" class="segment-button">
            <ion-icon :icon="checkboxOutline" size="small"></ion-icon>
            <ion-label>Tasks</ion-label>
          </ion-segment-button>
          <ion-segment-button value="resources" class="segment-button">
            <ion-icon :icon="folderOutline" size="small"></ion-icon>
            <ion-label>Resources</ion-label>
          </ion-segment-button>
        </ion-segment>

        <ion-searchbar
          v-model="searchQuery"
          placeholder="Search..."
          @ionInput="handleSearch"
          class="custom-searchbar">
        </ion-searchbar>
      </div>

      <!-- Users Tab -->
      <div v-if="selectedTab === 'users'" class="ion-padding-bottom fade-in">
        <ion-list class="custom-list">
          <ion-item-group>
            <ion-item v-for="user in filteredUsers" :key="user.id" class="custom-item">
              <ion-card class="custom-card">
                <ion-card-content>
                  <div class="user-info">
                    <div class="user-header">
                      <ion-avatar class="user-avatar">
                        <div class="avatar-content">
                          {{ user.username.charAt(0).toUpperCase() }}
                        </div>
                      </ion-avatar>
                      <div class="user-details">
                        <h2 class="user-name">{{ user.username }}</h2>
                        <p class="user-email">{{ user.email }}</p>
                        <div class="user-meta">
                          <ion-chip :class="['status-chip', user.role === 'admin' ? 'admin-chip' : 'user-chip']">
                            <ion-icon :icon="user.role === 'admin' ? 'shield' : 'person'"></ion-icon>
                            <ion-label>{{ user.role === 'admin' ? 'Administrator' : 'Regular User' }}</ion-label>
                          </ion-chip>
                          <ion-chip class="time-chip">
                            <ion-icon :icon="timeOutline"></ion-icon>
                            <ion-label>{{ formatDate(user.last_login) }}</ion-label>
                          </ion-chip>
                        </div>
                      </div>
                    </div>
                    <div class="action-wrapper">
                      <div class="role-select-wrapper" :class="{ 'is-loading': getRoleChangeStatus(user.id) === 'loading' }">
                        <ion-text color="medium" class="select-label">
                          Change Role
                        </ion-text>
                        <ion-select
                          v-model="user.role"
                          interface="popover"
                          @ionChange="handleRoleChange($event, user)"
                          :disabled="user.id === currentUser.id || getRoleChangeStatus(user.id) === 'loading'"
                          class="role-select"
                          :class="{
                            'select-error': lastRoleChangeError && roleChangeState?.userId === user.id,
                            'select-success': !lastRoleChangeError && roleChangeState?.userId === user.id
                          }">
                          <ion-select-option value="user">
                            <ion-icon :icon="personOutline"></ion-icon>
                            Regular User
                          </ion-select-option>
                          <ion-select-option value="admin">
                            <ion-icon :icon="shieldOutline"></ion-icon>
                            Administrator
                          </ion-select-option>
                        </ion-select>
                        <div class="role-status" v-if="getRoleChangeStatus(user.id) === 'loading'">
                          <ion-spinner name="crescent" color="primary"></ion-spinner>
                        </div>
                      </div>
                      <ion-button
                        fill="outline"
                        color="danger"
                        @click="confirmDeleteUser(user)"
                        :disabled="user.id === currentUser.id"
                        class="delete-button">
                        <ion-icon :icon="trashOutline" slot="start"></ion-icon>
                        Delete
                      </ion-button>
                    </div>
                  </div>
                </ion-card-content>
              </ion-card>
            </ion-item>
          </ion-item-group>
        </ion-list>
      </div>

      <!-- Tasks Tab -->
      <div v-if="selectedTab === 'tasks'" class="ion-padding-bottom fade-in">
        <ion-list class="custom-list">
          <ion-item-group v-for="(userTasks, index) in groupedTasks" :key="index">
            <ion-item-divider sticky>
              <ion-label>
                <ion-text color="dark">
                  <h2 class="ion-no-margin">{{ userTasks.username }}</h2>
                </ion-text>
                <ion-badge color="medium" class="ion-margin-top">
                  {{ userTasks.tasks.length }} tasks
                </ion-badge>
              </ion-label>
            </ion-item-divider>

            <ion-item v-for="task in userTasks.tasks" :key="task.id" class="custom-item">
              <ion-card class="custom-card">
                <ion-card-content>
                  <div class="task-content">
                    <div class="task-header">
                      <h3 class="task-title">{{ task.title }}</h3>
                      <ion-chip 
                        :class="['status-chip', task.completed ? 'completed-chip' : 'pending-chip']"
                        class="task-status">
                        <ion-icon :icon="task.completed ? checkmarkCircleOutline : timeOutline"></ion-icon>
                        <ion-label>{{ task.completed ? 'Completed' : 'Pending' }}</ion-label>
                      </ion-chip>
                    </div>
                    <p class="task-description">{{ task.description }}</p>
                    <div class="task-meta">
                      <ion-chip class="time-chip">
                        <ion-icon :icon="calendarOutline"></ion-icon>
                        <ion-label>Due: {{ formatDate(task.due_date) }}</ion-label>
                      </ion-chip>
                    </div>
                  </div>
                </ion-card-content>
              </ion-card>
            </ion-item>
          </ion-item-group>
        </ion-list>
      </div>

      <!-- Resources Tab -->
      <div v-if="selectedTab === 'resources'">
        <ion-list>
          <ion-item-group v-for="(userResources, index) in groupedResources" :key="index">
            <ion-item-divider sticky>
              <ion-label>
                <ion-text color="dark">
                  <h2 class="ion-no-margin">{{ userResources.username }}</h2>
                </ion-text>
                <ion-badge color="medium" class="ion-margin-top">
                  {{ userResources.resources.length }} resources
                </ion-badge>
              </ion-label>
            </ion-item-divider>

            <ion-item v-for="resource in userResources.resources" :key="resource.id" class="custom-item">
              <ion-card class="custom-card">
                <ion-card-content>
                  <div class="resource-content">
                    <div class="resource-header">
                      <ion-thumbnail>
                        <img
                          v-if="isImage(resource.file_type)"
                          :src="getResourceUrl(resource.filename)"
                          alt="Resource thumbnail"
                        />
                        <ion-icon
                          v-else
                          :icon="documentOutline"
                          size="large"
                          class="thumbnail-icon">
                        </ion-icon>
                      </ion-thumbnail>
                      <div class="resource-details">
                        <h3 class="resource-title">{{ resource.original_filename }}</h3>
                        <p class="resource-description">{{ resource.description }}</p>
                        <div class="resource-meta">
                          <ion-chip class="status-chip">
                            <ion-icon :icon="documentOutline"></ion-icon>
                            <ion-label>{{ formatFileSize(resource.file_size) }}</ion-label>
                          </ion-chip>
                          <ion-chip class="time-chip">
                            <ion-icon :icon="timeOutline"></ion-icon>
                            <ion-label>{{ formatDate(resource.upload_date) }}</ion-label>
                          </ion-chip>
                        </div>
                      </div>
                    </div>
                    <div class="action-wrapper">
                      <ion-button
                        fill="outline"
                        color="danger"
                        @click="confirmDeleteResource(resource)"
                        class="delete-button">
                        <ion-icon :icon="trashOutline" slot="start"></ion-icon>
                        Delete
                      </ion-button>
                    </div>
                  </div>
                </ion-card-content>
              </ion-card>
            </ion-item>
          </ion-item-group>
        </ion-list>
      </div>
    </ion-content>
  </page-layout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import {
  IonContent,
  IonSegment,
  IonSegmentButton,
  IonLabel,
  IonIcon,
  IonSearchbar,
  IonList,
  IonItem,
  IonButton,
  IonCard,
  IonCardContent,
  IonGrid,
  IonRow,
  IonCol,
  IonText,
  IonChip,
  IonBadge,
  IonItemDivider,
  IonItemGroup,
  IonThumbnail,
  IonSelect,
  IonSelectOption,
  alertController,
  toastController,
  loadingController
} from '@ionic/vue';
import {
  trashOutline,
  documentOutline,
  timeOutline,
  calendarOutline,
  peopleOutline,
  checkboxOutline,
  folderOutline,
  checkmarkCircleOutline,
  closeCircleOutline,
  shieldOutline,
  personOutline
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

interface GroupedTasks {
  username: string;
  tasks: Task[];
}

interface GroupedResources {
  username: string;
  resources: Resource[];
}

// Add new interface for role change tracking
interface RoleChangeState {
  userId: number;
  isLoading: boolean;
  originalRole: string;
}

// State
const selectedTab = ref('users');
const searchQuery = ref('');
const users = ref<User[]>([]);
const tasks = ref<Task[]>([]);
const resources = ref<Resource[]>([]);
const currentUser = ref<User>(JSON.parse(localStorage.getItem('user') || '{}'));

// Add new refs for role change state
const roleChangeState = ref<RoleChangeState | null>(null);
const lastRoleChangeError = ref<string | null>(null);

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

// Computed properties for grouped data
const groupedTasks = computed<GroupedTasks[]>(() => {
  if (!searchQuery.value) {
    // Group tasks by username when no search
    return Object.values(tasks.value.reduce((acc: Record<string, GroupedTasks>, task: Task) => {
      if (!acc[task.assigned_to]) {
        acc[task.assigned_to] = {
          username: task.assigned_to,
          tasks: []
        };
      }
      acc[task.assigned_to].tasks.push(task);
      return acc;
    }, {}));
  } else {
    // When searching, group filtered tasks
    return Object.values(filteredTasks.value.reduce((acc: Record<string, GroupedTasks>, task: Task) => {
      if (!acc[task.assigned_to]) {
        acc[task.assigned_to] = {
          username: task.assigned_to,
          tasks: []
        };
      }
      acc[task.assigned_to].tasks.push(task);
      return acc;
    }, {}));
  }
});

const groupedResources = computed<GroupedResources[]>(() => {
  if (!searchQuery.value) {
    // Group resources by username when no search
    return Object.values(resources.value.reduce((acc: Record<string, GroupedResources>, resource: Resource) => {
      if (!acc[resource.uploaded_by]) {
        acc[resource.uploaded_by] = {
          username: resource.uploaded_by,
          resources: []
        };
      }
      acc[resource.uploaded_by].resources.push(resource);
      return acc;
    }, {}));
  } else {
    // When searching, group filtered resources
    return Object.values(filteredResources.value.reduce((acc: Record<string, GroupedResources>, resource: Resource) => {
      if (!acc[resource.uploaded_by]) {
        acc[resource.uploaded_by] = {
          username: resource.uploaded_by,
          resources: []
        };
      }
      acc[resource.uploaded_by].resources.push(resource);
      return acc;
    }, {}));
  }
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

// Enhanced role change handler
const handleRoleChange = async (event: CustomEvent, user: any) => {
  const newRole = event.detail.value;
  const originalRole = user.role;
  
  // Set loading state
  roleChangeState.value = {
    userId: user.id,
    isLoading: true,
    originalRole
  };

  // Show loading indicator
  const loading = await loadingController.create({
    message: 'Updating role...',
    duration: 10000 // Max duration of 10 seconds
  });
  await loading.present();

  try {
    const response = await api.post('/admin.php', {
      action: 'update_user_role',
      user_id: user.id,
      role: newRole
    });

    if (response.data.success) {
      // Dismiss loading indicator
      await loading.dismiss();
      
      // Show success toast
      const toast = await toastController.create({
        message: `Role successfully updated to ${newRole === 'admin' ? 'Administrator' : 'Regular User'}`,
        duration: 2000,
        color: 'success',
        position: 'bottom',
        buttons: [
          {
            icon: checkmarkCircleOutline,
            role: 'cancel'
          }
        ]
      });
      await toast.present();
      
      // Update the local user data
      user.role = newRole;
      lastRoleChangeError.value = null;
    } else {
      throw new Error(response.data.message || 'Failed to update role');
    }
  } catch (error: any) {
    // Dismiss loading indicator
    await loading.dismiss();
    
    // Show error toast
    const toast = await toastController.create({
      message: error.message || 'Failed to update user role',
      duration: 3000,
      color: 'danger',
      position: 'bottom',
      buttons: [
        {
          icon: closeCircleOutline,
          role: 'cancel'
        }
      ]
    });
    await toast.present();
    
    // Store the error message
    lastRoleChangeError.value = error.message || 'Failed to update user role';
    
    // Revert the select value
    user.role = originalRole;
    
    // Show revert alert
    const alert = await alertController.create({
      header: 'Role Update Failed',
      message: 'The role change could not be completed. The previous role has been restored.',
      buttons: ['OK']
    });
    await alert.present();
  } finally {
    // Clear loading state
    roleChangeState.value = null;
  }
};

// Add computed property for role change status
const getRoleChangeStatus = (userId: number) => {
  if (roleChangeState.value?.userId === userId) {
    return roleChangeState.value.isLoading ? 'loading' : 'success';
  }
  return 'idle';
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
  const date = new Date(dateString);
  const today = new Date();
  const tomorrow = new Date(today);
  tomorrow.setDate(tomorrow.getDate() + 1);

  if (date.toDateString() === today.toDateString()) {
    return `Today at ${date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
  } else if (date.toDateString() === tomorrow.toDateString()) {
    return `Tomorrow at ${date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
  } else {
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  }
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
/* Dashboard Header */
.dashboard-header {
  padding: 16px 0;
}

.dashboard-title {
  font-size: 2rem;
  font-weight: 700;
  color: var(--ion-color-dark);
  margin: 0 0 16px 0;
  letter-spacing: -0.5px;
}

/* Custom Segment */
.custom-segment {
  --background: var(--ion-color-light);
  border-radius: 12px;
  padding: 4px;
}

.segment-button {
  --background-checked: var(--ion-color-primary);
  --color-checked: var(--ion-color-primary-contrast);
  --indicator-color: transparent;
  border-radius: 8px;
  text-transform: none;
  letter-spacing: 0;
  font-weight: 500;
}

.segment-button ion-icon {
  margin-bottom: 4px;
}

/* Custom Searchbar */
.custom-searchbar {
  --background: var(--ion-color-light);
  --box-shadow: none;
  --border-radius: 12px;
  --placeholder-color: var(--ion-color-medium);
  --icon-color: var(--ion-color-medium);
  padding: 0;
  margin-top: 16px;
}

/* List and Cards */
.custom-list {
  background: transparent;
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 16px;
}

.custom-item {
  --padding-start: 0;
  --padding-end: 0;
  --inner-padding-end: 0;
  --background: transparent;
}

.custom-card {
  width: 100%;
  margin: 0;
  border-radius: 16px;
  background: var(--ion-card-background);
  border: 1px solid var(--ion-border-color);
  overflow: hidden;
}

/* User Card Styles */
.user-info {
  width: 100%;
}

.user-header {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 12px;
}

.user-avatar {
  width: 48px;
  height: 48px;
  --border-radius: 12px;
  flex-shrink: 0;
}

.avatar-content {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--ion-color-primary);
  color: var(--ion-color-primary-contrast);
  font-size: 1.2rem;
  font-weight: 600;
}

.user-details {
  flex: 1;
  min-width: 0;
}

.user-name {
  font-size: 1.1rem;
  font-weight: 600;
  margin: 0;
  color: var(--ion-text-color);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.user-email {
  font-size: 0.9rem;
  color: var(--ion-color-medium);
  margin: 4px 0 0 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.user-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

/* Task Card Styles */
.task-content {
  width: 100%;
}

.task-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 8px;
}

.task-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--ion-text-color);
  margin: 0;
  flex: 1;
  min-width: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.task-description {
  color: var(--ion-color-medium);
  margin: 0 0 12px 0;
  font-size: 0.95rem;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Resource Card Styles */
.resource-content {
  width: 100%;
}

.resource-header {
  display: flex;
  gap: 16px;
  margin-bottom: 12px;
}

.resource-details {
  flex: 1;
  min-width: 0;
}

.resource-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--ion-text-color);
  margin: 0 0 8px 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.resource-description {
  color: var(--ion-color-medium);
  margin: 0 0 12px 0;
  font-size: 0.95rem;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.resource-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

ion-thumbnail {
  --size: 48px;
  --border-radius: 8px;
  background: var(--ion-color-light);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid var(--ion-border-color);
}

.thumbnail-icon {
  font-size: 24px;
  color: var(--ion-color-medium);
}

/* Chips and Status Indicators */
.status-chip {
  --background: transparent;
  font-weight: 500;
  padding: 4px 12px;
  margin: 0;
  height: 24px;
  
  &.admin-chip {
    --color: var(--ion-color-primary);
    border: 1px solid var(--ion-color-primary);
  }
  
  &.user-chip {
    --color: var(--ion-color-success);
    border: 1px solid var(--ion-color-success);
  }
  
  &.completed-chip {
    --color: var(--ion-color-success);
    border: 1px solid var(--ion-color-success);
  }
  
  &.pending-chip {
    --color: var(--ion-color-warning);
    border: 1px solid var(--ion-color-warning);
  }
}

.time-chip {
  --background: rgba(var(--ion-color-medium-rgb), 0.1);
  --color: var(--ion-color-medium);
  border: 1px solid var(--ion-color-medium);
  height: 24px;
  padding: 4px 12px;
  margin: 0;
}

/* Action Buttons */
.action-column {
  display: flex;
  align-items: center;
  justify-content: flex-end;
}

.action-wrapper {
  display: flex;
  flex-direction: column;
  gap: 8px;
  width: 100%;
}

.role-select-wrapper {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.select-label {
  font-size: 0.85rem;
}

.role-select {
  width: 100%;
  max-width: 200px;
  --padding-start: 8px;
  --padding-end: 8px;
  border: 1px solid var(--ion-border-color);
  border-radius: 8px;
}

.delete-button {
  width: 100%;
  max-width: 200px;
  height: 36px;
  --padding-start: 12px;
  --padding-end: 12px;
  font-size: 0.9rem;
}

/* Animations */
.fade-in {
  animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Dark Mode */
@media (prefers-color-scheme: dark) {
  .dashboard-title {
    color: var(--ion-color-light);
  }

  .custom-card {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
  }

  .avatar-content {
    background: var(--ion-color-primary-shade);
  }

  .admin-chip {
    --background: rgba(var(--ion-color-primary-rgb), 0.15);
  }

  .user-chip {
    --background: rgba(var(--ion-color-success-rgb), 0.15);
  }

  .time-chip {
    --background: rgba(var(--ion-color-medium-rgb), 0.15);
  }
}

/* Responsive Design */
@media (max-width: 768px) {
  .dashboard-title {
    font-size: 1.75rem;
  }

  .custom-list {
    padding: 0 12px;
  }

  .custom-card {
    border-radius: 12px;
  }

  .user-header {
    gap: 12px;
  }

  .user-avatar {
    width: 40px;
    height: 40px;
  }

  .user-name {
    font-size: 1rem;
  }

  .user-email {
    font-size: 0.85rem;
  }

  .task-title {
    font-size: 1rem;
  }

  .task-description {
    font-size: 0.9rem;
  }

  .action-wrapper {
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
  }

  .role-select,
  .delete-button {
    max-width: 48%;
  }

  .resource-header {
    gap: 12px;
  }

  .resource-title {
    font-size: 1rem;
  }

  .resource-description {
    font-size: 0.9rem;
  }
}

@media (max-width: 480px) {
  .dashboard-title {
    font-size: 1.5rem;
  }

  .custom-list {
    padding: 0 8px;
  }

  .action-wrapper {
    flex-direction: column;
  }

  .role-select,
  .delete-button {
    max-width: 100%;
  }

  .user-meta,
  .task-meta {
    flex-direction: column;
    align-items: flex-start;
  }

  .status-chip,
  .time-chip {
    width: 100%;
  }

  .resource-meta {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style> 