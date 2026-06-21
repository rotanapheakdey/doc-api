<template>
  <div>
    <div class="card">
      <div class="card-header">
        <h5 class="card-title">Upload Document</h5>
      </div>
      <div class="card-body">
        <!-- Success/Error Alert -->
        <div v-if="message" class="alert" :class="messageType === 'success' ? 'alert-success' : 'alert-danger'">
          {{ message }}
          <button type="button" class="close" @click="message = ''">×</button>
        </div>

        <form @submit.prevent="submitUpload">
          <div class="mb-3">
            <label class="form-label">Title <span class="text-danger">*</span></label>
            <input
              v-model="form.title"
              type="text"
              class="form-control"
              :class="{ 'is-invalid': errors.title }"
              placeholder="Enter document title"
              required
            />
            <div v-if="errors.title" class="invalid-feedback">{{ errors.title }}</div>
          </div>

          <div class="mb-3">
            <label class="form-label">File <span class="text-danger">*</span></label>
            <input
              type="file"
              class="form-control"
              :class="{ 'is-invalid': errors.file }"
              @change="handleFileChange"
              accept=".pdf,.doc,.docx"
              required
            />
            <div v-if="errors.file" class="invalid-feedback">{{ errors.file }}</div>
            <small class="text-muted">Supported: PDF, DOC, DOCX. Max size: 10MB</small>
            <div v-if="selectedFile" class="mt-2">
              <span class="badge badge-info">
                <i class="fas fa-file"></i> {{ selectedFile.name }} ({{ formatFileSize(selectedFile.size) }})
              </span>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Comment</label>
            <textarea
              v-model="form.comment"
              class="form-control"
              rows="3"
              placeholder="Optional comment about this document"
            ></textarea>
          </div>

          <button type="submit" class="btn btn-primary" :disabled="loading">
            <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
            {{ loading ? 'Uploading...' : 'Upload Document' }}
          </button>

          <router-link to="/documents" class="btn btn-secondary ms-2 ml-2">
            <i class="fas fa-arrow-left"></i> Cancel
          </router-link>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useDocumentStore } from '@/stores/document'

const router = useRouter()
const documentStore = useDocumentStore()

const loading = ref(false)
const selectedFile = ref(null)
const message = ref('')
const messageType = ref('success')

const form = reactive({
  title: '',
  comment: ''
})

const errors = reactive({
  title: '',
  file: ''
})

const formatFileSize = (bytes) => {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / 1048576).toFixed(1) + ' MB'
}

const handleFileChange = (event) => {
  const file = event.target.files[0]
  if (file) {
    // Check file size (10MB max)
    if (file.size > 10485760) {
      errors.file = 'File size exceeds 10MB limit'
      selectedFile.value = null
      return
    }
    errors.file = ''
    selectedFile.value = file
  }
}

const submitUpload = async () => {
  // Validate
  errors.title = ''
  errors.file = ''
  message.value = ''

  if (!form.title.trim()) {
    errors.title = 'Title is required'
    return
  }
  if (!selectedFile.value) {
    errors.file = 'Please select a file'
    return
  }

  loading.value = true

  try {
    const formData = new FormData()
    formData.append('title', form.title.trim())
    formData.append('file', selectedFile.value)
    formData.append('comment', form.comment.trim() || '')

    const result = await documentStore.uploadDocument(formData)

    if (result.success) {
      messageType.value = 'success'
      message.value = 'Document uploaded successfully!'

      // Reset form
      form.title = ''
      form.comment = ''
      selectedFile.value = null
      document.querySelector('input[type="file"]').value = ''

      // Redirect after 2 seconds
      setTimeout(() => {
        router.push('/documents')
      }, 1500)
    } else {
      messageType.value = 'danger'
      message.value = result.message || 'Upload failed'
    }
  } catch (error) {
    messageType.value = 'danger'
    message.value = error.response?.data?.message || 'Upload failed. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>
