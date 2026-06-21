<template>
  <div>
    <div class="card">
      <div class="card-header">
        <h5 class="card-title">All Documents</h5>
        <div class="card-tools">
          <router-link v-if="authStore.canUpload" to="/upload" class="btn btn-primary btn-sm">
            <i class="fas fa-upload"></i> Upload
          </router-link>
        </div>
      </div>
      <div class="card-body p-0">
        <div v-if="documentStore.loading" class="text-center py-5">
          <i class="fas fa-spinner fa-spin fa-2x"></i>
          <p>Loading documents...</p>
        </div>

        <table v-else class="table table-striped">
          <thead>
            <tr>
              <th>Control No</th>
              <th>Title</th>
              <th>Status</th>
              <th>Uploaded By</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="doc in documents" :key="doc.id">
              <td>{{ doc.control_no }}</td>
              <td>{{ doc.title }}</td>
              <td>
                <span class="badge" :class="getStatusClass(doc.status)">
                  {{ doc.status?.replace('_', ' ') }}
                </span>
              </td>
              <td>{{ doc.uploader?.name || 'N/A' }}</td>
              <td>
                <router-link :to="`/documents/${doc.id}`" class="btn btn-sm btn-primary">
                  <i class="fas fa-eye"></i>
                </router-link>
              </td>
            </tr>
            <tr v-if="documents.length === 0">
              <td colspan="5" class="text-center text-muted">
                {{ loadingMessage }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useDocumentStore } from '@/stores/document'

const authStore = useAuthStore()
const documentStore = useDocumentStore()

// Use computed to get documents based on role
const documents = computed(() => {
  const userRole = authStore.userRole

  // If DG, show urgent documents (pending_dg_init + pending_dg_approval)
  if (userRole === 'dg') {
    return documentStore.urgentDocuments || []
  }

  // If File Dept, show all documents
  if (userRole === 'file_dept') {
    return documentStore.documents || []
  }

  // If VDG, Department, Staff - show inbox documents
  if (['vdg', 'department', 'staff'].includes(userRole)) {
    return documentStore.inboxDocuments || []
  }

  return documentStore.documents || []
})

const loadingMessage = computed(() => {
  const userRole = authStore.userRole

  if (userRole === 'dg') {
    return 'No urgent documents awaiting your action'
  }
  if (userRole === 'file_dept') {
    return 'No documents found'
  }
  if (['vdg', 'department', 'staff'].includes(userRole)) {
    return 'No documents in your department inbox'
  }
  return 'No documents found'
})

const getStatusClass = (status) => {
  const classes = {
    'pending_dg_init': 'badge-danger',
    'pending_dispatch': 'badge-warning',
    'dg_directed': 'badge-primary',
    'pending_vdg_approval': 'badge-warning',
    'pending_dg_approval': 'badge-warning',
    'dg_signed': 'badge-success',
    'completed_archive': 'badge-secondary'
  }
  return classes[status] || 'badge-secondary'
}

onMounted(async () => {
  const userRole = authStore.userRole

  // Fetch appropriate data based on role
  if (userRole === 'dg') {
    // DG sees urgent feed
    await documentStore.fetchUrgent()
  } else if (['vdg', 'department', 'staff'].includes(userRole)) {
    // Department users see inbox
    await documentStore.fetchInbox()
  } else {
    // File Dept sees all documents
    await documentStore.fetchDocuments()
  }
})
</script>
