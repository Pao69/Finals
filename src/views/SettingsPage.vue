<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-title>Settings</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      <div class="settings-container">
        <!-- Profile Section -->
        <div class="profile-section">
          <div class="profile-header">
            <div class="avatar-container">
              <ion-avatar>
                <img :src="profileImage" alt="Profile" @error="handleImageError" />
              </ion-avatar>
              <div class="avatar-actions">
                <label for="profile-picture" class="change-photo-btn">
                  <ion-icon :icon="cameraOutline" slot="icon-only"></ion-icon>
                  <input 
                    id="profile-picture" 
                    type="file" 
                    accept="image/*" 
                    @change="handleProfilePictureSelect"
                    class="file-input"
                  />
                </label>
                <ion-button 
                  fill="clear" 
                  color="danger" 
                  class="remove-photo-btn"
                  @click="deleteProfilePicture"
                  v-if="profileImage !== 'https://ionicframework.com/docs/img/demos/avatar.svg'"
                >
                  <ion-icon :icon="trashOutline" slot="icon-only"></ion-icon>
                </ion-button>
              </div>
            </div>
            <h2>{{ profile.username }}</h2>
            <p>{{ profile.email }}</p>
          </div>

          <!-- Preview Modal -->
          <ion-modal 
            :is-open="!!selectedImage" 
            @didDismiss="cancelProfilePicture"
            class="preview-modal">
            <ion-header>
              <ion-toolbar>
                <ion-title>Preview Profile Picture</ion-title>
                <ion-buttons slot="end">
                  <ion-button @click="cancelProfilePicture">
                    <ion-icon :icon="closeOutline"></ion-icon>
                  </ion-button>
                </ion-buttons>
              </ion-toolbar>
            </ion-header>

            <ion-content class="ion-padding">
              <div class="preview-content">
                <div class="preview-container">
                  <img :src="selectedImage || ''" alt="Preview" class="preview-image" />
                </div>
                <div class="preview-actions">
                  <ion-button expand="block" color="success" @click="saveProfilePicture">
                    <ion-icon :icon="saveOutline" slot="start"></ion-icon>
                    Save Profile Picture
                  </ion-button>
                </div>
              </div>
            </ion-content>
          </ion-modal>
        </div>

        <ion-list class="settings-list">
          <!-- Profile Settings -->
          <ion-item-group>
            <ion-item-divider>
              <ion-label>Profile Settings</ion-label>
            </ion-item-divider>

            <ion-item lines="full">
              <ion-icon :icon="personOutline" slot="start" color="primary"></ion-icon>
              <ion-label position="stacked">Username</ion-label>
              <ion-input 
                v-model="profile.username" 
                type="text" 
                placeholder="Enter username"
                class="custom-input"
              ></ion-input>
            </ion-item>

            <ion-item lines="full">
              <ion-icon :icon="mailOutline" slot="start" color="primary"></ion-icon>
              <ion-label position="stacked">Email</ion-label>
              <ion-input 
                v-model="profile.email" 
                type="email" 
                placeholder="Enter email"
                class="custom-input"
              ></ion-input>
            </ion-item>

            <ion-item lines="full">
              <ion-icon :icon="callOutline" slot="start" color="primary"></ion-icon>
              <ion-label position="stacked">Phone</ion-label>
              <ion-input 
                v-model="profile.phone" 
                type="tel" 
                placeholder="Enter phone"
                class="custom-input"
              ></ion-input>
            </ion-item>

            <div class="button-container">
              <ion-button 
                expand="block" 
                @click="updateProfile" 
                :disabled="!isProfileChanged"
                class="save-button"
              >
                <ion-icon :icon="saveOutline" slot="start"></ion-icon>
                Save Changes
              </ion-button>
            </div>
          </ion-item-group>

          <!-- Security Settings -->
          <ion-item-group>
            <ion-item-divider>
              <ion-label>Security</ion-label>
            </ion-item-divider>

            <ion-item button detail @click="openChangePasswordModal" lines="full">
              <ion-icon :icon="lockClosedOutline" slot="start" color="primary"></ion-icon>
              <ion-label>
                <h2>Change Password</h2>
                <p>Update your account password</p>
              </ion-label>
            </ion-item>
          </ion-item-group>

          <!-- Account Actions -->
          <ion-item-group>
            <ion-item-divider>
              <ion-label>Account Actions</ion-label>
            </ion-item-divider>

            <ion-item button @click="deleteProfile" lines="full" class="delete-button">
              <ion-icon :icon="trashOutline" slot="start" color="danger"></ion-icon>
              <ion-label color="danger">
                <h2>Delete Account</h2>
                <p>Permanently delete your account and all data</p>
              </ion-label>
            </ion-item>

            <ion-item button @click="handleLogout" lines="full" class="logout-button">
              <ion-icon :icon="logOutOutline" slot="start" color="danger"></ion-icon>
              <ion-label color="danger">Logout</ion-label>
            </ion-item>
          </ion-item-group>
        </ion-list>
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
                @input="updatePasswordStrength"
                class="custom-input"
              ></ion-input>
            </ion-item>
          </div>

          <div class="form-group">
            <ion-item lines="full" class="custom-input-item">
              <ion-icon :icon="checkmarkCircleOutline" slot="start" color="primary"></ion-icon>
              <ion-label position="floating">Confirm New Password</ion-label>
              <ion-input
                type="password"
                v-model="passwordForm.confirmPassword"
                required
                placeholder="Confirm new password"
                class="custom-input"
              ></ion-input>
            </ion-item>
          </div>

          <!-- Password Strength Meter -->
          <div class="password-strength-container" v-if="passwordForm.newPassword">
            <div class="strength-header">
              <span>Password Strength</span>
              <span class="strength-label" :class="passwordStrength.label">
                {{ passwordStrength.label }}
              </span>
            </div>
            <div class="strength-meter">
              <div 
                class="strength-meter-fill" 
                :style="{ width: passwordStrength.score + '%' }"
                :class="passwordStrength.label"
              ></div>
            </div>
          </div>

          <div class="password-requirements" v-if="passwordForm.newPassword">
            <h3>Password Requirements</h3>
            <div class="requirements-grid">
              <div class="requirement-item" :class="{ valid: hasMinLength }">
                <ion-icon :icon="hasMinLength ? checkmarkCircle : closeCircle"></ion-icon>
                <span>At least 8 characters</span>
              </div>
              <div class="requirement-item" :class="{ valid: hasUpperCase }">
                <ion-icon :icon="hasUpperCase ? checkmarkCircle : closeCircle"></ion-icon>
                <span>One uppercase letter</span>
              </div>
              <div class="requirement-item" :class="{ valid: hasLowerCase }">
                <ion-icon :icon="hasLowerCase ? checkmarkCircle : closeCircle"></ion-icon>
                <span>One lowercase letter</span>
              </div>
              <div class="requirement-item" :class="{ valid: hasNumber }">
                <ion-icon :icon="hasNumber ? checkmarkCircle : closeCircle"></ion-icon>
                <span>One number</span>
              </div>
              <div class="requirement-item" :class="{ valid: passwordsMatch }">
                <ion-icon :icon="passwordsMatch ? checkmarkCircle : closeCircle"></ion-icon>
                <span>Passwords match</span>
              </div>
            </div>
          </div>

          <ion-button 
            expand="block" 
            type="submit" 
            class="submit-button"
            :disabled="!isPasswordFormValid"
            :color="isPasswordFormValid ? 'primary' : 'medium'"
          >
            <ion-icon :icon="saveOutline" slot="start"></ion-icon>
            Update Password
          </ion-button>
        </form>
      </ion-content>
    </ion-modal>

    <!-- Image Picker Modal -->
    <ion-modal 
      :is-open="isImagePickerOpen" 
      @didDismiss="isImagePickerOpen = false"
      class="image-picker-modal">
      <ion-header>
        <ion-toolbar>
          <ion-title>Change Profile Picture</ion-title>
          <ion-buttons slot="end">
            <ion-button @click="isImagePickerOpen = false">
              <ion-icon :icon="closeOutline"></ion-icon>
            </ion-button>
          </ion-buttons>
        </ion-toolbar>
      </ion-header>

      <ion-content class="ion-padding">
        <ProfilePictureManager
          :current-image="profileImage"
          @update="handleProfileImageUpdate"
          @delete="handleProfileImageDelete"
        />
      </ion-content>
    </ion-modal>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import {
  IonPage, IonHeader, IonToolbar, IonTitle, IonContent,
  IonList, IonItem, IonItemGroup, IonItemDivider,
  IonLabel, IonButton, IonIcon, IonInput, IonModal,
  IonButtons, toastController, IonAvatar, alertController
} from '@ionic/vue';
import {
  personOutline, mailOutline, callOutline,
  lockClosedOutline, logOutOutline, saveOutline,
  closeOutline, checkmarkCircle, closeCircle,
  cameraOutline, cloudUploadOutline, keyOutline,
  checkmarkCircleOutline, trashOutline, warningOutline
} from 'ionicons/icons';
import ProfilePictureManager from '@/components/ProfilePictureManager.vue';

// Register components
const components = {
  IonPage, IonHeader, IonToolbar, IonTitle, IonContent,
  IonList, IonItem, IonItemGroup, IonItemDivider,
  IonLabel, IonButton, IonIcon, IonInput, IonModal,
  IonButtons, IonAvatar
};

const router = useRouter();
const isChangePasswordModalOpen = ref(false);
const isImagePickerOpen = ref(false);
const profileImage = ref('https://ionicframework.com/docs/img/demos/avatar.svg');

// Profile state
const originalProfile = ref({
  username: '',
  email: '',
  phone: ''
});

const profile = ref({
  username: '',
  email: '',
  phone: ''
});

// Password change form
const passwordForm = ref({
  currentPassword: '',
  newPassword: '',
  confirmPassword: ''
});

const passwordStrength = ref({
  score: 0,
  label: 'weak'
});

const selectedImage = ref<string | null>(null);

// Load user profile with image
onMounted(() => {
  const userData = localStorage.getItem('user');
  if (userData) {
    const user = JSON.parse(userData);
    originalProfile.value = {
      username: user.username,
      email: user.email,
      phone: user.phone
    };
    profile.value = { ...originalProfile.value };
    if (user.image_link) {
      profileImage.value = user.image_link;
    }
  }
});

// Computed properties for password validation
const hasMinLength = computed(() => passwordForm.value.newPassword.length >= 8);
const hasUpperCase = computed(() => /[A-Z]/.test(passwordForm.value.newPassword));
const hasLowerCase = computed(() => /[a-z]/.test(passwordForm.value.newPassword));
const hasNumber = computed(() => /[0-9]/.test(passwordForm.value.newPassword));
const passwordsMatch = computed(() => 
  passwordForm.value.newPassword === passwordForm.value.confirmPassword &&
  passwordForm.value.newPassword !== ''
);

const isProfileChanged = computed(() => {
  return JSON.stringify(profile.value) !== JSON.stringify(originalProfile.value);
});

const isPasswordFormValid = computed(() => {
  return hasMinLength.value &&
         hasUpperCase.value &&
         hasLowerCase.value &&
         hasNumber.value &&
         passwordsMatch.value &&
         passwordForm.value.currentPassword;
});

const handleImageError = (e: Event) => {
  const target = e.target as HTMLImageElement;
  target.src = 'https://ionicframework.com/docs/img/demos/avatar.svg';
};

const handleProfileImageUpdate = (imageUrl: string) => {
  profileImage.value = imageUrl;
  isImagePickerOpen.value = false;
  
  // Update local storage
  const userData = JSON.parse(localStorage.getItem('user') || '{}');
  userData.image_link = imageUrl;
  localStorage.setItem('user', JSON.stringify(userData));
};

const handleProfileImageDelete = () => {
  profileImage.value = 'https://ionicframework.com/docs/img/demos/avatar.svg';
  isImagePickerOpen.value = false;
  
  // Update local storage
  const userData = JSON.parse(localStorage.getItem('user') || '{}');
  userData.image_link = null;
  localStorage.setItem('user', JSON.stringify(userData));
};

const openImagePicker = () => {
  isImagePickerOpen.value = true;
};

const handleLogout = () => {
  localStorage.removeItem('user');
  localStorage.removeItem('token');
  router.push('/login');
};

const updateProfile = async () => {
  try {
    const token = localStorage.getItem('token');
    if (!token) {
      throw new Error('No authentication token found');
    }

    // Validate email format
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(profile.value.email)) {
      throw new Error('Please enter a valid email address');
    }

    // Validate phone format (optional)
    if (profile.value.phone && !/^\+?[\d\s-]{10,}$/.test(profile.value.phone)) {
      throw new Error('Please enter a valid phone number');
    }

    const response = await axios.post(
      'http://localhost/codes/PROJ/dbConnect/update_profile.php',
      profile.value,
      {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      }
    );

    if (response.data.success) {
      const userData = JSON.parse(localStorage.getItem('user') || '{}');
      const updatedUser = { ...userData, ...profile.value };
      localStorage.setItem('user', JSON.stringify(updatedUser));
      originalProfile.value = { ...profile.value };

      const toast = await toastController.create({
        message: 'Profile updated successfully',
        duration: 2000,
        color: 'success',
        position: 'top',
        icon: checkmarkCircle
      });
      await toast.present();
    }
  } catch (error: any) {
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

            const response = await axios.delete(
              'http://localhost/codes/PROJ/dbConnect/delete_profile.php',
              {
                headers: {
                  'Authorization': `Bearer ${token}`
                }
              }
            );

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
  if (!isPasswordFormValid.value) return;

  try {
    const token = localStorage.getItem('token');
    if (!token) {
      throw new Error('No authentication token found');
    }

    // Additional password validation
    if (passwordForm.value.newPassword === passwordForm.value.currentPassword) {
      throw new Error('New password must be different from current password');
    }

    const response = await axios.post(
      'http://localhost/codes/PROJ/dbConnect/change_password.php',
      {
        current_password: passwordForm.value.currentPassword,
        new_password: passwordForm.value.newPassword
      },
      {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      }
    );

    if (response.data.success) {
      isChangePasswordModalOpen.value = false;
      
      const toast = await toastController.create({
        message: 'Password changed successfully',
        duration: 3000,
        color: 'success',
        position: 'top',
        icon: checkmarkCircle
      });
      await toast.present();

      // Clear local storage and redirect to login
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      
      const reloginToast = await toastController.create({
        message: 'Please log in again with your new password',
        duration: 3000,
        color: 'warning',
        position: 'top',
        icon: warningOutline
      });
      await reloginToast.present();

      // Redirect to login after a short delay
      setTimeout(() => {
        router.push('/login');
      }, 2000);
    }
  } catch (error: any) {
    const toast = await toastController.create({
      message: error.response?.data?.message || error.message || 'Failed to change password',
      duration: 3000,
      color: 'danger',
      position: 'top',
      icon: closeCircle
    });
    await toast.present();
  }
};

const updatePasswordStrength = () => {
  const password = passwordForm.value.newPassword;
  let score = 0;
  let label = 'weak';

  // Calculate password strength
  if (password.length >= 8) score += 20;
  if (/[A-Z]/.test(password)) score += 20;
  if (/[a-z]/.test(password)) score += 20;
  if (/[0-9]/.test(password)) score += 20;
  if (/[^A-Za-z0-9]/.test(password)) score += 20;

  // Set label based on score
  if (score >= 80) label = 'strong';
  else if (score >= 60) label = 'good';
  else if (score >= 40) label = 'medium';
  else if (score >= 20) label = 'weak';

  passwordStrength.value = { score, label };
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
    
    // Create a unique filename
    const timestamp = new Date().getTime();
    const filename = `profile_${timestamp}_${file.name}`;
    
    // Create a FileReader to preview the image
    const reader = new FileReader();
    reader.onload = (e) => {
      selectedImage.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);
  }
};

const saveProfilePicture = async () => {
  if (!selectedImage.value) return;

  try {
    const token = localStorage.getItem('token');
    if (!token) {
      throw new Error('No authentication token found');
    }

    // Create FormData for file upload
    const formData = new FormData();
    formData.append('profile_image', selectedImage.value);
    formData.append('action', 'update_profile_image');

    const response = await axios.post(
      'http://localhost/codes/PROJ/dbConnect/upload_profile.php',
      formData,
      {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'multipart/form-data'
        }
      }
    );

    if (response.data.success) {
      // Update the profile image with the new URL
      const imageUrl = `/images/pfp/${response.data.filename}`;
      profileImage.value = imageUrl;
      selectedImage.value = null;
      
      // Update local storage
      const userData = JSON.parse(localStorage.getItem('user') || '{}');
      userData.image_link = imageUrl;
      localStorage.setItem('user', JSON.stringify(userData));

      const toast = await toastController.create({
        message: 'Profile picture updated successfully',
        duration: 2000,
        color: 'success',
        position: 'top',
        icon: checkmarkCircle
      });
      await toast.present();
    }
  } catch (error: any) {
    const toast = await toastController.create({
      message: error.response?.data?.message || 'Failed to update profile picture',
      duration: 2000,
      color: 'danger',
      position: 'top',
      icon: closeCircle
    });
    await toast.present();
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
            const token = localStorage.getItem('token');
            if (!token) {
              throw new Error('No authentication token found');
            }

            const response = await axios.post(
              'http://localhost/codes/PROJ/dbConnect/delete_profile_picture.php',
              {},
              {
                headers: {
                  'Authorization': `Bearer ${token}`,
                  'Content-Type': 'application/json'
                }
              }
            );

            if (response.data.success) {
              // Reset to default avatar
              profileImage.value = 'https://ionicframework.com/docs/img/demos/avatar.svg';
              
              // Update local storage
              const userData = JSON.parse(localStorage.getItem('user') || '{}');
              userData.image_link = null;
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
          } catch (error: any) {
            const toast = await toastController.create({
              message: error.response?.data?.message || 'Failed to remove profile picture',
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

const cancelProfilePicture = () => {
  selectedImage.value = null;
};
</script>

<style scoped>
.settings-container {
  max-width: 600px;
  margin: 0 auto;
}

.profile-section {
  text-align: center;
  padding: 2rem 0;
  margin-bottom: 1rem;
}

.profile-header {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
}

.profile-header h2 {
  margin: 0.5rem 0 0;
  font-size: 1.5rem;
  font-weight: 600;
}

.profile-header p {
  margin: 0;
  color: var(--ion-color-medium);
}

ion-avatar {
  width: 100px;
  height: 100px;
  margin-bottom: 0.5rem;
}

.settings-list {
  background: transparent;
}

ion-item-divider {
  --background: transparent;
  --color: var(--ion-color-medium);
  font-weight: 600;
  text-transform: uppercase;
  font-size: 0.8rem;
  letter-spacing: 0.05rem;
  margin-top: 1rem;
}

ion-item {
  --padding-start: 0;
  margin: 0.5rem 0;
  --background: var(--ion-background-color);
  --border-color: var(--ion-color-light);
}

.custom-input {
  --padding-start: 0;
}

.button-container {
  padding: 1rem;
}

.save-button {
  margin: 0;
}

.logout-button {
  --background: var(--ion-color-danger-light);
}

.password-modal {
  --height: auto;
  --width: 100%;
  --max-width: 500px;
  --border-radius: 16px;
}

.password-form {
  padding: 1rem;
}

.form-group {
  margin-bottom: 1rem;
}

.custom-input-item {
  --background: var(--ion-color-light);
  --border-radius: 8px;
  --padding-start: 1rem;
  --padding-end: 1rem;
  --inner-padding-end: 0;
  margin-bottom: 0.5rem;
}

.custom-input-item ion-icon {
  font-size: 1.2rem;
  margin-right: 0.5rem;
}

.password-strength-container {
  background: var(--ion-color-light);
  padding: 1rem;
  border-radius: 8px;
  margin: 1rem 0;
}

.strength-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
  color: var(--ion-color-medium);
}

.strength-meter {
  height: 6px;
  background-color: var(--ion-color-light-shade);
  border-radius: 3px;
  overflow: hidden;
}

.strength-meter-fill {
  height: 100%;
  transition: all 0.3s ease;
  border-radius: 3px;
}

.strength-meter-fill.weak {
  background-color: var(--ion-color-danger);
}

.strength-meter-fill.medium {
  background-color: var(--ion-color-warning);
}

.strength-meter-fill.good {
  background-color: var(--ion-color-success-shade);
}

.strength-meter-fill.strong {
  background-color: var(--ion-color-success);
}

.strength-label {
  font-weight: 600;
  text-transform: capitalize;
}

.strength-label.weak {
  color: var(--ion-color-danger);
}

.strength-label.medium {
  color: var(--ion-color-warning);
}

.strength-label.good {
  color: var(--ion-color-success-shade);
}

.strength-label.strong {
  color: var(--ion-color-success);
}

.password-requirements {
  background: var(--ion-color-light);
  padding: 1.5rem;
  border-radius: 8px;
  margin: 1rem 0;
}

.password-requirements h3 {
  margin: 0 0 1rem 0;
  font-size: 1rem;
  color: var(--ion-color-medium);
}

.requirements-grid {
  display: grid;
  gap: 0.75rem;
}

.requirement-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--ion-color-medium);
  font-size: 0.9rem;
  transition: color 0.3s ease;
}

.requirement-item.valid {
  color: var(--ion-color-success);
}

.requirement-item ion-icon {
  font-size: 1.2rem;
}

.submit-button {
  margin-top: 1.5rem;
  --border-radius: 8px;
  --padding-top: 1rem;
  --padding-bottom: 1rem;
}

@media (min-width: 768px) {
  .password-modal {
    --height: auto;
    --width: 500px;
  }
}

.avatar-container {
  position: relative;
  margin-bottom: 0.5rem;
}

.avatar-actions {
  position: absolute;
  bottom: 0;
  right: 0;
  display: flex;
  gap: 0.5rem;
}

.change-photo-btn {
  position: relative;
  padding: 0.5rem;
  background: var(--ion-color-primary);
  border-radius: 50%;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  margin: 0;
}

.remove-photo-btn {
  --padding-start: 0.5rem;
  --padding-end: 0.5rem;
  --background: var(--ion-color-danger);
  --border-radius: 50%;
  width: 32px;
  height: 32px;
}

.change-photo-btn ion-icon,
.remove-photo-btn ion-icon {
  font-size: 1.2rem;
  color: white;
}

.file-input {
  display: none;
}

.preview-modal {
  --height: auto;
  --width: 100%;
  --max-width: 400px;
  --border-radius: 16px;
}

.preview-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1.5rem;
  padding: 1rem;
}

.preview-container {
  width: 200px;
  height: 200px;
  border-radius: 50%;
  overflow: hidden;
  border: 2px solid var(--ion-color-light);
}

.preview-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.preview-actions {
  width: 100%;
  max-width: 300px;
}

@media (min-width: 768px) {
  .preview-modal {
    --height: auto;
    --width: 400px;
  }
}

.delete-button {
  --background: var(--ion-color-danger-light);
  margin-bottom: 0.5rem;
}

.delete-button ion-label h2 {
  font-weight: 600;
}

.delete-button ion-label p {
  font-size: 0.8rem;
  opacity: 0.8;
}
</style>