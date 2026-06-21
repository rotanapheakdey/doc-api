<template>
  <div>
    <div class="card">
      <div class="card-header">
        <h5 class="card-title">Archive Search</h5>
      </div>
      <div class="card-body">
        <!-- Search Form -->
        <div class="input-group mb-3">
          <input
            v-model="searchQuery"
            type="text"
            class="form-control"
            placeholder="Search by title or control number..."
            @keyup.enter="searchArchive"
          />
          <button class="btn btn-primary" @click="searchArchive" :disabled="loading">
            <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
            <i class="fas fa-search"></i> Search
          </button>
          <button class="btn btn-secondary" @click="clearSearch" v-if="searched">
            <i class="fas fa-times"></i> Clear
          </button>
        </div>

        <!-- Results Info -->
        <div v-if="searched && !loading">
          <div v-if="archiveList.length > 0" class="mb-2">
            <span class="text-muted">
              Found <strong>{{ archiveList.length }}</strong> document(s)
            </span>
            <span class="badge" :class="accessLevel === 'Global' ? 'badge-success' : 'badge-warning'">
              {{ accessLevel }} Access
            </span>
          </div>

          <!-- Results Table -->
          <div class="table-responsive" v-if="archiveList.length > 0">
            <table class="table table-striped">
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
                <tr v-for="doc in archiveList" :key="doc.id">
                  <td>{{ doc.control_no }}</td>
                  <td>{{ doc.title }}</td>
                  <td>
                    <span class="badge badge-secondary">
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
              </tbody>
            </table>
          </div>
        </div>

        <!-- No Results -->
        <div v-else-if="searched && !loading && archiveList.length === 0" class="text-center py-5">
          <i class="fas fa-search fa-3x text-muted"></i>
          <p class="text-muted mt-3">No documents found. Try a different search term.</p>
        </div>

        <!-- Initial State -->
        <div v-else-if="!searched && !loading" class="text-center py-5">
          <i class="fas fa-archive fa-3x text-muted"></i>
          <p class="text-muted mt-3">Enter a search term to find archived documents.</p>
          <small class="text-muted">Search by document title or control number</small>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useDocumentStore } from '@/stores/document'

const router = useRouter()
const route = useRoute()
const documentStore = useDocumentStore()

const searchQuery = ref('')
const searched = ref(false)
const loading = ref(false)
const accessLevel = ref('Restricted')

// Use computed with safe access
const archiveList = computed(() => {
  return documentStore.archiveDocuments || []
})

const searchArchive = async () => {
  if (!searchQuery.value.trim()) {
    alert('Please enter a search term')
    return
  }

  loading.value = true
  searched.value = true

  // ✅ Update URL with search query
  router.replace({ query: { search: searchQuery.value.trim() } })

  try {
    const result = await documentStore.searchArchive(searchQuery.value.trim())
    if (result.success) {
      accessLevel.value = result.accessLevel || 'Restricted'
    } else {
      alert(result.message || 'Search failed')
    }
  } catch (error) {
    alert('Search failed. Please try again.')
  } finally {
    loading.value = false
  }
}

const clearSearch = () => {
  searchQuery.value = ''
  searched.value = false
  documentStore.clearArchive()
  accessLevel.value = 'Restricted'

  // ✅ Remove search query from URL
  router.replace({ query: {} })
}

// ✅ Auto-search if search query is in URL
onMounted(() => {
  const query = route.query.search
  if (query) {
    searchQuery.value = query
    // Auto search on page load
    setTimeout(() => {
      searchArchive()
    }, 300)
  }
})
</script>
