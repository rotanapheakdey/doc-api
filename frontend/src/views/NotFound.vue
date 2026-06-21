<template>
  <div class="d-flex align-items-center justify-content-center min-vh-100 bg-light">
    <div class="text-center">
      <!-- Icon -->
      <div class="mb-4">
        <i class="fas fa-file-alt text-muted" style="font-size: 100px;"></i>
      </div>

      <!-- 404 Message -->
      <h1 class="display-1 fw-bold text-primary">404</h1>
      <h2 class="mb-4 text-dark">Page Not Found</h2>

      <!-- Back to Dashboard Button -->
      <router-link to="/" class="btn btn-primary btn-lg">
        <i class="fas fa-home me-2"></i> Back to Login
      </router-link>
    </div>
  </div>
</template>

<style scoped>
.min-vh-100 {
  min-height: 100vh;
}

.text-primary {
  color: #007bff !important;
}

.btn-lg {
  padding: 0.75rem 2rem;
}
</style>
