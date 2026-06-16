<template>
  <div>
    <div class="row">
      <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
          <div class="inner">
            <h3>150</h3>
            <p>Total Documents</p>
          </div>
          <div class="icon">
            <i class="fas fa-file-alt"></i>
          </div>
          <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
      </div>

      <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
          <div class="inner">
            <h3>53</h3>
            <p>Pending</p>
          </div>
          <div class="icon">
            <i class="fas fa-clock"></i>
          </div>
          <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
      </div>

      <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
          <div class="inner">
            <h3>44</h3>
            <p>Archived</p>
          </div>
          <div class="icon">
            <i class="fas fa-archive"></i>
          </div>
          <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
      </div>

      <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
          <div class="inner">
            <h3>12</h3>
            <p>Urgent</p>
          </div>
          <div class="icon">
            <i class="fas fa-exclamation-triangle"></i>
          </div>
          <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <h5 class="card-title">Recent Documents</h5>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
              </button>
            </div>
          </div>
          <div class="card-body">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Control No</th>
                  <th>Title</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>DOC-2024-001</td>
                  <td>Budget Report</td>
                  <td><span class="badge badge-warning">Pending</span></td>
                </tr>
                <tr>
                  <td>DOC-2024-002</td>
                  <td>Project Proposal</td>
                  <td><span class="badge badge-success">Approved</span></td>
                </tr>
                <tr>
                  <td>DOC-2024-003</td>
                  <td>Annual Review</td>
                  <td><span class="badge badge-danger">Urgent</span></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <h5 class="card-title">Quick Actions</h5>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6">
                <router-link to="/upload" class="btn btn-primary btn-block mb-2">
                  <i class="fas fa-upload"></i> Upload Document
                </router-link>
              </div>
              <div class="col-md-6">
                <router-link to="/archive" class="btn btn-info btn-block mb-2">
                  <i class="fas fa-search"></i> Search Archive
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
