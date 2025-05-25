<template>
  <ion-page>
    <ion-content class="ion-padding" :style="{ '--background': '#1a1a1a' }">
      <div class="login-container">
        <h1 class="ion-text-center">Welcome Back</h1>
        <p class="ion-text-center subtitle">Sign in to continue</p>

        <form @submit.prevent="handleSubmit" class="form">
          <span class="input-span">
            <label for="username" class="label">Username</label>
            <div class="inputForm">
              <input
                type="text"
                id="username"
                v-model="username"
                required
                placeholder="Enter your username"/>
            </div>
            <span class="error-message" v-if="errors.username">{{ errors.username }}</span>
          </span>

          <span class="input-span">
            <label for="password" class="label">Password</label>
            <div class="inputForm">
              <input
                :type="showPassword ? 'text' : 'password'"
                id="password"
                v-model="password"
                required
                placeholder="Enter your password"/>
              <span class="toggle-password" @click="showPassword = !showPassword">
                {{ showPassword ? '👁️' : '👁️‍🗨️' }}
              </span>
            </div>
            <span class="error-message" v-if="errors.password">{{ errors.password }}</span>
          </span>

          <div class="remember-forgot-row">
            <label class="remember-label">
              <input type="checkbox" v-model="rememberMe" /> Remember Me
            </label>
            <router-link to="/forgot-password" class="forgot-link">Forgot Password?</router-link>
          </div>

          <button class="button-submit" type="submit" :disabled="!isFormValid">Sign In</button>

          <p class="p">Don't have an account? <span class="span" @click="goToSignup">Sign Up</span></p>
        </form>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { IonPage, IonContent, toastController } from '@ionic/vue';

const router = useRouter();
const username = ref('');
const password = ref('');
const showPassword = ref(false);
const errors = ref({
  username: '',
  password: '',
  general: ''
});

const rememberMe = ref(true);

const isFormValid = computed(() => {
  return username.value && password.value;
});

const handleSubmit = async () => {
  // Reset errors
  errors.value = {
    username: '',
    password: '',
    general: ''
  };

  if (!username.value) {
    errors.value.username = 'Username is required';
    return;
  }

  if (!password.value) {
    errors.value.password = 'Password is required';
    return;
  }

  try {
    // Clear both storages before logging in
    localStorage.clear();
    sessionStorage.clear();

    const response = await axios.post('http://localhost/Codes/PROJ/dbConnect/login.php', {
      username: username.value,
      password: password.value
    });

    if (response.data.message === 'success') {
      const user = response.data.user;
      console.log('Response user data:', user);
      
      // Explicitly check and preserve admin status
      const isAdmin = user.role === 'admin';
      console.log('Is admin?', isAdmin);
      console.log('Original role:', user.role);
      
      // Clear everything first
      localStorage.clear();
      sessionStorage.clear();
      delete axios.defaults.headers.common['Authorization'];
      
      // Create a new user object with explicit role preservation
      const userToStore = {
        id: user.id,
        username: user.username,
        email: user.email,
        phone: user.phone,
        role: user.role // Use the exact role from the response
      };
      console.log('User data to store:', userToStore);
      
      // Store user data and token in the appropriate storage
      const storage = rememberMe.value ? localStorage : sessionStorage;
      console.log('Using storage:', rememberMe.value ? 'localStorage' : 'sessionStorage');
      
      // Set the token first
      const token = response.data.token;
      storage.setItem('token', token);
      axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
      
      // Then set the user data
      storage.setItem('user', JSON.stringify(userToStore));
      
      // Verify stored data
      const storedUserData = storage.getItem('user');
      const storedUser = storedUserData ? JSON.parse(storedUserData) : null;
      console.log('Stored user data:', storedUser);
      
      // Show success message
      const toast = await toastController.create({
        message: 'Login successful!',
        duration: 2000,
        color: 'success',
        position: 'top'
      });
      await toast.present();
      
      // Force a clean navigation state
      await router.replace('/login');
      
      // Then navigate to the correct route
      setTimeout(() => {
        const targetRoute = isAdmin ? '/tabs/admin' : '/tabs/dashboard';
        router.replace(targetRoute);
      }, 100);
    } 
    
    else {
      throw new Error(response.data.message || 'Invalid username or password');
    }
  } 
  catch (error: any) {
    console.error('Login error:', error);
    const errorMessage = error.response?.data?.message || error.message || 'Login failed. Please try again.';
    const toast = await toastController.create({
      message: errorMessage,
      duration: 2000,
      color: 'danger',
      position: 'top'
    });
    await toast.present();
    errors.value.general = errorMessage;
  }
};

const goToSignup = () => {
  router.push('/signup');
};
</script>

<style scoped>
.login-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 100%;
  padding: 20px;
  color: #ffffff;
}

h1 {
  font-size: 2.5rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
  color: #4a90e2;
}

.subtitle {
  color: #8c8c8c;
  margin-bottom: 2rem;
}

.form {
  width: 100%;
  max-width: 400px;
  background-color: #2a2a2a;
  padding: 2rem;
  border-radius: 15px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.input-span {
  display: block;
  margin-bottom: 1.5rem;
}

.label {
  display: block;
  margin-bottom: 0.5rem;
  color: #4a90e2;
  font-weight: 500;
}

.inputForm {
  position: relative;
  background-color: #333333;
  border: 2px solid #404040;
  border-radius: 10px;
  transition: all 0.3s ease;
}

.inputForm:focus-within {
  border-color: #4a90e2;
}

input {
  width: 100%;
  padding: 12px 15px;
  background: transparent;
  border: none;
  color: #ffffff;
  font-size: 1rem;
  outline: none;
}

input::placeholder {
  color: #666666;
}

.toggle-password {
  position: absolute;
  right: 15px;
  top: 50%;
  transform: translateY(-50%);
  cursor: pointer;
  user-select: none;
}

.error-message {
  color: #ff4d4d;
  font-size: 0.8rem;
  margin-top: 0.5rem;
  display: block;
}

.button-submit {
  width: 100%;
  padding: 14px;
  background-color: #4a90e2;
  color: white;
  border: none;
  border-radius: 10px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  margin-top: 1rem;
}

.button-submit:hover:not(:disabled) {
  background-color: #357abd;
}

.button-submit:disabled {
  background-color: #333333;
  cursor: not-allowed;
}

.p {
  text-align: center;
  color: #8c8c8c;
  margin-top: 1.5rem;
}

.span {
  color: #4a90e2;
  cursor: pointer;
  font-weight: 500;
}

.span:hover {
  text-decoration: underline;
}

.remember-forgot-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.2rem;
  flex-wrap: wrap;
  gap: 0.8rem;
}

.remember-label {
  color: #8c8c8c;
  font-size: 0.98rem;
  display: inline-flex;
  align-items: center;
  gap: 0.4em;
  cursor: pointer;
  flex-shrink: 0;
}

.forgot-link {
  color: #4a90e2;
  text-decoration: none;
  transition: color 0.3s ease;
  cursor: pointer;
}

.forgot-link:hover {
  color: #357abd;
  text-decoration: underline;
}

:deep(ion-content) {
  --background: #1a1a1a;
}

@media (max-width: 480px) {
  .form {
    padding: 1.5rem;
  }

  h1 {
    font-size: 2rem;
  }
}
</style>