<template>
  <ion-page>
    <ion-content class="ion-padding" :style="{ '--background': '#1a1a1a' }">
      <div class="signup-container">
        <h1 class="ion-text-center">Create Account</h1>
        <p class="ion-text-center subtitle">Join us to manage your tasks efficiently</p>

        <form @submit.prevent="handleSubmit" class="form">
          <span class="input-span">
            <label for="username" class="label">Username</label>
            <div class="inputForm">
              <input
                type="text"
                id="username"
                v-model="username"
                required
                placeholder="Enter username"
                @blur="validateUsername"/>
            </div>
            <span class="error-message" v-if="errors.username">{{ errors.username }}</span>
          </span>

          <span class="input-span">
            <label for="email" class="label">Email</label>
            <div class="inputForm">
              <input
                type="email"
                id="email"
                v-model="email"
                required
                placeholder="Enter email"
                @blur="validateEmail"/>
            </div>
            <span class="error-message" v-if="errors.email">{{ errors.email }}</span>
          </span>

          <span class="input-span">
            <label for="phone" class="label">Phone</label>
            <div class="inputForm">
              <input
                type="tel"
                id="phone"
                v-model="phone"
                required
                placeholder="Enter phone"
                @input="validatePhone"/>
            </div>
            <span class="error-message" v-if="errors.phone">{{ errors.phone }}</span>
          </span>

          <span class="input-span">
            <label for="password" class="label">Password</label>
            <div class="inputForm">
              <input
                :type="showPassword ? 'text' : 'password'"
                id="password"
                v-model="password"
                required
                placeholder="Enter password"
                @input="validatePassword"/>
              <span class="toggle-password" @click="showPassword = !showPassword">
                {{ showPassword ? '🙈' : '👁️' }}
              </span>
            </div>
            <span class="error-message" v-if="errors.password">{{ errors.password }}</span>
            <div class="password-requirements" v-if="password">
              <p :class="{ valid: hasUpperCase }">✓ Uppercase letter</p>
              <p :class="{ valid: hasLowerCase }">✓ Lowercase letter</p>
              <p :class="{ valid: hasNumber }">✓ Number</p>
              <p :class="{ valid: hasSpecial }">✓ Special character</p>
            </div>
          </span>

          <!-- Admin option in development mode -->
          <span v-if="isDevelopment" class="input-span admin-option">
            <ion-checkbox v-model="isAdmin">Make this account an admin</ion-checkbox>
            <ion-note>This option is only available in development mode</ion-note>
          </span>

          <button class="button-submit" type="submit" :disabled="!isFormValid">Sign Up</button>

          <p class="p">Already have an account? <span class="span" @click="goToLogin">Login</span></p>
        </form>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { IonPage, IonContent, toastController, IonCheckbox, IonNote } from '@ionic/vue';

const router = useRouter();
const username = ref('');
const email = ref('');
const phone = ref('');
const password = ref('');
const showPassword = ref(false);
const isAdmin = ref(false);
const isDevelopment = import.meta.env.MODE === 'development';
const errors = ref({
  username: '',
  email: '',
  phone: '',
  password: '',
  general: ''
});

// Password validation computed properties
const hasUpperCase = computed(() => /[A-Z]/.test(password.value));
const hasLowerCase = computed(() => /[a-z]/.test(password.value));
const hasNumber = computed(() => /[0-9]/.test(password.value));
const hasSpecial = computed(() => /[!@#$%^&*(),.?":{}|<>]/.test(password.value));

const isFormValid = computed(() => {
  return !errors.value.username &&
         !errors.value.email &&
         !errors.value.phone &&
         !errors.value.password &&
         username.value &&
         email.value &&
         phone.value &&
         password.value;
});

const validateUsername = async () => {
  if (!username.value) {
    errors.value.username = 'Username is required';
    return;
  }
  // Optionally, add frontend-only uniqueness check here if you have a list of users
  errors.value.username = '';
};

const validateEmail = () => {
  if (!email.value) {
    errors.value.email = 'Email is required';
    return;
  }
  
  if (!email.value.endsWith('@gmail.com')) {
    errors.value.email = 'Email must be a Gmail address';
  } else {
    errors.value.email = '';
  }
};

const validatePhone = () => {
  const phoneRegex = /^\d{11}$/;
  if (!phone.value) {
    errors.value.phone = 'Phone number is required';
  } else if (!phoneRegex.test(phone.value)) {
    errors.value.phone = 'Phone number must be exactly 11 digits';
  } else {
    errors.value.phone = '';
  }
};

const validatePassword = () => {
  if (!password.value) {
    errors.value.password = 'Password is required';
    return;
  }

  if (password.value.length < 8) {
    errors.value.password = 'Password must be at least 8 characters';
    return;
  }

  if (!hasUpperCase.value || !hasLowerCase.value || !hasNumber.value || !hasSpecial.value) {
    errors.value.password = 'Password must meet all requirements';
  } else {
    errors.value.password = '';
  }
};

const handleSubmit = async () => {
  // Reset errors
  errors.value = {
    username: '',
    email: '',
    phone: '',
    password: '',
    general: ''
  };

  try {
    const response = await axios.post('http://localhost/codes/PROJ/dbConnect/signup.php', {
      username: username.value,
      email: email.value,
      phone: phone.value,
      password: password.value,
      isAdmin: isAdmin.value
    });

    if (response.data.message === 'success') {
      const toast = await toastController.create({
        message: 'Account created successfully! Please login.',
        duration: 2000,
        color: 'success',
        position: 'top'
      });
      await toast.present();
      router.push('/login');
    } else {
      throw new Error(response.data.message);
    }
  } catch (error: any) {
    console.error('Signup error:', error);
    const errorMessage = error.response?.data?.message || 
                        (error.message === 'Network Error' ? 
                          'Cannot connect to server. Please check if XAMPP is running and Apache is started.' : 
                          error.message);
    const toast = await toastController.create({
      message: errorMessage,
      duration: 3000,
      color: 'danger',
      position: 'top'
    });
    await toast.present();
    errors.value.general = errorMessage;
  }
};

const goToLogin = () => {
  router.push('/login');
};
</script>

<style scoped>
.signup-container {
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

.password-requirements {
  margin-top: 1rem;
  font-size: 0.9rem;
}

.password-requirements p {
  color: #ff4d4d;
  margin: 0.3rem 0;
  transition: color 0.3s ease;
}

.password-requirements p.valid {
  color: #4CAF50;
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

.admin-option {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 12px;
  background: #333333;
  border-radius: 8px;
  margin-bottom: 20px;
}

ion-checkbox {
  --size: 20px;
  --checkbox-background-checked: #4a90e2;
}

ion-note {
  font-size: 0.8rem;
  color: #666666;
}

/* Dark theme specific styles */
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