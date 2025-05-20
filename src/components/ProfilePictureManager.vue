<template>
  <div class="profile-picture-manager">
    <div class="preview-container" v-if="selectedImage">
      <img :src="selectedImage" alt="Preview" class="image-preview" />
    </div>
    
    <div class="upload-options">
      <label for="file" class="custum-file-upload">
        <div class="icon">
          <svg viewBox="0 0 24 24" fill="" xmlns="http://www.w3.org/2000/svg">
            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
            <g id="SVGRepo_iconCarrier">
              <path fill-rule="evenodd" clip-rule="evenodd" d="M10 1C9.73478 1 9.48043 1.10536 9.29289 1.29289L3.29289 7.29289C3.10536 7.48043 3 7.73478 3 8V20C3 21.6569 4.34315 23 6 23H7C7.55228 23 8 22.5523 8 22C8 21.4477 7.55228 21 7 21H6C5.44772 21 5 20.5523 5 20V9H10C10.5523 9 11 8.55228 11 8V3H18C18.5523 3 19 3.44772 19 4V9C19 9.55228 19.4477 10 20 10C20.5523 10 21 9.55228 21 9V4C21 2.34315 19.6569 1 18 1H10ZM9 7H6.41421L9 4.41421V7ZM14 15.5C14 14.1193 15.1193 13 16.5 13C17.8807 13 19 14.1193 19 15.5V16V17H20C21.1046 17 22 17.8954 22 19C22 20.1046 21.1046 21 20 21H13C11.8954 21 11 20.1046 11 19C11 17.8954 11.8954 17 13 17H14V16V15.5ZM16.5 11C14.142 11 12.2076 12.8136 12.0156 15.122C10.2825 15.5606 9 17.1305 9 19C9 21.2091 10.7909 23 13 23H20C22.2091 23 24 21.2091 24 19C24 17.1305 22.7175 15.5606 20.9844 15.122C20.7924 12.8136 18.858 11 16.5 11Z" fill=""></path>
            </g>
          </svg>
        </div>
        <div class="text">
          <span>Click to upload image</span>
        </div>
        <input 
          id="file" 
          type="file" 
          accept="image/*" 
          @change="handleFileSelect"
        >
      </label>
      
      <div class="action-buttons">
        <ion-button 
          expand="block" 
          @click="uploadImage" 
          :disabled="!selectedImage"
          class="save-image-btn"
        >
          <ion-icon :icon="saveOutline" slot="start"></ion-icon>
          Save Image
        </ion-button>

        <ion-button 
          expand="block" 
          @click="deleteImage" 
          :disabled="!hasProfilePicture"
          class="delete-image-btn"
          color="danger"
        >
          <ion-icon :icon="trashOutline" slot="start"></ion-icon>
          Delete Image
        </ion-button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { IonButton, IonIcon, toastController } from '@ionic/vue';
import { saveOutline, trashOutline } from 'ionicons/icons';
import axios from 'axios';

const props = defineProps<{
  currentImage: string;
}>();

const emit = defineEmits<{
  (e: 'update', imageUrl: string): void;
  (e: 'delete'): void;
}>();

const selectedImage = ref<string | null>(null);
const hasProfilePicture = computed(() => props.currentImage !== 'https://ionicframework.com/docs/img/demos/avatar.svg');

const handleFileSelect = (event: Event) => {
  const input = event.target as HTMLInputElement;
  if (input.files && input.files[0]) {
    const file = input.files[0];
    const reader = new FileReader();
    
    reader.onload = (e) => {
      selectedImage.value = e.target?.result as string;
    };
    
    reader.readAsDataURL(file);
  }
};

const uploadImage = async () => {
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
      emit('update', response.data.img_link);
      
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

const deleteImage = async () => {
  try {
    const token = localStorage.getItem('token');
    const response = await axios.put(
      'http://localhost/codes/PROJ/dbConnect/tasks.php',
      {
        action: 'delete_profile_image'
      },
      {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      }
    );

    if (response.data.success) {
      emit('delete');
      
      const toast = await toastController.create({
        message: 'Profile picture deleted successfully',
        duration: 2000,
        color: 'success'
      });
      await toast.present();
    }
  } catch (error: any) {
    const toast = await toastController.create({
      message: error.response?.data?.message || 'Failed to delete profile picture',
      duration: 2000,
      color: 'danger'
    });
    await toast.present();
  }
};
</script>

<style scoped>
.profile-picture-manager {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
}

.preview-container {
  width: 100%;
  max-width: 300px;
  aspect-ratio: 1;
  border-radius: 50%;
  overflow: hidden;
  border: 2px solid var(--ion-color-light);
  background-color: var(--ion-color-light);
}

.image-preview {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.upload-options {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  width: 100%;
  max-width: 300px;
}

.custum-file-upload {
  height: 200px;
  width: 300px;
  display: flex;
  flex-direction: column;
  align-items: space-between;
  gap: 20px;
  cursor: pointer;
  align-items: center;
  justify-content: center;
  border: 2px dashed #e8e8e8;
  background-color: #212121;
  padding: 1.5rem;
  border-radius: 10px;
  box-shadow: 0px 48px 35px -48px #e8e8e8;
}

.custum-file-upload .icon {
  display: flex;
  align-items: center;
  justify-content: center;
}

.custum-file-upload .icon svg {
  height: 80px;
  fill: #e8e8e8;
}

.custum-file-upload .text {
  display: flex;
  align-items: center;
  justify-content: center;
}

.custum-file-upload .text span {
  font-weight: 400;
  color: #e8e8e8;
}

.custum-file-upload input {
  display: none;
}

.action-buttons {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  width: 100%;
}

.save-image-btn,
.delete-image-btn {
  margin: 0;
  width: 100%;
}
</style> 