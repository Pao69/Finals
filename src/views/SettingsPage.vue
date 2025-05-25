<template>
  <page-layout title="Settings">
    <ion-content class="ion-padding">
      <div class="settings-container">
        <!-- Profile Card -->
        <ion-card class="profile-card">
          <div class="profile-header">
            <div class="avatar-section">
              <ion-avatar class="large-avatar">
                <img 
                  :src="profileImage" 
                  alt="Profile" 
                  @error="handleImageError"
                  class="profile-avatar-image"/>
              </ion-avatar>
              <div class="avatar-actions">
                <ion-button 
                  fill="clear" 
                  class="upload-photo-btn"
                  @click="triggerFileInput">
                  <ion-icon :icon="cameraOutline" class="camera-icon"></ion-icon>
                  <span>Change Photo</span>
                </ion-button>
                <input 
                  type="file" 
                  ref="fileInput"
                  id="profile-picture"
                  accept="image/*" 
                  @change="handleProfilePictureSelect"
                  class="file-input"
                  style="display: none"/>
                <ion-button 
                  v-if="profileImage !== 'https://ionicframework.com/docs/img/demos/avatar.svg'"
                  fill="clear" 
                  color="danger"
                  class="remove-photo-btn"
                  @click="deleteProfilePicture">
                  <ion-icon :icon="trashOutline" slot="start"></ion-icon>
                  <span>Remove</span>
                </ion-button>
              </div>
            </div>
            <h2 class="profile-name">{{ profile.username }}</h2>
            <p class="profile-email">{{ profile.email }}</p>
          </div>
        </ion-card>

        <!-- Settings Sections -->
        <div class="settings-sections">
          <!-- Profile Information -->
          <ion-card class="settings-section">
            <ion-card-header>
              <ion-card-title>
                <ion-icon :icon="personOutline" class="section-icon"></ion-icon>
                Profile Information
              </ion-card-title>
            </ion-card-header>
            <ion-card-content>
              <div class="settings-form">
                <ion-item>
                  <ion-label position="stacked">Username</ion-label>
                  <ion-input 
                    v-model="profile.username" 
                    type="text" 
                    placeholder="Enter username"
                    class="custom-input">
                  </ion-input>
                </ion-item>
                <ion-item>
                  <ion-label position="stacked">Email</ion-label>
                  <ion-input 
                    v-model="profile.email" 
                    type="email" 
                    placeholder="Enter email"
                    class="custom-input">
                  </ion-input>
                </ion-item>
                <ion-item>
                  <ion-label position="stacked">Phone</ion-label>
                  <ion-input 
                    v-model="profile.phone" 
                    type="tel" 
                    placeholder="Enter phone"
                    class="custom-input">
                  </ion-input>
                </ion-item>
                <ion-button 
                  expand="block" 
                  @click="updateProfile" 
                  :disabled="!isProfileChanged"
                  class="save-button">
                  <ion-icon :icon="saveOutline" slot="start"></ion-icon>
                  Save Changes
                </ion-button>
              </div>
            </ion-card-content>
          </ion-card>

          <!-- Security Settings -->
          <ion-card class="settings-section">
            <ion-card-header>
              <ion-card-title>
                <ion-icon :icon="lockClosedOutline" class="section-icon"></ion-icon>
                Security
              </ion-card-title>
            </ion-card-header>
            <ion-card-content>
              <div class="security-options">
                <ion-button 
                  expand="block" 
                  fill="outline" 
                  @click="openChangePasswordModal"
                  class="security-button">
                  <ion-icon :icon="keyOutline" slot="start"></ion-icon>
                  Change Password
                </ion-button>
              </div>
            </ion-card-content>
          </ion-card>

          <!-- Account Actions -->
          <ion-card class="settings-section danger-zone">
            <ion-card-header>
              <ion-card-title>
                <ion-icon :icon="warningOutline" class="section-icon"></ion-icon>
                Account Actions
              </ion-card-title>
            </ion-card-header>
            <ion-card-content>
              <div class="danger-actions">
                <ion-button 
                  expand="block" 
                  color="danger" 
                  fill="outline"
                  @click="deleteProfile"
                  class="danger-button">
                  <ion-icon :icon="trashOutline" slot="start"></ion-icon>
                  Delete Account
                </ion-button>
                <ion-button 
                  expand="block" 
                  color="medium" 
                  @click="handleLogout"
                  class="logout-button">
                  <ion-icon :icon="logOutOutline" slot="start"></ion-icon>
                  Logout
                </ion-button>
              </div>
            </ion-card-content>
          </ion-card>
        </div>
      </div>
    </ion-content>

    <!-- Change Password Modal -->
    <ion-modal 
      :is-open="isChangePasswordModalOpen" 
      @didDismiss="isChangePasswordModalOpen = false"
      class="password-modal">
      <ion-header>
        <ion-toolbar>
          <ion-title>Change Password</ion-title>
          <ion-buttons slot="end">
            <ion-button @click="isChangePasswordModalOpen = false">
              <ion-icon :icon="closeOutline"></ion-icon>
            </ion-button>
          </ion-buttons>
        </ion-toolbar>
      </ion-header>

      <ion-content class="ion-padding">
        <form @submit.prevent="changePassword" class="password-form">
          <div class="form-group">
            <ion-item lines="full" class="custom-input-item">
              <ion-icon :icon="lockClosedOutline" slot="start" color="primary"></ion-icon>
              <ion-label position="floating">Current Password</ion-label>
              <ion-input
                type="password"
                v-model="passwordForm.currentPassword"
                required
                placeholder="Enter current password"
                class="custom-input"
              ></ion-input>
            </ion-item>
            <div v-if="passwordErrors.currentPassword" class="error-message">
              {{ passwordErrors.currentPassword }}
            </div>
          </div>

          <div class="form-group">
            <ion-item lines="full" class="custom-input-item">
              <ion-icon :icon="keyOutline" slot="start" color="primary"></ion-icon>
              <ion-label position="floating">New Password</ion-label>
              <ion-input
                type="password"
                v-model="passwordForm.newPassword"
                required
                placeholder="Enter new password"
                class="custom-input">
              </ion-input>
            </ion-item>
            <div v-if="passwordErrors.newPassword" class="error-message">
              {{ passwordErrors.newPassword }}
            </div>
          </div>

          <div class="form-group">
            <ion-item lines="full" class="custom-input-item">
              <ion-icon :icon="keyOutline" slot="start" color="primary"></ion-icon>
              <ion-label position="floating">Confirm New Password</ion-label>
              <ion-input
                type="password"
                v-model="passwordForm.confirmPassword"
                required
                placeholder="Confirm new password"
                class="custom-input">
              </ion-input>
            </ion-item>
            <div v-if="passwordErrors.confirmPassword" class="error-message">
              {{ passwordErrors.confirmPassword }}
            </div>
          </div>

          <ion-button 
            expand="block" 
            type="submit"
            class="submit-button">
            Change Password
          </ion-button>
        </form>
      </ion-content>
    </ion-modal>
  </page-layout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/utils/api';
import {
  IonContent,
  IonList, IonItem, IonItemGroup, IonItemDivider,
  IonLabel, IonButton, IonIcon, IonInput, IonModal,
  IonButtons, toastController, IonAvatar, alertController
} from '@ionic/vue';
import {
  personOutline,
  lockClosedOutline, 
  logOutOutline, 
  saveOutline,
  closeOutline, 
  closeCircle, 
  checkmarkCircle,
  cameraOutline, 
  keyOutline,
  checkmarkCircleOutline, 
  trashOutline, 
  warningOutline
} from 'ionicons/icons';
import PageLayout from '@/components/PageLayout.vue';

// Register components
const components = {
  IonContent,
  IonList, IonItem, IonItemGroup, IonItemDivider,
  IonLabel, IonButton, IonIcon, IonInput, IonModal,
  IonButtons, IonAvatar
};

const router = useRouter();
const isChangePasswordModalOpen = ref(false);

//for default profile
const profileImage = ref('https://ionicframework.com/docs/img/demos/avatar.svg');

// Profile state
const originalProfile = ref({
  username: '',
  email: '',
  phone: '',
  role: 'user'
});

const profile = ref({
  username: '',
  email: '',
  phone: '',
  role: 'user'
});

// Password change form
const passwordForm = ref({
  currentPassword: '',
  newPassword: '',
  confirmPassword: ''
});

const passwordErrors = ref({
  currentPassword: '',
  newPassword: '',
  confirmPassword: ''
});

// Computed properties
const isProfileChanged = computed(() => {
  return JSON.stringify(profile.value) !== JSON.stringify(originalProfile.value);
});

// Load user profile with image
onMounted(async () => {
  const userData = localStorage.getItem('user') || sessionStorage.getItem('user');
  if (userData) {
    const user = JSON.parse(userData);
    originalProfile.value = {
      username: user.username || '',
      email: user.email || '',
      phone: user.phone || '',
      role: user.role || 'user'
    };
    profile.value = { ...originalProfile.value };
    
    // Set profile image if it exists in user data
    if (user.pfp) {
      profileImage.value = user.pfp;
    }
    
    // Fetch current profile picture in case it was updated
    await fetchProfilePicture();
  } else {
    // If no user data is found, redirect to login
    router.push('/login');
  }
});

// Password validation
const validatePassword = () => {
  passwordErrors.value = {
    currentPassword: '',
    newPassword: '',
    confirmPassword: ''
  };

  if (!passwordForm.value.currentPassword) {
    passwordErrors.value.currentPassword = 'Current password is required';
  }

  if (!passwordForm.value.newPassword) {
    passwordErrors.value.newPassword = 'New password is required';
  } else if (passwordForm.value.newPassword.length < 8) {
    passwordErrors.value.newPassword = 'Password must be at least 8 characters long';
  }

  if (!passwordForm.value.confirmPassword) {
    passwordErrors.value.confirmPassword = 'Please confirm your new password';
  } else if (passwordForm.value.newPassword !== passwordForm.value.confirmPassword) {
    passwordErrors.value.confirmPassword = 'Passwords do not match';
  }

  return !Object.values(passwordErrors.value).some(error => error);
};

const handleImageError = (e: Event) => {
  const target = e.target as HTMLImageElement;
  target.src = 'https://ionicframework.com/docs/img/demos/avatar.svg';
};

const handleProfilePictureSelect = async (event: Event) => {
  const input = event.target as HTMLInputElement;
  if (input.files && input.files[0]) {
    const file = input.files[0];
    
    // Validate file type
    if (!file.type.startsWith('image/')) {
      const toast = await toastController.create({
        message: 'Please select an image file',
        duration: 2000,
        color: 'danger',
        position: 'top',
        icon: closeCircle
      });
      await toast.present();
      return;
    }

    // Validate file size (max 5MB)
    if (file.size > 5 * 1024 * 1024) {
      const toast = await toastController.create({
        message: 'Image size should be less than 5MB',
        duration: 2000,
        color: 'danger',
        position: 'top',
        icon: closeCircle
      });
      await toast.present();
      return;
    }

    try {
      // Create FormData for file upload
      const formData = new FormData();
      formData.append('profile_image', file);

      console.log('Uploading file:', {
        name: file.name,
        type: file.type,
        size: file.size
      });

      // Use the consistent API instance with FormData
      const response = await api.post('/profile_picture.php', formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      });

      console.log('Server response:', response.data);

      if (response.data.success) {
        // Construct the full URL for the profile image
        const baseUrl = import.meta.env.VITE_API_URL || 'http://localhost';
        const imageUrl = response.data.image_link.startsWith('http') 
          ? response.data.image_link 
          : `${baseUrl}${response.data.image_link}`;
        
        // Update the profile image
        profileImage.value = imageUrl;
        
        // Update local storage
        const userData = JSON.parse(localStorage.getItem('user') || '{}');
        userData.pfp = imageUrl;
        localStorage.setItem('user', JSON.stringify(userData));

        const toast = await toastController.create({
          message: 'Profile picture updated successfully',
          duration: 2000,
          color: 'success',
          position: 'top',
          icon: checkmarkCircle
        });
        await toast.present();

        // Clear the file input
        input.value = '';

        // Force a page refresh after a shorter delay
        setTimeout(() => {
          window.location.reload();
        }, 200);
      } else {
        const toast = await toastController.create({
          message: response.data.message || 'Failed to update profile picture',
          duration: 2000,
          color: 'danger',
          position: 'top',
          icon: closeCircle
        });
        await toast.present();
      }
    } catch (error: any) {
      console.error('Upload error details:', error);

      // Get detailed error message
      let errorMessage = 'Failed to update profile picture';
      let errorDetails = '';

      if (error.response?.data) {
        errorMessage = error.response.data.message || errorMessage;
        if (error.response.data.error_details) {
          const details = error.response.data.error_details;
          errorDetails = `\nDetails:\n`;
          if (!details.upload_dir_exists) {
            errorDetails += '- Upload directory does not exist\n';
          }
          if (!details.upload_dir_writable) {
            errorDetails += '- Upload directory is not writable\n';
          }
          if (details.error_code) {
            errorDetails += `- Error code: ${details.error_code}\n`;
          }
        }
      } else if (error.message) {
        errorMessage = error.message;
      }

      // Show error toast with details if available
      const toast = await toastController.create({
        message: errorDetails ? `${errorMessage}\n${errorDetails}` : errorMessage,
        duration: 5000,
        color: 'danger',
        position: 'top',
        icon: closeCircle
      });
      await toast.present();

      // Clear the file input on error
      input.value = '';
    }
  }
};

const deleteProfilePicture = async () => {
  const alert = await alertController.create({
    header: 'Remove Profile Picture',
    message: 'Are you sure you want to remove your profile picture?',
    buttons: [
      {
        text: 'Cancel',
        role: 'cancel',
        cssClass: 'secondary'
      },
      {
        text: 'Remove',
        role: 'destructive',
        handler: async () => {
          try {
            const response = await api.delete('/profile_picture.php');

            if (response.data.success) {
              // Reset to default avatar
              profileImage.value = 'https://ionicframework.com/docs/img/demos/avatar.svg';
              
              // Update local storage
              const userData = JSON.parse(localStorage.getItem('user') || '{}');
              userData.pfp = 'https://ionicframework.com/docs/img/demos/avatar.svg';
              localStorage.setItem('user', JSON.stringify(userData));

              const toast = await toastController.create({
                message: 'Profile picture removed successfully',
                duration: 2000,
                color: 'success',
                position: 'top',
                icon: checkmarkCircle
              });
              await toast.present();
            }

            // Force a page refresh after a shorter delay, regardless of success or failure
            setTimeout(() => {
              window.location.reload();
            }, 200);
          } catch (error: any) {
            console.error('Delete error:', error);
            const toast = await toastController.create({
              message: error.message || 'Failed to remove profile picture',
              duration: 2000,
              color: 'danger',
              position: 'top',
              icon: closeCircle
            });
            await toast.present();

            // Still reload the page even if there's an error
            setTimeout(() => {
              window.location.reload();
            }, 200);
          }
        }
      }
    ]
  });

  await alert.present();
};

//Logout
const handleLogout = () => {
  localStorage.removeItem('user');
  localStorage.removeItem('token');
  router.push('/login');
};

const updateProfile = async () => {
  try {
    // Validate email format
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(profile.value.email)) {
      throw new Error('Please enter a valid email address');
    }

    // Validate phone format (optional)
    if (profile.value.phone && !/^\+?[\d\s-]{10,}$/.test(profile.value.phone)) {
      throw new Error('Please enter a valid phone number');
    }

    const response = await api.post('/update_profile.php', profile.value);

    if (response.data.success) {
      // Get the storage where user data is stored
      const storage = localStorage.getItem('user') ? localStorage : sessionStorage;
      const userData = JSON.parse(storage.getItem('user') || '{}');
      
      // Update user data while preserving other fields
      const updatedUser = { 
        ...userData,
        username: profile.value.username,
        email: profile.value.email,
        phone: profile.value.phone
      };
      
      // Store updated user data
      storage.setItem('user', JSON.stringify(updatedUser));
      originalProfile.value = { ...profile.value };

      const toast = await toastController.create({
        message: 'Profile updated successfully',
        duration: 2000,
        color: 'success',
        position: 'top',
        icon: checkmarkCircle
      });
      await toast.present();
    } else {
      throw new Error(response.data.message || 'Failed to update profile');
    }
  } catch (error: any) {
    console.error('Profile update error:', error);
    const toast = await toastController.create({
      message: error.response?.data?.message || error.message || 'Failed to update profile',
      duration: 3000,
      color: 'danger',
      position: 'top',
      icon: closeCircle
    });
    await toast.present();
  }
};

const deleteProfile = async () => {
  const alert = await alertController.create({
    header: 'Delete Account',
    message: 'Are you sure you want to delete your account? This action cannot be undone.',
    buttons: [
      {
        text: 'Cancel',
        role: 'cancel',
        cssClass: 'secondary'
      },
      {
        text: 'Delete',
        role: 'destructive',
        handler: async () => {
          try {
            const token = localStorage.getItem('token');
            if (!token) {
              throw new Error('No authentication token found');
            }

            const response = await api.delete('/delete_profile.php');

            if (response.data.success) {
              // Clear local storage
              localStorage.removeItem('user');
              localStorage.removeItem('token');

              const toast = await toastController.create({
                message: 'Account deleted successfully',
                duration: 2000,
                color: 'success',
                position: 'top',
                icon: checkmarkCircle
              });
              await toast.present();

              // Redirect to login page
              router.push('/login');
            }
          } catch (error: any) {
            const toast = await toastController.create({
              message: error.response?.data?.message || 'Failed to delete account',
              duration: 3000,
              color: 'danger',
              position: 'top',
              icon: closeCircle
            });
            await toast.present();
          }
        }
      }
    ]
  });

  await alert.present();
};

const openChangePasswordModal = () => {
  passwordForm.value = {
    currentPassword: '',
    newPassword: '',
    confirmPassword: ''
  };
  isChangePasswordModalOpen.value = true;
};

const changePassword = async () => {
  if (!validatePassword()) {
    return;
  }

  try {
    const response = await api.post('/change_password.php', {
      currentPassword: passwordForm.value.currentPassword,
      newPassword: passwordForm.value.newPassword
    });

    if (response.data.success) {
      const toast = await toastController.create({
        message: 'Password changed successfully',
        duration: 2000,
        color: 'success'
      });
      await toast.present();

      // Clear form and close modal
      passwordForm.value = {
        currentPassword: '',
        newPassword: '',
        confirmPassword: ''
      };
      isChangePasswordModalOpen.value = false;
    }
  } catch (error: any) {
    const toast = await toastController.create({
      message: error.response?.data?.message || 'Failed to change password',
      duration: 3000,
      color: 'danger'
    });
    await toast.present();
  }
};

const fetchProfilePicture = async () => {
  try {
    // First check local storage or session storage for cached profile picture
    const userData = localStorage.getItem('user') || sessionStorage.getItem('user');
    if (userData) {
      const user = JSON.parse(userData);
      if (user.pfp) {
        profileImage.value = user.pfp;
      }
    }

    const token = localStorage.getItem('token') || sessionStorage.getItem('token');
    if (!token) return;

    const response = await api.get('/profile_picture.php');
    if (response.data.success && response.data.image_link) {
      profileImage.value = response.data.image_link;
      
      // Update user data in the storage that contains it
      const storage = localStorage.getItem('user') ? localStorage : sessionStorage;
      const userData = JSON.parse(storage.getItem('user') || '{}');
      userData.pfp = response.data.image_link;
      storage.setItem('user', JSON.stringify(userData));
    }
  } catch (error) {
    console.error('Failed to fetch profile picture:', error);
    // If there's an error, ensure we at least show the default avatar
    if (!profileImage.value) {
      profileImage.value = 'https://ionicframework.com/docs/img/demos/avatar.svg';
    }
  }
};

const triggerFileInput = () => {
  const fileInput = document.getElementById('profile-picture');
  if (fileInput) {
    fileInput.click();
  }
};
</script>

<style scoped>
.settings-container {
  max-width: 800px;
  margin: 0 auto;
  padding: 0;
}

.profile-card {
  margin: 0 0 16px 0;
  border-radius: 16px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  border: 1px solid var(--ion-color-light-shade);
  overflow: hidden;
  background: var(--ion-background-color);
}

.profile-header {
  padding: 32px 16px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.avatar-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}

.large-avatar {
  width: 120px;
  height: 120px;
  border: 3px solid var(--ion-color-primary);
  overflow: hidden;
}

.profile-name {
  font-size: 1.5rem;
  font-weight: 600;
  margin: 0.5rem 0;
  color: var(--ion-color-dark);
}

.profile-email {
  color: var(--ion-color-medium);
  margin: 0;
  font-size: 0.95rem;
}

.avatar-actions {
  display: flex;
  gap: 8px;
  justify-content: center;
}

.upload-photo-btn {
  --padding-start: 12px;
  --padding-end: 12px;
  height: 36px;
  font-size: 0.9rem;
  --color: var(--ion-color-primary);
  text-transform: none;
}

.upload-photo-btn .camera-icon {
  margin-right: 8px;
  font-size: 1.1rem;
}

.remove-photo-btn {
  --padding-start: 12px;
  --padding-end: 12px;
  height: 36px;
  font-size: 0.9rem;
  --color: var(--ion-color-danger);
  text-transform: none;
}

.settings-sections {
  display: grid;
  gap: 16px;
}

.settings-section {
  margin: 0;
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
  display: flex;
  align-items: center;
  gap: 8px;
}

.section-icon {
  font-size: 1.4rem;
  color: var(--ion-color-primary);
}

ion-card-content {
  padding: 16px;
}

.settings-form {
  display: grid;
  gap: 16px;
}

ion-item {
  --background: var(--ion-color-light);
  --border-radius: 12px;
  margin: 0;
  --padding-start: 16px;
  --padding-end: 16px;
  border: 1px solid var(--ion-color-light-shade);
}

ion-input {
  --padding-start: 0;
  --padding-end: 0;
  margin-top: 8px;
}

ion-button {
  --border-radius: 12px;
  height: 48px;
  font-weight: 500;
  margin: 8px 0 0 0;
}

.security-options, 
.danger-actions {
  display: grid;
  gap: 12px;
}

.security-button {
  --background: var(--ion-color-light);
  --color: var(--ion-color-dark);
  --border-color: var(--ion-color-primary);
}

.danger-zone {
  border: 1px solid var(--ion-color-danger);
}

.danger-button {
  --background: transparent;
  --color: var(--ion-color-danger);
  --border-color: var(--ion-color-danger);
}

.logout-button {
  --background: var(--ion-color-light);
  --color: var(--ion-color-dark);
}

/* Password Modal Styles */
.password-modal {
  --background: var(--ion-background-color);
  --height: auto;
  --width: 90%;
  max-width: 400px;
  --border-radius: 16px;
  border: 1px solid var(--ion-color-light-shade);
}

.password-form {
  padding: 16px;
}

.form-group {
  margin-bottom: 16px;
}

.custom-input-item {
  --background: var(--ion-color-light);
  --border-radius: 8px;
  --padding-start: 12px;
  --padding-end: 12px;
  margin-bottom: 8px;
}

.error-message {
  color: var(--ion-color-danger);
  font-size: 0.8rem;
  margin-top: 4px;
  padding-left: 16px;
}

.submit-button {
  margin-top: 24px;
}

/* Responsive Design */
@media (max-width: 768px) {
  ion-content {
    --padding: 16px;
  }

  .profile-card,
  .settings-section {
    border-radius: 12px;
  }

  ion-button {
    height: 44px;
  }
}

@media (min-width: 768px) {
  ion-content {
    --padding: 24px;
  }

  .settings-sections {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .profile-card {
    grid-column: 1 / -1;
  }
  
  .danger-zone {
    grid-column: 1 / -1;
  }

  .settings-section:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
  }
}
</style>