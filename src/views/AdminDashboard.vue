<template>
  <page-layout title="Admin Dashboard">
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
            <ion-item v-for="user in filteredUsers" :key="user.id" class="custom-item ion-margin-bottom">
              <ion-card class="custom-card ion-no-margin">
                <ion-card-content>
                  <ion-grid class="ion-no-padding">
                    <ion-row class="ion-align-items-center">
                      <ion-col size="12" size-md="7">
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
                            </div>
                          </div>
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
                      </ion-col>
                      <ion-col size="12" size-md="5" class="action-column">
                        <div class="action-wrapper">
                          <div class="role-select-wrapper" :class="{ 'is-loading': getRoleChangeStatus(user.id) === 'loading' }">
                            <ion-text color="medium" class="select-label">
                              Change Role
                            </ion-text>
                            <ion-select
                              v-model="user.role"
                              interface="action-sheet"
                              @ionChange="handleRoleChange($event, user)"
                              :disabled="user.id === currentUser.id || getRoleChangeStatus(user.id) === 'loading'"
                              class="role-select"
                              :class="{
                                'select-error': lastRoleChangeError && roleChangeState?.userId === user.id,
                                'select-success': !lastRoleChangeError && roleChangeState?.userId === user.id
                              }">
                              <ion-select-option value="user">Regular User</ion-select-option>
                              <ion-select-option value="admin">Administrator</ion-select-option>
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
                      </ion-col>
                    </ion-row>
                  </ion-grid>
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

            <ion-item v-for="task in userTasks.tasks" :key="task.id" class="custom-item ion-margin-bottom">
              <ion-card class="custom-card ion-no-margin">
                <ion-card-content>
                  <ion-grid class="ion-no-padding">
                    <ion-row class="ion-align-items-center">
                      <ion-col size="12">
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
                      </ion-col>
                    </ion-row>
                  </ion-grid>
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

            <ion-item v-for="resource in userResources.resources" :key="resource.id" class="ion-margin-bottom">
              <ion-card class="ion-no-margin ion-margin-vertical full-width">
                <ion-card-content>
                  <ion-grid>
                    <ion-row class="ion-align-items-center">
                      <ion-col size="auto">
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
                      </ion-col>
                      <ion-col>
                        <ion-text color="dark">
                          <h3 class="ion-no-margin">{{ resource.original_filename }}</h3>
                        </ion-text>
                        <ion-text color="medium">
                          <p class="ion-no-margin ion-margin-vertical">{{ resource.description }}</p>
                        </ion-text>
                        <ion-chip color="tertiary" outline>
                          <ion-icon :icon="documentOutline"></ion-icon>
                          <ion-label>{{ formatFileSize(resource.file_size) }}</ion-label>
                        </ion-chip>
                        <ion-chip color="medium">
                          <ion-icon :icon="timeOutline"></ion-icon>
                          <ion-label>{{ formatDate(resource.upload_date) }}</ion-label>
                        </ion-chip>
                      </ion-col>
                      <ion-col size="auto">
                        <ion-button
                          fill="outline"
                          color="danger"
                          @click="confirmDeleteResource(resource)"
                          class="action-button">
                          <ion-icon :icon="trashOutline" slot="start"></ion-icon>
                          Delete
                        </ion-button>
                      </ion-col>
                    </ion-row>
                  </ion-grid>
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
  closeCircleOutline
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
  transition: all 0.2s ease;
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
}

.custom-item {
  --padding-start: 0;
  --padding-end: 0;
  --inner-padding-end: 0;
  --background: transparent;
}

.custom-card {
  border-radius: 16px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  width: 100%;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.custom-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
}

/* User Info */
.user-info {
  padding: 8px 0;
}

.user-header {
  display: flex;
  align-items: center;
  gap: 16px;
}

.user-avatar {
  width: 48px;
  height: 48px;
  --border-radius: 14px;
}

.avatar-content {
  width: 100%;
  height: 100%;
  background: var(--ion-color-primary);
  color: var(--ion-color-primary-contrast);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  font-weight: 600;
}

.user-details {
  flex: 1;
}

.user-name {
  font-size: 1.2rem;
  font-weight: 600;
  color: var(--ion-color-dark);
  margin: 0;
  line-height: 1.4;
}

.user-email {
  font-size: 0.95rem;
  color: var(--ion-color-medium);
  margin: 4px 0 0 0;
  line-height: 1.4;
}

.user-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 16px;
}

/* Status Chips */
.status-chip {
  --background: transparent;
  font-weight: 500;
  padding: 4px 12px;
}

.admin-chip {
  --background: rgba(var(--ion-color-primary-rgb), 0.1);
  --color: var(--ion-color-primary);
  border: 1px solid var(--ion-color-primary);
}

.user-chip {
  --background: rgba(var(--ion-color-success-rgb), 0.1);
  --color: var(--ion-color-success);
  border: 1px solid var(--ion-color-success);
}

.time-chip {
  --background: rgba(var(--ion-color-medium-rgb), 0.1);
  --color: var(--ion-color-medium);
  border: 1px solid var(--ion-color-medium);
}

/* Actions */
.action-column {
  display: flex;
  align-items: center;
  justify-content: flex-end;
}

.action-wrapper {
  display: flex;
  align-items: center;
  gap: 16px;
}

.role-select-wrapper {
  position: relative;
  background: var(--ion-color-light);
  border-radius: 12px;
  padding: 8px 16px;
  display: flex;
  align-items: center;
  gap: 12px;
  transition: all 0.2s ease;
}

.role-select-wrapper.is-loading {
  background: rgba(var(--ion-color-primary-rgb), 0.05);
}

.role-status {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  display: flex;
  align-items: center;
  justify-content: center;
}

.select-label {
  font-size: 0.95rem;
  font-weight: 500;
  white-space: nowrap;
}

.role-select {
  --background: transparent;
  --border-width: 0;
  --padding-start: 8px;
  --padding-end: 32px;
  min-width: 160px;
  font-size: 0.95rem;
  font-weight: 500;
  transition: all 0.2s ease;
}

.select-error {
  --highlight-color: var(--ion-color-danger);
  color: var(--ion-color-danger);
}

.select-success {
  --highlight-color: var(--ion-color-success);
  color: var(--ion-color-success);
}

.delete-button {
  --border-radius: 12px;
  --border-width: 2px;
  --box-shadow: none;
  height: 42px;
  font-weight: 500;
}

.delete-button ion-icon {
  font-size: 18px;
  margin-right: 6px;
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

  .custom-card:hover {
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.3);
  }

  .role-select-wrapper.is-loading {
    background: rgba(var(--ion-color-primary-rgb), 0.15);
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

  .action-column {
    margin-top: 16px;
  }

  .action-wrapper {
    flex-direction: column;
    width: 100%;
  }

  .role-select-wrapper {
    width: 100%;
    justify-content: space-between;
  }

  .role-select {
    flex: 1;
    min-width: 0;
  }

  .delete-button {
    width: 100%;
  }
}

@media (max-width: 480px) {
  .dashboard-title {
    font-size: 1.5rem;
  }

  .user-header {
    gap: 12px;
  }

  .user-avatar {
    width: 40px;
    height: 40px;
  }

  .user-name {
    font-size: 1.1rem;
  }

  .user-email {
    font-size: 0.9rem;
  }

  .status-chip {
    font-size: 0.85rem;
  }
}

/* Task Styles */
.task-content {
  padding: 8px 0;
}

.task-header {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  margin-bottom: 8px;
}

.task-title {
  font-size: 1.2rem;
  font-weight: 600;
  color: var(--ion-color-dark);
  margin: 0;
  line-height: 1.4;
  flex: 1;
}

.task-description {
  color: var(--ion-color-medium);
  margin: 8px 0;
  line-height: 1.5;
  font-size: 0.95rem;
}

.task-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 12px;
}

.status-chip {
  margin: 0;
  min-width: 100px;
  justify-content: center;
}

.completed-chip {
  --background: rgba(var(--ion-color-success-rgb), 0.1);
  --color: var(--ion-color-success);
  border: 1px solid var(--ion-color-success);
}

.pending-chip {
  --background: rgba(var(--ion-color-warning-rgb), 0.1);
  --color: var(--ion-color-warning);
  border: 1px solid var(--ion-color-warning);
}

.time-chip {
  --background: rgba(var(--ion-color-medium-rgb), 0.1);
  --color: var(--ion-color-medium);
  border: 1px solid var(--ion-color-medium);
}

/* Dark mode adjustments */
@media (prefers-color-scheme: dark) {
  .task-title {
    color: var(--ion-color-light);
  }

  .completed-chip {
    --background: rgba(var(--ion-color-success-rgb), 0.15);
  }

  .pending-chip {
    --background: rgba(var(--ion-color-warning-rgb), 0.15);
  }

  .time-chip {
    --background: rgba(var(--ion-color-medium-rgb), 0.15);
  }
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .task-status-icon {
    width: 40px;
    height: 40px;
    margin-right: 12px;
  }

  .task-status-icon ion-icon {
    font-size: 20px;
  }

  .task-title {
    font-size: 1.1rem;
  }

  .task-description {
    font-size: 0.9rem;
  }
}
</style> 