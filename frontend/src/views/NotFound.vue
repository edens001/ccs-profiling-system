<template>
  <div class="not-found">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-md-6 text-center">
          <div class="error-icon">
            <i class="bi bi-exclamation-triangle"></i>
          </div>
          <h1 class="display-1 fw-bold text-primary">404</h1>
          <h2 class="mb-4">Page Not Found</h2>
          <p class="lead mb-4">The page you are looking for doesn't exist or has been moved.</p>
          <router-link to="/" class="btn btn-primary btn-lg">
            <i class="bi bi-house-door me-2"></i>
            Go Home
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'NotFound'
}
</script>

<style scoped>
.not-found {
  min-height: 100vh;
  display: flex;
  align-items: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.error-icon {
  font-size: 5rem;
  margin-bottom: 20px;
}

.error-icon i {
  color: white;
  opacity: 0.8;
}

.btn-primary {
  background: white;
  color: #667eea;
  border: none;
  padding: 12px 30px;
  font-weight: 600;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}
</style>