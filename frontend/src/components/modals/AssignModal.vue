<template>
  <div class="modal show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); display: block;">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Assign Department</h5>
          <button type="button" class="close" @click="$emit('close')">
            <span>&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <!-- Loading -->
          <div v-if="loadingDepartments" class="text-center py-3">
            <span class="spinner-border spinner-border-sm me-2"></span>
            Loading departments...
          </div>

          <!-- Error -->
          <div v-else-if="error" class="alert alert-danger">{{ error }}</div>

          <!-- Form -->
          <template v-else>
            <div class="mb-3">
              <p class="text-muted mb-1"><strong>Document:</strong> {{ props.document?.title }}</p>
              <p class="text-muted"><strong>Control No:</strong> {{ props.document?.control_no }}</p>
            </div>

            <div class="mb-3">
              <label class="form-label">Department <span class="text-danger">*</span></label>
              <select v-model="form.department_id" class="form-select" required>
                <option value="">Select Department</option>
                <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                  {{ dept.name }}
                </option>
              </select>
              <small class="text-muted" v-if="departments.length === 0">
                No departments available.
              </small>
            </div>

            <div class="mb-3">
              <label class="form-label">Note</label>
              <textarea v-model="form.note" class="form-control" rows="3" placeholder="Optional note..."></textarea>
            </div>
          </template>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="$emit('close')">Cancel</button>
          <button type="button" class="btn btn-primary" @click="submit" :disabled="loading || !form.department_id">
            <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
            {{ loading ? 'Assigning...' : 'Assign' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
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
const loadingDepartments = ref(false)
const error = ref('')
const departments = ref([])

const form = reactive({
  department_id: '',
  note: ''
})

const submit = async () => {
  if (!form.department_id) {
    error.value = 'Please select a department'
    return
  }

  loading.value = true
  error.value = ''

  try {
    const result = await documentStore.assignDocument(props.document.id, {
      assigned_department_id: form.department_id,
      dg_note: form.note
    })

    if (result.success) {
      emit('success')
      emit('close')
    } else {
      error.value = result.message || 'Assignment failed'
    }
  } catch (err) {
    error.value = 'An error occurred. Please try again.'
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  loadingDepartments.value = true
  try {
    const result = await documentStore.fetchDepartments()
    if (result) {
      departments.value = result.departments || []
    }
  } catch (err) {
    error.value = 'Failed to load departments'
  } finally {
    loadingDepartments.value = false
  }
})
</script>
