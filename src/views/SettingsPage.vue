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
            </div>
            <h2>{{ profile.username }}</h2>
            <p>{{ profile.email }}</p>
          </div>

          <!-- Preview Modal -->
          <ion-modal 
            :is-open="!!selectedImage" 
            @didDismiss="cancelProfilePicture"
            class="preview-modal"
          >
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
      class="password-modal"
    >
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
          <ion-item lines="full">
            <ion-label position="floating">Current Password</ion-label>
            <ion-input
              type="password"
              v-model="passwordForm.currentPassword"
              required
              placeholder="Enter current password"
            ></ion-input>
          </ion-item>

          <ion-item lines="full">
            <ion-label position="floating">New Password</ion-label>
            <ion-input
              type="password"
              v-model="passwordForm.newPassword"
              required
              placeholder="Enter new password"
              @input="updatePasswordStrength"
            ></ion-input>
          </ion-item>

          <ion-item lines="full">
            <ion-label position="floating">Confirm New Password</ion-label>
            <ion-input
              type="password"
              v-model="passwordForm.confirmPassword"
              required
              placeholder="Confirm new password"
            ></ion-input>
          </ion-item>

          <!-- Password Strength Meter -->
          <div class="password-strength" v-if="passwordForm.newPassword">
            <div class="strength-meter">
              <div 
                class="strength-meter-fill" 
                :style="{ width: passwordStrength.score + '%' }"
                :class="passwordStrength.label"
              ></div>
            </div>
            <div class="strength-label" :class="passwordStrength.label">
              {{ passwordStrength.label }}
            </div>
          </div>

          <div class="password-requirements" v-if="passwordForm.newPassword">
            <p :class="{ valid: hasMinLength }">
              <ion-icon :icon="hasMinLength ? checkmarkCircle : closeCircle"></ion-icon>
              At least 8 characters
            </p>
            <p :class="{ valid: hasUpperCase }">
              <ion-icon :icon="hasUpperCase ? checkmarkCircle : closeCircle"></ion-icon>
              One uppercase letter
            </p>
            <p :class="{ valid: hasLowerCase }">
              <ion-icon :icon="hasLowerCase ? checkmarkCircle : closeCircle"></ion-icon>
              One lowercase letter
            </p>
            <p :class="{ valid: hasNumber }">
              <ion-icon :icon="hasNumber ? checkmarkCircle : closeCircle"></ion-icon>
              One number
            </p>
            <p :class="{ valid: passwordsMatch }">
              <ion-icon :icon="passwordsMatch ? checkmarkCircle : closeCircle"></ion-icon>
              Passwords match
            </p>
          </div>

          <ion-button 
            expand="block" 
            type="submit" 
            class="submit-button"
            :disabled="!isPasswordFormValid"
          >
            Update Password
          </ion-button>
        </form>
      </ion-content>
    </ion-modal>

    <!-- Image Picker Modal -->
    <ion-modal 
      :is-open="isImagePickerOpen" 
      @didDismiss="isImagePickerOpen = false"
      class="image-picker-modal"
    >
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
  IonButtons, toastController, IonAvatar
} from '@ionic/vue';
import {
  personOutline, mailOutline, callOutline,
  lockClosedOutline, logOutOutline, saveOutline,
  closeOutline, checkmarkCircle, closeCircle,
  cameraOutline, cloudUploadOutline
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
    if (user.pfp) {
      profileImage.value = user.pfp;
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
  userData.pfp = imageUrl;
  localStorage.setItem('user', JSON.stringify(userData));
};

const handleProfileImageDelete = () => {
  profileImage.value = 'https://ionicframework.com/docs/img/demos/avatar.svg';
  isImagePickerOpen.value = false;
  
  // Update local storage
  const userData = JSON.parse(localStorage.getItem('user') || '{}');
  userData.pfp = null;
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
    const response = await axios.post(
      'http://localhost/codes/PROJ/dbConnect/update_profile.php',
      profile.value,
      {
        headers: {
          'Authorization': `Bearer ${token}`
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
        color: 'success'
      });
      await toast.present();
    }
  } catch (error: any) {
    const toast = await toastController.create({
      message: error.response?.data?.message || 'Failed to update profile',
      duration: 2000,
      color: 'danger'
    });
    await toast.present();
  }
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
    const response = await axios.post(
      'http://localhost/codes/PROJ/dbConnect/change_password.php',
      {
        currentPassword: passwordForm.value.currentPassword,
        newPassword: passwordForm.value.newPassword
      },
      {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      }
    );

    if (response.data.success) {
      isChangePasswordModalOpen.value = false;
      
      const toast = await toastController.create({
        message: response.data.message,
        duration: 3000,
        color: 'success'
      });
      await toast.present();

      // Handle required relogin
      if (response.data.requireRelogin) {
        // Clear local storage
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        
        // Show additional toast
        const reloginToast = await toastController.create({
          message: 'Please log in again with your new password',
          duration: 3000,
          color: 'warning'
        });
        await reloginToast.present();

        // Redirect to login after a short delay
        setTimeout(() => {
          router.push('/login');
        }, 2000);
      }
    }
  } catch (error: any) {
    const toast = await toastController.create({
      message: error.response?.data?.message || 'Failed to change password',
      duration: 3000,
      color: 'danger',
      position: 'top'
    });
    await toast.present();

    // Handle rate limiting
    if (error.response?.status === 429) {
      isChangePasswordModalOpen.value = false;
    }
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
    const response = await axios.put(
      'http://localhost/codes/PROJ/dbConnect/tasks.php',
      {
        action: 'update_profile_image',
        img_link: selectedImage.value
      },
      {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      }
    );

    if (response.data.success) {
      // Update the profile image with the local path
      const localPath = `/src/images/${response.data.filename}`;
      profileImage.value = localPath;
      selectedImage.value = null;
      
      // Update local storage
      const userData = JSON.parse(localStorage.getItem('user') || '{}');
      userData.pfp = localPath;
      localStorage.setItem('user', JSON.stringify(userData));

      const toast = await toastController.create({
        message: 'Profile picture updated successfully',
        duration: 2000,
        color: 'success'
      });
      await toast.present();
    }
  } catch (error: any) {
    const toast = await toastController.create({
      message: error.response?.data?.message || 'Failed to update profile picture',
      duration: 2000,
      color: 'danger'
    });
    await toast.present();
  }
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
  --height: 100%;
  --width: 100%;
}

.password-form {
  padding: 1rem 0;
}

.password-strength {
  padding: 1rem;
  margin-top: 0.5rem;
}

.strength-meter {
  height: 4px;
  background-color: var(--ion-color-light);
  border-radius: 2px;
  overflow: hidden;
}

.strength-meter-fill {
  height: 100%;
  transition: width 0.3s ease;
  border-radius: 2px;
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
  text-align: right;
  font-size: 0.8rem;
  margin-top: 0.25rem;
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
  padding: 1rem;
  margin: 1rem 0;
  background: var(--ion-color-light);
  border-radius: 8px;
}

.password-requirements p {
  margin: 0.5rem 0;
  color: var(--ion-color-medium);
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.password-requirements p.valid {
  color: var(--ion-color-success);
}

.password-requirements ion-icon {
  font-size: 1.2rem;
}

.password-requirements p.valid ion-icon {
  color: var(--ion-color-success);
}

.password-requirements p:not(.valid) ion-icon {
  color: var(--ion-color-medium);
}

.last-input {
  margin-bottom: 1rem;
}

.submit-button {
  margin-top: 1rem;
}

@media (min-width: 768px) {
  .password-modal {
    --height: auto;
    --width: 400px;
  }
}

.avatar-container {
  position: relative;
  margin-bottom: 0.5rem;
}

.change-photo-btn {
  position: absolute;
  bottom: 0;
  right: 0;
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

.change-photo-btn ion-icon {
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
</style>