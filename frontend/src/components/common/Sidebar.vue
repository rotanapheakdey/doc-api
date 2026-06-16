<template>
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="/" class="brand-link">
      <span class="brand-text font-weight-light">📄 DMS</span>
    </a>

    <div class="sidebar">
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <img src="https://adminlte.io/themes/v3/dist/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image">
        </div>
        <div class="info">
          <a href="#" class="d-block">John Doe</a>
        </div>
      </div>

      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column">
          <li class="nav-item">
            <router-link to="/" class="nav-link">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>Dashboard</p>
            </router-link>
          </li>
          <li class="nav-item">
            <router-link to="" class="nav-link">
              <i class="nav-icon fas fa-file-alt"></i>
              <p>Documents</p>
            </router-link>
          </li>
          <li class="nav-item">
            <router-link to="" class="nav-link">
              <i class="nav-icon fas fa-upload"></i>
              <p>Upload</p>
            </router-link>
          </li>
          <li class="nav-item">
            <router-link to="" class="nav-link">
              <i class="nav-icon fas fa-archive"></i>
              <p>Archive</p>
            </router-link>
          </li>
          <li class="nav-item">
            <router-link to="" class="nav-link">
              <i class="nav-icon fas fa-user"></i>
              <p>Profile</p>
            </router-link>
          </li>
        </ul>
      </nav>
    </div>
  </aside>
</template>
