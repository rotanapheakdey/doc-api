<template>
  <div class="login-page bg-light min-vh-100 d-flex align-items-center justify-content-center">
    <div class="login-box" style="width: 360px;">
      <div class="card card-outline card-primary">
        <div class="card-header text-center bg-white border-bottom-0 pt-4">
          <router-link to="/" class="h1 text-decoration-none">
            <b class="text-primary">DMS</b>
          </router-link>
        </div>
        <div class="card-body">
          <p class="text-center text-muted border-bottom pb-3 mb-3">Sign in to start your session</p>

          <!-- Error Alert -->
          <div v-if="errorMessage" class="alert alert-danger alert-dismissible fade show">
            {{ errorMessage }}
            <button type="button" class="close" @click="errorMessage = ''">×</button>
          </div>

          <form @submit.prevent="signIn">
            <div class="mb-3">
              <div class="input-group">
                <input
                  v-model="user.email"
                  type="email"
                  class="form-control"
                  :class="{ 'is-invalid': userError.email }"
                  placeholder="Email"
                />
                <span class="input-group-text bg-white">
                  <i class="fas fa-envelope"></i>
                </span>
              </div>
              <div v-if="userError.email" class="invalid-feedback d-block">
                {{ userError.email }}
              </div>
            </div>

            <div class="mb-3">
              <div class="input-group">
                <input
                  v-model="user.password"
                  type="password"
                  class="form-control"
                  :class="{ 'is-invalid': userError.password }"
                  placeholder="Password"
                />
                <span class="input-group-text bg-white">
                  <i class="fas fa-lock"></i>
                </span>
              </div>
              <div v-if="userError.password" class="invalid-feedback d-block">
                {{ userError.password }}
              </div>
            </div>

            <div class="row">
              <div class="col-8"></div>
              <div class="col-4">
                <button type="submit" class="btn btn-primary btn-block" :disabled="loading">
                  <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
                  {{ loading ? 'Loading...' : 'Sign In' }}
                </button>
              </div>
            </div>
          </form>

          <p class="mb-1 mt-3">
            <a href="#" class="text-center text-decoration-none">I forgot my password</a>
          </p>
          <p class="mb-0">
            <a href="#" class="text-center text-decoration-none">Register a new membership</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from "vue-router";
import { reactive, ref } from "vue";
import { useAuthStore } from '@/stores/auth'

const router = useRouter();
const authStore = useAuthStore();
const loading = ref(false);
const errorMessage = ref('');

const user = reactive({
  email: "",
  password: "",
});

const userError = reactive({
  email: "",
  password: "",
});

async function signIn() {
  // Reset errors
  userError.email = "";
  userError.password = "";
  errorMessage.value = '';

  // Validate
  if (!user.email) {
    userError.email = "Email is required";
    return;
  }
  if (!user.password) {
    userError.password = "Password is required";
    return;
  }

  loading.value = true;

  try {
    const result = await authStore.login(user.email, user.password);

    if (result.success) {
      user.email = "";
      user.password = "";
      router.replace({ name: 'dashboard' });
    } else {
      errorMessage.value = result.message || 'Login failed';
    }
  } catch (error) {
    errorMessage.value = 'An unexpected error occurred';
  } finally {
    loading.value = false;
  }
}
</script>
