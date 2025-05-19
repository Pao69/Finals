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
                placeholder="Enter your username"
              />
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
                placeholder="Enter your password"
              />
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
            <span class="forgot-link" @click="showForgotModal = true">Forgot Password?</span>
          </div>

          <button class="button-submit" type="submit" :disabled="!isFormValid">Sign In</button>

          <p class="p">Don't have an account? <span class="span" @click="goToSignup">Sign Up</span></p>
        </form>

        <ion-modal :is-open="showForgotModal" @didDismiss="resetForgotModal()">
          <div class="forgot-modal">
            <h2>Forgot Password</h2>
            <p class="forgot-desc" v-if="forgotStep === 1">Enter your email address and we'll send you a reset code.</p>
            <form v-if="forgotStep === 1" @submit.prevent="handleForgotEmail">
              <input
                type="email"
                v-model="forgotEmail"
                required
                placeholder="Enter your email"
                class="forgot-input"
              />
              <button class="button-submit" type="submit" :disabled="forgotLoading || resendTimer > 0">
                {{ forgotLoading ? 'Sending...' : 'Send Reset Code' }}
              </button>
              <div v-if="resendTimer > 0" class="resend-timer">You can resend in {{ resendTimer }}s</div>
            </form>
            <div v-if="devResetCode && forgotStep === 2 && isDevMode" class="dev-reset-code">
              <strong>Reset Code (dev):</strong> <span>{{ devResetCode }}</span>
            </div>
            <button v-if="forgotStep === 2 && resendTimer === 0" class="resend-link" @click="handleForgotEmail" :disabled="forgotLoading">Resend Code</button>
            <form v-else-if="forgotStep === 2" @submit.prevent="handleForgotReset">
              <p class="forgot-desc">Enter the code sent to your email and your new password.</p>
              <input
                type="text"
                v-model="resetCode"
                required
                placeholder="Enter reset code"
                class="forgot-input"
              />
              <input
                type="password"
                v-model="resetPassword"
                required
                placeholder="Enter new password"
                class="forgot-input"
              />
              <input
                type="password"
                v-model="resetPasswordConfirm"
                required
                placeholder="Confirm new password"
                class="forgot-input"
              />
              <div class="password-requirements" v-if="resetPassword">
                <p :class="{ valid: resetHasLength }">✓ At least 11 characters</p>
                <p :class="{ valid: resetHasLetter }">✓ At least one letter</p>
                <p :class="{ valid: resetHasNumber }">✓ At least one number</p>
                <p :class="{ valid: resetHasSpecial }">✓ At least one special character</p>
              </div>
              <button class="button-submit" type="submit" :disabled="forgotLoading">
                {{ forgotLoading ? 'Resetting...' : 'Reset Password' }}
              </button>
            </form>
            <p v-if="forgotMessage" class="forgot-message">{{ forgotMessage }}</p>
            <button class="close-modal" @click="resetForgotModal()">Close</button>
          </div>
        </ion-modal>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { IonPage, IonContent, toastController, IonModal } from '@ionic/vue';

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
const showForgotModal = ref(false);
const forgotEmail = ref('');
const forgotLoading = ref(false);
const forgotMessage = ref('');
const forgotStep = ref(1);
const resetCode = ref('');
const resetPassword = ref('');
const resetPasswordConfirm = ref('');
const devResetCode = ref('');
const isDevMode = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
const resendTimer = ref(0);
let resendInterval: any = null;

const isFormValid = computed(() => {
  return username.value && password.value;
});

const resetHasLength = computed(() => resetPassword.value.length >= 11);
const resetHasLetter = computed(() => /[A-Za-z]/.test(resetPassword.value));
const resetHasNumber = computed(() => /[0-9]/.test(resetPassword.value));
const resetHasSpecial = computed(() => /[!@#$%^&*(),.?":{}|<>]/.test(resetPassword.value));

function startResendTimer() {
  resendTimer.value = 30;
  if (resendInterval) clearInterval(resendInterval);
  resendInterval = setInterval(() => {
    if (resendTimer.value > 0) {
      resendTimer.value--;
    } else {
      clearInterval(resendInterval);
    }
  }, 1000);
}

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
    const response = await axios.post('http://localhost/Codes/PROJ/dbConnect/login.php', {
      username: username.value,
      password: password.value
    });

    if (response.data.message === 'success') {
      // Store user data and token in the appropriate storage
      if (rememberMe.value) {
        localStorage.setItem('user', JSON.stringify(response.data.user));
        localStorage.setItem('token', response.data.token);
      } else {
        sessionStorage.setItem('user', JSON.stringify(response.data.user));
        sessionStorage.setItem('token', response.data.token);
      }
      axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`;
      await router.push('/tabs/dashboard');
    } else {
      throw new Error(response.data.message || 'Invalid username or password');
    }
  } catch (error: any) {
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

function resetForgotModal() {
  showForgotModal.value = false;
  forgotEmail.value = '';
  forgotMessage.value = '';
  forgotStep.value = 1;
  resetCode.value = '';
  resetPassword.value = '';
  resetPasswordConfirm.value = '';
  forgotLoading.value = false;
  devResetCode.value = '';
  resendTimer.value = 0;
  if (resendInterval) clearInterval(resendInterval);
}

const handleForgotEmail = async () => {
  forgotLoading.value = true;
  forgotMessage.value = '';
  devResetCode.value = '';
  try {
    const response = await axios.post('http://localhost/Codes/PROJ/dbConnect/request_reset.php', {
      email: forgotEmail.value
    });
    if (response.data.success) {
      forgotStep.value = 2;
      forgotMessage.value = 'A reset code has been sent to your email.';
      if (response.data.debug_code && isDevMode) {
        devResetCode.value = response.data.debug_code;
      }
      startResendTimer();
    } else {
      forgotMessage.value = response.data.message || 'Failed to send reset code.';
    }
  } catch (error: any) {
    forgotMessage.value = error.response?.data?.message || error.message || 'Failed to send reset code.';
  }
  forgotLoading.value = false;
};

const handleForgotReset = async () => {
  forgotLoading.value = true;
  forgotMessage.value = '';
  // Frontend validation
  if (!resetCode.value) {
    forgotMessage.value = 'Reset code is required.';
    forgotLoading.value = false;
    return;
  }
  if (!resetPassword.value) {
    forgotMessage.value = 'New password is required.';
    forgotLoading.value = false;
    return;
  }
  if (resetPassword.value.length < 11) {
    forgotMessage.value = 'Password must be at least 11 characters.';
    forgotLoading.value = false;
    return;
  }
  if (!/[A-Za-z]/.test(resetPassword.value)) {
    forgotMessage.value = 'Password must contain at least one letter.';
    forgotLoading.value = false;
    return;
  }
  if (!/[0-9]/.test(resetPassword.value)) {
    forgotMessage.value = 'Password must contain at least one number.';
    forgotLoading.value = false;
    return;
  }
  if (!/[!@#$%^&*(),.?":{}|<>]/.test(resetPassword.value)) {
    forgotMessage.value = 'Password must contain at least one special character.';
    forgotLoading.value = false;
    return;
  }
  if (resetPassword.value !== resetPasswordConfirm.value) {
    forgotMessage.value = 'Passwords do not match.';
    forgotLoading.value = false;
    return;
  }
  try {
    const response = await axios.post('http://localhost/Codes/PROJ/dbConnect/reset_password.php', {
      email: forgotEmail.value,
      code: resetCode.value,
      newPassword: resetPassword.value
    });
    if (response.data.success) {
      forgotMessage.value = 'Password reset successful! You can now log in.';
      forgotStep.value = 1;
      forgotEmail.value = '';
      resetCode.value = '';
      resetPassword.value = '';
      resetPasswordConfirm.value = '';
    } else {
      forgotMessage.value = response.data.message || 'Failed to reset password.';
    }
  } catch (error: any) {
    forgotMessage.value = error.response?.data?.message || error.message || 'Failed to reset password.';
  }
  forgotLoading.value = false;
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
}
.remember-label {
  color: #8c8c8c;
  font-size: 0.98rem;
  display: flex;
  align-items: center;
  gap: 0.4em;
  cursor: pointer;
}
.forgot-link {
  color: #4a90e2;
  font-size: 0.98rem;
  cursor: pointer;
  font-weight: 500;
}
.forgot-link:hover {
  text-decoration: underline;
}
.forgot-modal {
  background: #232323;
  color: #fff;
  padding: 2rem 1.5rem 1.5rem 1.5rem;
  border-radius: 16px;
  max-width: 350px;
  margin: 2.5rem auto;
  box-shadow: 0 4px 24px rgba(0,0,0,0.12);
  display: flex;
  flex-direction: column;
  align-items: stretch;
}
.forgot-modal h2 {
  text-align: center;
  color: #4a90e2;
  margin-bottom: 0.5rem;
}
.forgot-desc {
  text-align: center;
  color: #bdbdbd;
  margin-bottom: 1.2rem;
}
.forgot-input {
  width: 100%;
  padding: 12px 15px;
  background: #333;
  border: 2px solid #404040;
  border-radius: 10px;
  color: #fff;
  font-size: 1rem;
  margin-bottom: 1rem;
}
.forgot-message {
  color: #4a90e2;
  text-align: center;
  margin-top: 1rem;
  font-size: 1rem;
}
.close-modal {
  margin-top: 1.2rem;
  background: none;
  border: none;
  color: #8c8c8c;
  font-size: 1rem;
  cursor: pointer;
  text-align: center;
}
.close-modal:hover {
  color: #fff;
}

.dev-reset-code {
  background: #222;
  color: #4a90e2;
  padding: 0.7em 1em;
  border-radius: 8px;
  margin: 1em 0 0.5em 0;
  text-align: center;
  font-size: 1.1em;
}

.resend-link {
  background: none;
  border: none;
  color: #4a90e2;
  font-size: 1rem;
  margin: 0.5em 0 1em 0;
  cursor: pointer;
  text-align: center;
  display: block;
}
.resend-link:disabled {
  color: #888;
  cursor: not-allowed;
}
.resend-timer {
  color: #bdbdbd;
  font-size: 0.95em;
  margin-top: 0.5em;
  text-align: center;
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