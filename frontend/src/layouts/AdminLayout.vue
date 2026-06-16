<template>
  <div class="wrapper">
    <Navbar />
    <SideBar />

    <div class="content-wrapper">
      <div class="content-header">
        <div class="container-fluid">
          <div class="row mb-2">
            <div class="col-sm-6">
              <h1 class="m-0">{{ pageTitle }}</h1>
            </div>
          </div>
        </div>
      </div>

      <div class="content">
        <div class="container-fluid">
          <router-view />
        </div>
      </div>
    </div>

    <Footer />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import Navbar from '@/components/common/Navbar.vue'
import SideBar from '@/components/common/Sidebar.vue'
import Footer from '@/components/common/Footer.vue'

const route = useRoute()
const pageTitle = computed(() => route.meta?.title || 'Dashboard')
</script>
