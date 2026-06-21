<template>
  <div class="modal show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); display: block;">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Upload Report</h5>
          <button type="button" class="close" @click="$emit('close')">
            <span>&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div v-if="error" class="alert alert-danger">{{ error }}</div>
          <div v-if="success" class="alert alert-success">{{ success }}</div>

          <div class="mb-3">
            <p class="text-muted mb-1"><strong>Document:</strong> {{ props.document?.title }}</p>
            <p class="text-muted"><strong>Control No:</strong> {{ props.document?.control_no }}</p>
          </div>

          <div class="mb-3">
            <label class="form-label">Report File <span class="text-danger">*</span></label>
            <input
              type="file"
              class="form-control"
              :class="{ 'is-invalid': fileError }"
              @change="handleFileChange"
              accept=".pdf,.doc,.docx"
            />
            <div v-if="fileError" class="invalid-feedback">{{ fileError }}</div>
            <small class="text-muted">Supported: PDF, DOC, DOCX. Max size: 10MB</small>
            <div v-if="selectedFile" class="mt-2">
              <span class="badge bg-info text-white">
                <i class="fas fa-file"></i> {{ selectedFile.name }}
              </span>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="$emit('close')">Cancel</button>
          <button type="button" class="btn btn-success" @click="submit" :disabled="loading">
            <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
            {{ loading ? 'Uploading...' : 'Upload Report' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useDocumentStore } from '@/stores/document'

const props = defineProps({
  document: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'success'])

const documentStore = useDocumentStore()
const loading = ref(false)
const error = ref('')
const success = ref('')
const fileError = ref('')
const selectedFile = ref(null)

const handleFileChange = (event) => {
  const file = event.target.files[0]
  if (file) {
    if (file.size > 10485760) {
      fileError.value = 'File size exceeds 10MB limit'
      selectedFile.value = null
      return
    }
    fileError.value = ''
    selectedFile.value = file
  }
}

const submit = async () => {
  if (!selectedFile.value) {
    fileError.value = 'Please select a file'
    return
  }

  loading.value = true
  error.value = ''
  success.value = ''

  try {
    const formData = new FormData()
    formData.append('report', selectedFile.value)

    const result = await documentStore.uploadReport(props.document.id, formData)

    if (result.success) {
      success.value = 'Report uploaded successfully!'
      emit('success')
      setTimeout(() => emit('close'), 1500)
    } else {
      error.value = result.message || 'Upload failed'
    }
  } catch (err) {
    error.value = 'An error occurred. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>
