<template>
  <ion-page>
    <ion-content class="ion-padding" :style="{ '--background': '#1a1a1a' }">
      <div class="forgot-container">
        <h1 class="ion-text-center">Forgot Password</h1>
        <p class="ion-text-center subtitle">Reset your account password</p>

        <form v-if="step === 1" @submit.prevent="handleRequestCode" class="form">
          <span class="input-span">
            <label for="email" class="label">Email</label>
            <div class="inputForm">
              <input
                type="email"
                id="email"
                v-model="email"
                required
                placeholder="Enter your email"/>
            </div>
            <span class="error-message" v-if="error">{{ error }}</span>
          </span>
          <ion-button 
            expand="block" 
            type="submit" 
            :disabled="loading || resendTimer > 0"
            class="submit-button">
            {{ loading ? 'Sending...' : 'Send Reset Code' }}
          </ion-button>
          <div v-if="resendTimer > 0" class="resend-timer">You can resend in {{ resendTimer }}s</div>
        </form>

        <div v-if="devResetCode && step === 2 && isDevMode" class="dev-reset-code">
          Reset Code (dev): {{ devResetCode }}
        </div>

        <form v-if="step === 2" @submit.prevent="handleResetPassword" class="form">
          <span class="input-span">
            <label for="code" class="label">Reset Code</label>
            <div class="inputForm">
              <input
                type="text"
                id="code"
                v-model="code"
                required
                placeholder="Enter reset code"/>
            </div>
          </span>
          <span class="input-span">
            <label for="newPassword" class="label">New Password</label>
            <div class="inputForm">
              <input
                :type="showPassword ? 'text' : 'password'"
                id="newPassword"
                v-model="newPassword"
                required
                placeholder="Enter new password"/>
              <span class="toggle-password" @click="showPassword = !showPassword">
                {{ showPassword ? '👁️' : '👁️‍🗨️' }}
              </span>
            </div>
            <div class="password-requirements" v-if="newPassword">
              <p :class="{ valid: hasLength }">✓ At least 11 characters</p>
              <p :class="{ valid: hasLetter }">✓ At least one letter</p>
              <p :class="{ valid: hasNumber }">✓ At least one number</p>
              <p :class="{ valid: hasSpecial }">✓ At least one special character</p>
            </div>
          </span>
          <span class="input-span">
            <label for="confirmPassword" class="label">Confirm Password</label>
            <div class="inputForm">
              <input
                :type="showPassword ? 'text' : 'password'"
                id="confirmPassword"
                v-model="confirmPassword"
                required
                placeholder="Confirm new password"/>
            </div>
          </span>
          <span class="error-message" v-if="error">{{ error }}</span>
          
          <div class="button-group">
            <ion-button 
              expand="block" 
              type="submit" 
              :disabled="loading || !isFormValid"
              class="submit-button">
              {{ loading ? 'Resetting Password...' : 'Reset Password' }}
            </ion-button>
            <ion-button 
              v-if="resendTimer === 0" 
              expand="block" 
              fill="outline" 
              @click="handleRequestCode" 
              :disabled="loading"
              class="resend-button">
              Resend Code
            </ion-button>
          </div>
        </form>

        <div v-if="successMessage" class="success-message">{{ successMessage }}</div>
        <router-link to="/login" class="back-link">Back to Login</router-link>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { IonPage, IonContent, IonButton, toastController } from '@ionic/vue';
import api from '@/utils/api';

const router = useRouter();
const step = ref(1);
const email = ref('');
const code = ref('');
const newPassword = ref('');
const confirmPassword = ref('');
const showPassword = ref(false);
const error = ref('');
const successMessage = ref('');
const loading = ref(false);
const devResetCode = ref('');
const isDevMode = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
const resendTimer = ref(0);
let resendInterval: number | undefined;

const hasLength = computed(() => newPassword.value.length >= 11);
const hasLetter = computed(() => /[A-Za-z]/.test(newPassword.value));
const hasNumber = computed(() => /[0-9]/.test(newPassword.value));
const hasSpecial = computed(() => /[!@#$%^&*(),.?":{}|<>]/.test(newPassword.value));

const isFormValid = computed(() => {
  return code.value &&
         newPassword.value &&
         confirmPassword.value &&
         hasLength.value &&
         hasLetter.value &&
         hasNumber.value &&
         hasSpecial.value &&
         newPassword.value === confirmPassword.value;
});

function startResendTimer() {
  resendTimer.value = 30;
  if (resendInterval) {
    window.clearInterval(resendInterval);
  }
  resendInterval = window.setInterval(() => {
    if (resendTimer.value > 0) {
      resendTimer.value--;
    } else {
      if (resendInterval) {
        window.clearInterval(resendInterval);
      }
    }
  }, 1000);
}

const handleRequestCode = async () => {
  error.value = '';
  successMessage.value = '';
  devResetCode.value = '';
  loading.value = true;
  try {
    const response = await api.post('/request_reset.php', {
      email: email.value
    });
    if (response.data.success) {
      step.value = 2;
      if (response.data.debug_code && isDevMode) {
        devResetCode.value = response.data.debug_code;
      }
      startResendTimer();
      error.value = '';
      successMessage.value = 'A reset code has been sent to your email.';
    } else {
      error.value = response.data.message || 'Failed to send reset code.';
    }
  } catch (err: any) {
    error.value = err.response?.data?.message || 
                 (err.message === 'Network Error' ? 
                   'Cannot connect to server. Please check if XAMPP is running and Apache is started.' : 
                   err.message || 'Failed to send reset code.');
  } finally {
    loading.value = false;
  }
};

const handleResetPassword = async () => {
  error.value = '';
  successMessage.value = '';

  if (!code.value) {
    error.value = 'Reset code is required.';
    return;
  }
  if (!newPassword.value) {
    error.value = 'New password is required.';
    return;
  }
  if (!hasLength.value) {
    error.value = 'Password must be at least 11 characters.';
    return;
  }
  if (!hasLetter.value) {
    error.value = 'Password must contain at least one letter.';
    return;
  }
  if (!hasNumber.value) {
    error.value = 'Password must contain at least one number.';
    return;
  }
  if (!hasSpecial.value) {
    error.value = 'Password must contain at least one special character.';
    return;
  }
  if (newPassword.value !== confirmPassword.value) {
    error.value = 'Passwords do not match.';
    return;
  }

  loading.value = true;
  try {
    const response = await api.post('/reset_password.php', {
      email: email.value,
      code: code.value,
      newPassword: newPassword.value
    });

    if (response.data.success) {
      // Clear any existing auth data
      localStorage.clear();
      sessionStorage.clear();

      // Show success toast
      const toast = await toastController.create({
        message: 'Password reset successful! Please log in with your new password.',
        duration: 3000,
        color: 'success',
        position: 'top'
      });
      await toast.present();

      // Reset form
      successMessage.value = 'Password reset successful! You can now log in.';
      error.value = '';
      step.value = 1;
      email.value = '';
      code.value = '';
      newPassword.value = '';
      confirmPassword.value = '';
      devResetCode.value = '';

      // Wait for toast to be shown
      await new Promise(resolve => setTimeout(resolve, 500));
      
      // Navigate to login page using replace to prevent going back
      router.replace('/login');
    } else {
      throw new Error(response.data.message || 'Failed to reset password.');
    }
  } catch (err: any) {
    error.value = err.response?.data?.message || 
                 (err.message === 'Network Error' ? 
                   'Cannot connect to server. Please check if XAMPP is running and Apache is started.' : 
                   err.message || 'Failed to reset password.');
    
    const toast = await toastController.create({
      message: error.value,
      duration: 3000,
      color: 'danger',
      position: 'top'
    });
    await toast.present();
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.forgot-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 100%;
  padding: 20px;
  color: #ffffff;
}

h1 {
  font-size: 2.2rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
  color: #4a90e2;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.subtitle {
  color: #8c8c8c;
  margin-bottom: 2rem;
  font-size: 1.1rem;
}

.form {
  width: 100%;
  max-width: 400px;
  background-color: #2a2a2a;
  padding: 2rem;
  border-radius: 15px;
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
  margin-bottom: 1.5rem;
  border: 1px solid #3a3a3a;
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
  font-size: 1rem;
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
  box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.1);
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
  opacity: 0.7;
  transition: opacity 0.2s ease;
}

.toggle-password:hover {
  opacity: 1;
}

.error-message {
  color: #ff4d4d;
  font-size: 0.9rem;
  margin-top: 0.5rem;
  display: block;
}

.success-message {
  color: #4CAF50;
  font-size: 1rem;
  text-align: center;
  margin-bottom: 1rem;
  padding: 10px 15px;
  background-color: rgba(76, 175, 80, 0.1);
  border-radius: 8px;
  border: 1px solid rgba(76, 175, 80, 0.2);
}

.password-requirements {
  margin-top: 1rem;
  font-size: 0.9rem;
  background-color: #333333;
  padding: 12px 15px;
  border-radius: 8px;
}

.password-requirements p {
  color: #ff4d4d;
  margin: 0.3rem 0;
  transition: color 0.3s ease;
  display: flex;
  align-items: center;
  gap: 8px;
}

.password-requirements p.valid {
  color: #4CAF50;
}

.button-group {
  margin-top: 2rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.submit-button {
  margin: 0;
  --background: #4a90e2;
  --background-hover: #357abd;
  --background-activated: #357abd;
  --border-radius: 10px;
  --padding-top: 1rem;
  --padding-bottom: 1rem;
  font-weight: 600;
}

.resend-button {
  margin: 0;
  --border-color: #4a90e2;
  --color: #4a90e2;
  --background: transparent;
  --background-hover: rgba(74, 144, 226, 0.1);
  --background-activated: rgba(74, 144, 226, 0.2);
  --border-radius: 10px;
  --padding-top: 1rem;
  --padding-bottom: 1rem;
}

.resend-timer {
  color: #8c8c8c;
  font-size: 0.95rem;
  margin-top: 1rem;
  text-align: center;
  background-color: #333333;
  padding: 8px 12px;
  border-radius: 6px;
}

.dev-reset-code {
  background: #222;
  color: #4a90e2;
  padding: 12px 16px;
  border-radius: 8px;
  margin: 1rem 0;
  text-align: center;
  font-size: 1.1rem;
  border: 1px solid #333;
  max-width: 400px;
  width: 100%;
}

.back-link {
  color: #4a90e2;
  text-align: center;
  display: block;
  margin-top: 1.5rem;
  font-size: 1rem;
  text-decoration: none;
  transition: all 0.3s ease;
}

.back-link:hover {
  color: #357abd;
  text-decoration: underline;
}
</style> 