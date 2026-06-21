<template>
  <div v-if="documentStore.loading" class="text-center py-5">
    <i class="fas fa-spinner fa-spin fa-2x"></i>
    <p>Loading document...</p>
  </div>

  <div v-else-if="document">
    <div class="row">
      <!-- Document Info -->
      <div class="col-md-8">
        <div class="card">
          <div class="card-header">
            <h5 class="card-title">{{ document.title }}</h5>
            <span class="float-right badge" :class="getStatusClass(document.status)">
              {{ document.status?.replace('_', ' ') }}
            </span>
          </div>
          <div class="card-body">
            <table class="table table-bordered">
              <tbody>
                <tr>
                  <th width="30%">Control No</th>
                  <td>{{ document.control_no }}</td>
                </tr>
                <tr>
                  <th>Title</th>
                  <td>{{ document.title }}</td>
                </tr>
                <tr>
                  <th>Status</th>
                  <td>
                    <span class="badge" :class="getStatusClass(document.status)">
                      {{ document.status?.replace('_', ' ') }}
                    </span>
                  </td>
                </tr>
                <tr>
                  <th>Uploaded By</th>
                  <td>{{ document.uploader?.name || 'N/A' }}</td>
                </tr>
                <tr>
                  <th>Department</th>
                  <td>{{ document.department?.name || 'Not Assigned' }}</td>
                </tr>
                <tr>
                  <th>Comment</th>
                  <td>{{ document.file_dept_comment || 'No comment' }}</td>
                </tr>
                <tr>
                  <th>Created</th>
                  <td>{{ formatDate(document.created_at) }}</td>
                </tr>
                <tr>
                  <th>Updated</th>
                  <td>{{ formatDate(document.updated_at) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="col-md-4">
        <div class="card">
          <div class="card-header">
            <h5 class="card-title">Actions</h5>
          </div>
          <div class="card-body">
            <!-- Download -->
            <button @click="downloadFile" class="btn btn-primary btn-block mb-2">
              <i class="fas fa-download"></i> Download File
            </button>

            <!-- Assign (DG only) -->
            <button v-if="canAssign" @click="openAssignModal" class="btn btn-warning btn-block mb-2">
              <i class="fas fa-share"></i> Assign Department
            </button>

            <!-- Dispatch (File Dept) -->
            <button v-if="canDispatch" @click="openDispatchModal" class="btn btn-info btn-block mb-2">
              <i class="fas fa-paper-plane"></i> Dispatch
            </button>

            <!-- Upload Report (Dept/Staff) -->
            <button v-if="canReport" @click="openReportModal" class="btn btn-success btn-block mb-2">
              <i class="fas fa-upload"></i> Upload Report
            </button>

            <!-- Sign VDG -->
            <button v-if="canVDGSign" @click="signVDG" class="btn btn-primary btn-block mb-2">
              <i class="fas fa-signature"></i> Sign (VDG)
            </button>

            <!-- Sign DG -->
            <button v-if="canDGSign" @click="signDG" class="btn btn-danger btn-block mb-2">
              <i class="fas fa-signature"></i> Sign (DG)
            </button>

            <!-- Archive -->
            <button v-if="canArchive" @click="archiveDocument" class="btn btn-secondary btn-block mb-2">
              <i class="fas fa-archive"></i> Archive
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <AssignModal
      v-if="showAssignModal"
      :document="document"
      @close="showAssignModal = false"
      @success="refreshDocument"
    />

    <DispatchModal
      v-if="showDispatchModal"
      :document="document"
      @close="showDispatchModal = false"
      @success="refreshDocument"
    />

    <ReportModal
      v-if="showReportModal"
      :document="document"
      @close="showReportModal = false"
      @success="refreshDocument"
    />
  </div> <!-- ✅ This was missing! -->

  <div v-else class="text-center py-5">
    <i class="fas fa-file-alt fa-3x text-muted"></i>
    <p class="text-muted mt-3">Document not found</p>
    <router-link to="/documents" class="btn btn-primary">
      <i class="fas fa-arrow-left"></i> Back to Documents
    </router-link>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useDocumentStore } from '@/stores/document'
import AssignModal from '@/components/modals/AssignModal.vue'
import DispatchModal from '@/components/modals/DispatchModal.vue'
import ReportModal from '@/components/modals/ReportModal.vue'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const documentStore = useDocumentStore()

const showAssignModal = ref(false)
const showDispatchModal = ref(false)
const showReportModal = ref(false)

const document = computed(() => documentStore.currentDocument)

// Permission checks
const canAssign = computed(() =>
  authStore.isDG && document.value?.status === 'pending_dg_init'
)
const canDispatch = computed(() =>
  authStore.isFileDept && document.value?.status === 'pending_dispatch'
)
const canReport = computed(() =>
  (authStore.isDepartment || authStore.isStaff) && document.value?.status === 'dg_directed'
)
const canVDGSign = computed(() =>
  authStore.isVDG && document.value?.status === 'pending_vdg_approval'
)
const canDGSign = computed(() =>
  authStore.isDG && document.value?.status === 'pending_dg_approval'
)
const canArchive = computed(() =>
  authStore.isFileDept && document.value?.status === 'dg_signed'
)

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

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleString()
}

const downloadFile = async () => {
  try {
    const result = await documentStore.downloadDocument(document.value.id)
    if (result.success) {
      const blob = new Blob([result.data], { type: 'application/pdf' })
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = `document-${document.value.id}.pdf`
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      window.URL.revokeObjectURL(url)
    } else {
      alert(result.message || 'Download failed')
    }
  } catch (error) {
    alert('Failed to download file')
  }
}

const openAssignModal = () => {
  showAssignModal.value = true
}

const openDispatchModal = () => {
  showDispatchModal.value = true
}

const openReportModal = () => {
  showReportModal.value = true
}

const signVDG = async () => {
  if (!confirm('Sign this document as VDG?')) return
  const result = await documentStore.signVDG(document.value.id)
  if (result.success) {
    alert('Document signed by VDG successfully!')
  } else {
    alert(result.message || 'VDG signature failed')
  }
}

const signDG = async () => {
  if (!confirm('Sign this document as DG?')) return
  const result = await documentStore.signDG(document.value.id)
  if (result.success) {
    alert('Document signed by DG successfully!')
  } else {
    alert(result.message || 'DG signature failed')
  }
}

const archiveDocument = async () => {
  if (!confirm('Archive this document?')) return
  const result = await documentStore.archiveDocument(document.value.id)
  if (result.success) {
    alert('Document archived successfully!')
  } else {
    alert(result.message || 'Archive failed')
  }
}

const refreshDocument = async () => {
  await documentStore.fetchDocument(route.params.id)
}

onMounted(async () => {
  const id = route.params.id
  await documentStore.fetchDocument(id)
  if (!documentStore.currentDocument) {
    router.push('/documents')
  }
})
</script>
