<template>
  <ion-page>
    <ion-header>
      <ion-toolbar class="dark-toolbar">
        <ion-title>Settings</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding custom-content dark-theme">
      <div class="settings-container">
        <!-- Profile Card -->
        <div class="profile-card">
          <div class="profile-header">
            <div class="avatar-container">
              <ion-avatar class="large-avatar">
                <img 
                  :src="profileImage" 
                  alt="Profile" 
                  @error="handleImageError"
                  class="profile-avatar-image"
                />
              </ion-avatar>
              <div class="avatar-actions">
                <div class="upload-btn-wrapper">
                  <button class="change-photo-btn">
                    <ion-icon :icon="cameraOutline" class="camera-icon"></ion-icon>
                  </button>
                  <input 
                    type="file" 
                    id="profile-picture"
                    accept="image/*" 
                    @change="handleProfilePictureSelect"
                    class="file-input"
                  />
                </div>
                <ion-button 
                  fill="clear" 
                  class="remove-photo-btn"
                  @click="deleteProfilePicture"
                  v-if="profileImage !== 'https://ionicframework.com/docs/img/demos/avatar.svg'"
                >
                  <ion-icon :icon="trashOutline" class="trash-icon"></ion-icon>
                </ion-button>
              </div>
            </div>
            <h2 class="profile-name">{{ profile.username }}</h2>
            <p class="profile-email">{{ profile.email }}</p>
          </div>
        </div>

        <!-- Settings Sections -->
        <div class="settings-sections">
          <!-- Profile Information -->
          <div class="settings-section">
            <h3 class="section-title">
              <ion-icon :icon="personOutline" class="section-icon"></ion-icon>
              Profile Information
            </h3>
            <div class="settings-form">
              <div class="form-group">
                <label>Username</label>
                <ion-input 
                  v-model="profile.username" 
                  type="text" 
                  placeholder="Enter username"
                  class="custom-input"
                ></ion-input>
              </div>
              <div class="form-group">
                <label>Email</label>
                <ion-input 
                  v-model="profile.email" 
                  type="email" 
                  placeholder="Enter email"
                  class="custom-input"
                ></ion-input>
              </div>
              <div class="form-group">
                <label>Phone</label>
                <ion-input 
                  v-model="profile.phone" 
                  type="tel" 
                  placeholder="Enter phone"
                  class="custom-input"
                ></ion-input>
              </div>
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
          </div>

          <!-- Security Settings -->
          <div class="settings-section">
            <h3 class="section-title">
              <ion-icon :icon="lockClosedOutline" class="section-icon"></ion-icon>
              Security
            </h3>
            <div class="security-options">
              <ion-button 
                expand="block" 
                fill="outline" 
                @click="openChangePasswordModal"
                class="security-button"
              >
                <ion-icon :icon="keyOutline" slot="start"></ion-icon>
                Change Password
              </ion-button>
            </div>
          </div>

          <!-- Account Actions -->
          <div class="settings-section danger-zone">
            <h3 class="section-title">
              <ion-icon :icon="warningOutline" class="section-icon"></ion-icon>
              Account Actions
            </h3>
            <div class="danger-actions">
              <ion-button 
                expand="block" 
                color="danger" 
                fill="outline"
                @click="deleteProfile"
                class="danger-button"
              >
                <ion-icon :icon="trashOutline" slot="start"></ion-icon>
                Delete Account
              </ion-button>
              <ion-button 
                expand="block" 
                color="medium" 
                @click="handleLogout"
                class="logout-button"
              >
                <ion-icon :icon="logOutOutline" slot="start"></ion-icon>
                Logout
              </ion-button>
            </div>
          </div>
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
  </ion-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/utils/api';
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

// Register components
const components = {
  IonPage, IonHeader, IonToolbar, IonTitle, IonContent,
  IonList, IonItem, IonItemGroup, IonItemDivider,
  IonLabel, IonButton, IonIcon, IonInput, IonModal,
  IonButtons, IonAvatar
};

const router = useRouter();
const isChangePasswordModalOpen = ref(false);
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

// Load user profile with image
onMounted(async () => {
  const userData = localStorage.getItem('user');
  if (userData) {
    const user = JSON.parse(userData);
    originalProfile.value = {
      username: user.username,
      email: user.email,
      phone: user.phone
    };
    profile.value = { ...originalProfile.value };
    
    // Fetch current profile picture
    await fetchProfilePicture();
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
      message: error.message || 'Failed to update profile',
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

    const response = await api.post('/change_password.php', {
      current_password: passwordForm.value.currentPassword,
      new_password: passwordForm.value.newPassword
    });

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

const fetchProfilePicture = async () => {
  try {
    const token = localStorage.getItem('token');
    if (!token) return;

    const response = await api.get('/profile_picture.php');

    if (response.data.success && response.data.image_link) {
      profileImage.value = response.data.image_link;
      
      // Update local storage
      const userData = JSON.parse(localStorage.getItem('user') || '{}');
      userData.pfp = response.data.image_link;
      localStorage.setItem('user', JSON.stringify(userData));
    }
  } catch (error) {
    console.error('Failed to fetch profile picture:', error);
  }
};
</script>

<style scoped>
.dark-toolbar {
  --background: #1a1a1a;
  --color: #ffffff;
}

.custom-content {
  --background: #121212;
}

.settings-container {
  max-width: 800px;
  margin: 0 auto;
  padding: 1rem;
}

.profile-card {
  background: #1e1e1e;
  border-radius: 20px;
  padding: 2rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
  margin-bottom: 2rem;
  border: 1px solid #2a2a2a;
}

.profile-header {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.large-avatar {
  width: 120px;
  height: 120px;
  margin-bottom: 1rem;
  border: 3px solid var(--ion-color-primary);
}

.profile-name {
  font-size: 1.8rem;
  font-weight: 600;
  margin: 0.5rem 0;
  color: #ffffff;
}

.profile-email {
  color: #888888;
  margin: 0;
}

.avatar-container {
  position: relative;
  width: 120px;
  height: 120px;
  margin: 0 auto 1rem;
}

.avatar-actions {
  position: absolute;
  bottom: -5px;
  right: -5px;
  display: flex;
  gap: 0.5rem;
  z-index: 1;
}

.upload-btn-wrapper {
  position: relative;
  overflow: hidden;
  display: inline-block;
}

.change-photo-btn {
  background: var(--ion-color-primary);
  border-radius: 50%;
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  border: 2px solid #1e1e1e;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
  padding: 0;
  margin: 0;
}

.file-input {
  font-size: 100px;
  position: absolute;
  left: 0;
  top: 0;
  opacity: 0;
  right: 0;
  bottom: 0;
  cursor: pointer;
  width: 100%;
  height: 100%;
}

.camera-icon {
  color: white;
  font-size: 1.2rem;
}

.remove-photo-btn {
  --padding-start: 0.5rem;
  --padding-end: 0.5rem;
  --background: var(--ion-color-danger);
  --border-radius: 50%;
  width: 36px;
  height: 36px;
  margin: 0;
}

.settings-sections {
  display: grid;
  gap: 1.5rem;
}

.settings-section {
  background: #1e1e1e;
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
  border: 1px solid #2a2a2a;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1.2rem;
  font-weight: 600;
  margin: 0 0 1.5rem 0;
  color: #ffffff;
}

.section-icon {
  font-size: 1.4rem;
  color: var(--ion-color-primary);
}

.settings-form {
  display: grid;
  gap: 1rem;
}

.form-group {
  display: grid;
  gap: 0.5rem;
}

.form-group label {
  font-size: 0.9rem;
  font-weight: 500;
  color: #888888;
}

.custom-input {
  --background: #2a2a2a;
  --color: #ffffff;
  --padding-start: 1rem;
  --padding-end: 1rem;
  --border-radius: 10px;
  --placeholder-color: #666666;
  border: 1px solid #333333;
}

.save-button {
  margin-top: 1rem;
  --border-radius: 10px;
  --background: var(--ion-color-primary);
}

.security-options, .danger-actions {
  display: grid;
  gap: 1rem;
}

.security-button {
  --border-radius: 10px;
  --background: #2a2a2a;
  --color: #ffffff;
  --border-color: var(--ion-color-primary);
}

.danger-zone {
  border: 1px solid var(--ion-color-danger);
  background: #1e1e1e;
}

.danger-button {
  --border-radius: 10px;
  --background: transparent;
  --color: var(--ion-color-danger);
  --border-color: var(--ion-color-danger);
}

.logout-button {
  --border-radius: 10px;
  --background: #2a2a2a;
  --color: #ffffff;
}

/* Password Modal Styles */
.password-modal {
  --background: #1a1a1a;
  --height: auto;
  --width: 100%;
  --max-width: 500px;
  --border-radius: 16px;
}

.password-form {
  padding: 1rem;
  color: #ffffff;
}

.custom-input-item {
  --background: #2a2a2a;
  --color: #ffffff;
  --border-radius: 8px;
  --padding-start: 1rem;
  --padding-end: 1rem;
  --inner-padding-end: 0;
  margin-bottom: 0.5rem;
  border: 1px solid #333333;
}

.password-strength-container {
  background: #2a2a2a;
  padding: 1rem;
  border-radius: 8px;
  margin: 1rem 0;
  border: 1px solid #333333;
}

.strength-header {
  color: #888888;
}

.strength-meter {
  background-color: #333333;
}

.password-requirements {
  background: #2a2a2a;
  border: 1px solid #333333;
}

.password-requirements h3 {
  color: #888888;
}

.requirement-item {
  color: #888888;
}

.requirement-item.valid {
  color: var(--ion-color-success);
}

/* Responsive Design */
@media (min-width: 768px) {
  .settings-sections {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .profile-card {
    grid-column: 1 / -1;
  }
  
  .danger-zone {
    grid-column: 1 / -1;
  }
}

@media (max-width: 767px) {
  .settings-container {
    padding: 0.5rem;
  }
  
  .profile-card, .settings-section {
    padding: 1.25rem;
  }
}

/* Dark theme overrides for modals */
ion-modal {
  --ion-background-color: #1a1a1a;
  --ion-toolbar-background: #1e1e1e;
  --ion-toolbar-color: #ffffff;
}

ion-modal ion-content {
  --background: #1a1a1a;
}

ion-modal ion-item {
  --background: #2a2a2a;
  --color: #ffffff;
  --border-color: #333333;
}

ion-modal ion-input {
  --color: #ffffff;
  --placeholder-color: #666666;
}

/* Additional dark theme styles */
.preview-modal {
  --background: #1a1a1a;
}

.preview-container {
  border-color: #333333;
}

.preview-content {
  background: #1a1a1a;
}

/* Ensure text inputs are visible in dark theme */
input, textarea {
  color: #ffffff;
  background: #2a2a2a;
}

/* Style scrollbars for dark theme */
::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

::-webkit-scrollbar-track {
  background: #1a1a1a;
}

::-webkit-scrollbar-thumb {
  background: #333333;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: #444444;
}
</style>