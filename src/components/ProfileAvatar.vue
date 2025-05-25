<template>
  <div class="profile-avatar" @click="goToSettings">
    <ion-avatar>
      <img 
        :src="profileImage" 
        alt="Profile" 
        @error="handleImageError"
        class="avatar-image"/>
    </ion-avatar>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { IonAvatar } from '@ionic/vue';
import api from '@/utils/api';

const router = useRouter();
const profileImage = ref('https://ionicframework.com/docs/img/demos/avatar.svg');

const handleImageError = (e: Event) => {
  const target = e.target as HTMLImageElement;
  target.src = 'https://ionicframework.com/docs/img/demos/avatar.svg';
};

const goToSettings = () => {
  router.push('/tabs/settings');
};

const fetchProfilePicture = async () => {
  try {
    const userData = localStorage.getItem('user');
    if (userData) {
      const user = JSON.parse(userData);
      if (user.pfp) {
        profileImage.value = user.pfp;
        return;
      }
    }

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

onMounted(() => {
  fetchProfilePicture();
});
</script>

<style scoped>
.profile-avatar {
  cursor: pointer;
}

.profile-avatar:hover {
  transform: none;
}

.avatar-image {
  width: 40px;
  height: 40px;
  border: 2px solid var(--ion-color-primary);
  border-radius: 50%;
}

ion-avatar {
  width: 40px;
  height: 40px;
}
</style> 