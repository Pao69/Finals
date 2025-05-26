<template>
  <page-layout title="Settings" :show-back-button="false">
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
            </div>
            <div class="profile-info">
              <h2 class="profile-name">{{ profile.username }}</h2>
              <p class="profile-email">{{ profile.email }}</p>
            </div>
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
                    class="custom-input"
                    @ionInput="(e: CustomEvent) => validateField('username', (e.target as HTMLInputElement).value || '')"
                    :class="{ 'has-error': validationErrors.username }">
                  </ion-input>
                  <ion-note color="danger" v-if="validationErrors.username">
                    {{ validationErrors.username }}
                  </ion-note>
                </ion-item>
                <ion-item>
                  <ion-label position="stacked">Email</ion-label>
                  <ion-input 
                    v-model="profile.email" 
                    type="email" 
                    placeholder="Enter email"
                    class="custom-input"
                    @ionInput="(e: CustomEvent) => validateField('email', (e.target as HTMLInputElement).value || '')"
                    :class="{ 'has-error': validationErrors.email }">
                  </ion-input>
                  <ion-note color="danger" v-if="validationErrors.email">
                    {{ validationErrors.email }}
                  </ion-note>
                </ion-item>
                <ion-item>
                  <ion-label position="stacked">Phone</ion-label>
                  <ion-input 
                    v-model="profile.phone" 
                    type="tel" 
                    placeholder="Enter phone (e.g., +639123456789)"
                    class="custom-input"
                    @ionInput="(e: CustomEvent) => validateField('phone', (e.target as HTMLInputElement).value || '')"
                    :class="{ 'has-error': validationErrors.phone }">
                  </ion-input>
                  <ion-note color="danger" v-if="validationErrors.phone">
                    {{ validationErrors.phone }}
                  </ion-note>
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
  </page-layout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/utils/api';
import axios from 'axios';
import {
  IonContent,
  IonList, IonItem, IonItemGroup, IonItemDivider,
  IonLabel, IonButton, IonIcon, IonInput, IonModal,
  IonButtons, toastController, IonAvatar, alertController,
  loadingController,
  IonPage,
  IonHeader,
  IonToolbar,
  IonTitle,
  IonCard,
  IonCardHeader,
  IonCardTitle,
  IonCardContent
} from '@ionic/vue';
import {
  personOutline,
  logOutOutline, 
  saveOutline,
  closeOutline, 
  closeCircle, 
  checkmarkCircle,
  cameraOutline, 
  checkmarkCircleOutline, 
  trashOutline, 
  warningOutline,
  refreshOutline
} from 'ionicons/icons';
import PageLayout from '@/components/PageLayout.vue';

// Register components
const components = {
  IonContent,
  IonList, IonItem, IonItemGroup, IonItemDivider,
  IonLabel, IonButton, IonIcon, IonInput, IonModal,
  IonButtons, IonAvatar,
  IonPage,
  IonHeader,
  IonToolbar,
  IonTitle,
  IonCard,
  IonCardHeader,
  IonCardTitle,
  IonCardContent
};

const router = useRouter();

// Profile state
const profileImage = ref('https://ionicframework.com/docs/img/demos/avatar.svg');
const isImageLoading = ref(false);

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

// Computed properties
const isProfileChanged = computed(() => {
  return JSON.stringify(profile.value) !== JSON.stringify(originalProfile.value);
});

// Function to update profile image everywhere
const updateProfileImageEverywhere = (imageUrl: string) => {
  // Update the local state
  profileImage.value = imageUrl;
  
  // Update storage
  const storage = localStorage.getItem('user') ? localStorage : sessionStorage;
  const userData = JSON.parse(storage.getItem('user') || '{}');
  userData.pfp = imageUrl;
  storage.setItem('user', JSON.stringify(userData));
};

// Enhanced fetchProfilePicture function
const fetchProfilePicture = async () => {
  try {
    isImageLoading.value = true;
    
    // First check storage for cached profile picture
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
      const imageUrl = response.data.image_link;
      updateProfileImageEverywhere(imageUrl);
    }
  } catch (error) {
    console.error('Failed to fetch profile picture:', error);
    if (!profileImage.value) {
      profileImage.value = 'https://ionicframework.com/docs/img/demos/avatar.svg';
    }
  } finally {
    isImageLoading.value = false;
  }
};

// Modified handleProfilePictureSelect
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
      // Show loading indicator
      const loading = await loadingController.create({
        message: 'Uploading...',
        duration: 3000
      });
      await loading.present();

      // Create FormData for file upload
      const formData = new FormData();
      formData.append('profile_image', file);

      // Use the consistent API instance with FormData
      const response = await api.post('/profile_picture.php', formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      });

      if (response.data.success) {
        // Construct the full URL for the profile image
        const baseUrl = import.meta.env.VITE_API_URL || 'http://localhost';
        const imageUrl = response.data.image_link.startsWith('http') 
          ? response.data.image_link 
          : `${baseUrl}${response.data.image_link}`;
        
        // Update profile image everywhere
        updateProfileImageEverywhere(imageUrl);

        // Dismiss loading indicator
        await loading.dismiss();

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
        
        // Fetch the updated profile picture
        await fetchProfilePicture();
      } else {
        await loading.dismiss();
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

// Modified deleteProfilePicture
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
            const loading = await loadingController.create({
              message: 'Removing...',
              duration: 2000
            });
            await loading.present();

            const response = await api.delete('/profile_picture.php');

            if (response.data.success) {
              const defaultAvatar = 'https://ionicframework.com/docs/img/demos/avatar.svg';
              updateProfileImageEverywhere(defaultAvatar);

              await loading.dismiss();
              const toast = await toastController.create({
                message: 'Profile picture removed successfully',
                duration: 2000,
                color: 'success',
                position: 'top',
                icon: checkmarkCircle
              });
              await toast.present();
              
              // Fetch the updated profile picture
              await fetchProfilePicture();
            } else {
              await loading.dismiss();
              const toast = await toastController.create({
                message: 'Failed to remove profile picture',
                duration: 2000,
                color: 'danger',
                position: 'top',
                icon: closeCircle
              });
              await toast.present();
            }
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
          }
        }
      }
    ]
  });

  await alert.present();
};

// Enhanced onMounted setup
onMounted(async () => {
  try {
    // Get user data from the storage that contains it
    const storage = localStorage.getItem('user') ? localStorage : sessionStorage;
    const userData = storage.getItem('user');
    
    if (userData) {
      const user = JSON.parse(userData);
      
      // Fetch latest user data from the server
      const response = await api.get('/profile.php');
      if (response.data.success) {
        const serverUser = response.data.user;
        originalProfile.value = {
          username: serverUser.username || user.username || '',
          email: serverUser.email || user.email || '',
          phone: serverUser.phone || user.phone || '',
          role: serverUser.role || user.role || 'user'
        };
        profile.value = { ...originalProfile.value };
      } else {
        originalProfile.value = {
          username: user.username || '',
          email: user.email || '',
          phone: user.phone || '',
          role: user.role || 'user'
        };
        profile.value = { ...originalProfile.value };
      }
      
      // Set initial profile image if it exists in user data
      if (user.pfp) {
        profileImage.value = user.pfp;
      }
      
      // Fetch current profile picture
      await fetchProfilePicture();
    } else {
      router.push('/login');
    }
  } catch (error) {
    console.error('Error loading profile:', error);
    const toast = await toastController.create({
      message: 'Failed to load profile data',
      duration: 2000,
      color: 'danger'
    });
    await toast.present();
  }
});

// Add watch effect for profile image changes
watch(profileImage, (newValue) => {
  if (newValue && newValue !== 'https://ionicframework.com/docs/img/demos/avatar.svg') {
    // Pre-load the image to ensure it's in the browser cache
    const img = new Image();
    img.src = newValue;
  }
}, { immediate: true });

const handleImageError = (e: Event) => {
  const target = e.target as HTMLImageElement;
  target.src = 'https://ionicframework.com/docs/img/demos/avatar.svg';
};

const handleLogout = async () => {
  // Clear both storages to ensure clean state
  localStorage.clear();
  sessionStorage.clear();
  
  // Clear axios default headers
  delete axios.defaults.headers.common['Authorization'];
  
  // Show loading indicator
  const loading = await loadingController.create({
    message: 'Logging out...',
    duration: 1000
  });
  await loading.present();
  
  // Use router.replace instead of push to clear navigation history
  setTimeout(() => {
    router.replace('/login');
  }, 100);
};

// Add validation functions
type ValidationField = 'username' | 'email' | 'phone';

const validationRules: Record<ValidationField, RegExp> = {
  username: /^[a-zA-Z0-9_]{3,20}$/,
  email: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.(com|net|org|edu|gov|mil|biz|info|io|co\.uk|co\.in|com\.au|edu\.ph)$/,
  phone: /^(\+63|0)[0-9]{10}$/
};

const validationMessages: Record<ValidationField, string> = {
  username: 'Username must be 3-20 characters long and can only contain letters, numbers, and underscores',
  email: 'Please enter a valid email address with a valid domain (e.g., .com, .net, .org)',
  phone: 'Please enter a valid phone number (e.g., +639123456789 or 09123456789)'
};

const validationErrors = ref<Record<ValidationField, string>>({
  username: '',
  email: '',
  phone: ''
});

const validateField = (field: ValidationField, value: string) => {
  if (!value || !validationRules[field].test(value)) {
    validationErrors.value[field] = validationMessages[field];
    return false;
  }
  validationErrors.value[field] = '';
  return true;
};

// Update profile function
const updateProfile = async () => {
  try {
    // Validate all fields
    let isValid = true;
    (Object.keys(validationRules) as ValidationField[]).forEach(field => {
      if (!validateField(field, profile.value[field] || '')) {
        isValid = false;
      }
    });

    if (!isValid) {
      const toast = await toastController.create({
        message: 'Please fix the validation errors',
        duration: 3000,
        color: 'danger',
        position: 'top'
      });
      await toast.present();
      return;
    }

    // Show loading
    const loading = await loadingController.create({
      message: 'Updating profile...',
      duration: 3000
    });
    await loading.present();

    const response = await api.post('/user_profile.php', {
      username: profile.value.username,
      email: profile.value.email,
      phone: profile.value.phone
    });

    await loading.dismiss();

    if (response.data.success) {
      // Update local storage/session storage
      const storage = localStorage.getItem('user') ? localStorage : sessionStorage;
      const userData = JSON.parse(storage.getItem('user') || '{}');
      Object.assign(userData, {
        username: profile.value.username,
        email: profile.value.email,
        phone: profile.value.phone
      });
      storage.setItem('user', JSON.stringify(userData));

      // Update original profile to match current
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
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  border: 1px solid var(--ion-color-light-shade);
  overflow: hidden;
  background: var(--ion-card-background);
}

.profile-header {
  padding: 32px 16px;
  display: flex;
  align-items: center;
  gap: 24px;
  background: var(--ion-background-color);
}

.avatar-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}

.large-avatar {
  width: 100px;
  height: 100px;
  border: 3px solid var(--ion-color-primary);
  overflow: hidden;
}

.profile-info {
  flex: 1;
}

.profile-name {
  font-size: 1.5rem;
  font-weight: 600;
  margin: 0;
  color: var(--ion-text-color);
}

.profile-email {
  color: var(--ion-color-medium);
  margin: 4px 0 0 0;
  font-size: 0.95rem;
}

.avatar-actions {
  display: flex;
  gap: 8px;
  margin-top: 8px;
}

.upload-photo-btn,
.remove-photo-btn {
  --padding-start: 12px;
  --padding-end: 12px;
  height: 36px;
  font-size: 0.9rem;
  text-transform: none;
  --border-radius: 10px;
}

.upload-photo-btn {
  --background: var(--ion-color-primary);
  --color: var(--ion-color-primary-contrast);
}

.remove-photo-btn {
  --background: transparent;
  --color: var(--ion-color-danger);
  --border-color: var(--ion-color-danger);
}

.settings-sections {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.settings-section {
  margin: 0;
  border-radius: 16px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  border: 1px solid var(--ion-color-light-shade);
  overflow: hidden;
  background: var(--ion-card-background);
}

ion-card-header {
  padding: 16px;
  border-bottom: 1px solid var(--ion-color-light-shade);
  background: var(--ion-background-color);
}

ion-card-title {
  font-size: 1.2rem;
  font-weight: 600;
  color: var(--ion-text-color);
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
  display: flex;
  flex-direction: column;
  gap: 16px;
}

ion-item {
  --background: var(--ion-background-color);
  --border-radius: 10px;
  margin: 0;
  --padding-start: 16px;
  --padding-end: 16px;
  border: 2px solid var(--ion-color-light-shade);
}

ion-item:focus-within {
  border-color: var(--ion-color-primary);
}

ion-input {
  --padding-start: 0;
  --padding-end: 0;
  margin-top: 8px;
  --placeholder-color: var(--ion-color-medium);
  --color: var(--ion-text-color);
}

ion-button {
  --border-radius: 10px;
  height: 44px;
  font-weight: 500;
  margin: 8px 0 0 0;
}

.danger-zone {
  border: 1px solid var(--ion-color-danger);
  margin-top: 16px;
}

.danger-button {
  --background: transparent;
  --color: var(--ion-color-danger);
  --border-color: var(--ion-color-danger);
}

.logout-button {
  --background: var(--ion-color-medium);
  --color: var(--ion-color-light);
  margin-top: 12px;
}

.password-requirements {
  margin-top: 8px;
  font-size: 0.9rem;
  padding-left: 16px;
}

.password-requirements p {
  color: var(--ion-color-danger);
  margin: 4px 0;
}

.password-requirements p.valid {
  color: var(--ion-color-success);
}

.error-message {
  color: var(--ion-color-danger);
  font-size: 0.8rem;
  margin-top: 4px;
  padding-left: 16px;
  margin-bottom: 8px;
}

.save-button {
  margin-top: 16px;
}

/* Responsive Design */
@media (max-width: 768px) {
  .profile-header {
    flex-direction: column;
    text-align: center;
    padding: 24px 16px;
  }

  .profile-info {
    text-align: center;
  }

  .avatar-actions {
    justify-content: center;
  }

  .settings-container {
    padding: 16px;
  }

  .profile-card,
  .settings-section {
    margin-bottom: 16px;
  }

  ion-button {
    height: 42px;
  }
}

@media (min-width: 768px) {
  .settings-container {
    padding: 24px;
  }

  .settings-section:hover {
    transform: none;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }
}

.password-actions {
  margin-top: 16px;
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}

.action-button {
  margin: 0;
  height: 42px;
  --box-shadow: none;
}

@media (max-width: 480px) {
  .password-actions {
    grid-template-columns: 1fr;
  }
}

.inputForm {
  position: relative;
  width: 100%;
}

.toggle-password {
  position: absolute;
  right: 15px;
  top: 50%;
  transform: translateY(-50%);
  cursor: pointer;
  user-select: none;
  z-index: 1;
}

.has-error {
  --border-color: var(--ion-color-danger);
}

ion-note {
  font-size: 0.8rem;
  padding: 4px 0;
}

.custom-input {
  margin-top: 4px;
}

ion-item {
  --padding-bottom: 8px;
}
</style>