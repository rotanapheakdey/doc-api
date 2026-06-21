<template>
  <div class="modal show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); display: block;">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Dispatch Document</h5>
          <button type="button" class="close" @click="$emit('close')">
            <span>&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div v-if="error" class="alert alert-danger">{{ error }}</div>

          <div class="mb-3">
            <p class="text-muted mb-1"><strong>Document:</strong> {{ props.document?.title }}</p>
            <p class="text-muted"><strong>Control No:</strong> {{ props.document?.control_no }}</p>
          </div>

          <div class="mb-3">
            <label class="form-label">Additional Comment</label>
            <textarea v-model="comment" class="form-control" rows="3" placeholder="Optional comment..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="$emit('close')">Cancel</button>
          <button type="button" class="btn btn-info" @click="submit" :disabled="loading">
            <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
            {{ loading ? 'Dispatching...' : 'Dispatch' }}
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
const comment = ref('')

const submit = async () => {
  loading.value = true
  error.value = ''

  try {
    const result = await documentStore.dispatchDocument(props.document.id, {
      comment: comment.value
    })

    if (result.success) {
      emit('success')
      emit('close')
    } else {
      error.value = result.message || 'Dispatch failed'
    }
  } catch (err) {
    error.value = 'An error occurred. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>
