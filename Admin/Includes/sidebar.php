<style>
  .nursery-sidebar {
    background: linear-gradient(180deg, #0f766e 0%, #16a34a 55%, #22c55e 100%) !important;
    box-shadow: 8px 0 28px rgba(15, 118, 110, 0.16);
    border-right: 1px solid rgba(255, 255, 255, 0.16);
    overflow: hidden !important;
    position: relative;
  }

  .nursery-sidebar::before {
    content: "";
    position: absolute;
    inset: 0;
    background:
      radial-gradient(circle at top left, rgba(255, 255, 255, 0.18), transparent 28%),
      radial-gradient(circle at bottom right, rgba(255, 255, 255, 0.13), transparent 32%);
    pointer-events: none;
  }

  .nursery-sidebar .sidebar-brand {
    position: relative;
    z-index: 1;
    min-height: 92px;
    padding: 18px 12px;
    background: rgba(255, 255, 255, 0.13) !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.18);
  }

  .nursery-sidebar .sidebar-brand-icon {
    width: 46px;
    height: 46px;
    min-width: 46px;
    border-radius: 16px;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 12px 25px rgba(15, 23, 42, 0.16);
    overflow: hidden;
  }

  .nursery-sidebar .sidebar-brand-icon img {
    width: 36px;
    height: 36px;
    object-fit: cover;
    border-radius: 12px;
  }

  .nursery-sidebar .sidebar-brand-text {
    color: #ffffff;
    font-size: 17px;
    font-weight: 900;
    letter-spacing: 0.5px;
    white-space: normal;
    line-height: 1.2;
  }

  .nursery-sidebar .sidebar-divider {
    border-top: 1px solid rgba(255, 255, 255, 0.17) !important;
    margin-left: 16px;
    margin-right: 16px;
  }

  .nursery-sidebar .sidebar-heading {
    position: relative;
    z-index: 1;
    color: rgba(255, 255, 255, 0.76) !important;
    font-size: 11px;
    font-weight: 900;
    letter-spacing: 1px;
    text-transform: uppercase;
    padding: 10px 22px 6px;
  }

  .nursery-sidebar .nav-item {
    position: relative;
    z-index: 1;
    margin: 5px 14px;
    max-width: calc(100% - 28px);
  }

  .nursery-sidebar .nav-link {
    color: rgba(255, 255, 255, 0.92) !important;
    border-radius: 16px;
    padding: 12px 12px !important;
    font-weight: 700;
    transition: all 0.22s ease;
    width: 100%;
    max-width: 100%;
    display: flex !important;
    align-items: center;
    min-height: 46px;
    box-sizing: border-box;
    overflow: hidden;
  }

  .nursery-sidebar .nav-link i {
    color: rgba(255, 255, 255, 0.92) !important;
    width: 22px;
    min-width: 22px;
    text-align: center;
    margin-right: 8px;
    font-size: 15px;
  }

  .nursery-sidebar .nav-link span {
    font-size: 14px;
    white-space: normal;
    line-height: 1.2;
    overflow-wrap: break-word;
  }

  .nursery-sidebar .nav-link:hover {
    background: rgba(255, 255, 255, 0.18);
    color: #ffffff !important;
    transform: none;
  }

  .nursery-sidebar .nav-link:hover i {
    color: #fde68a !important;
  }

  .nursery-sidebar .nav-item.active .nav-link {
    background: #ffffff;
    color: #0f766e !important;
    box-shadow: 0 12px 25px rgba(15, 23, 42, 0.15);
  }

  .nursery-sidebar .nav-item.active .nav-link i {
    color: #0f766e !important;
  }

  .nursery-sidebar .collapse {
    width: 100%;
    max-width: 100%;
  }

  .nursery-sidebar .collapse-inner {
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
    border-radius: 16px !important;
    margin: 8px 0 10px;
    padding: 10px 8px !important;
    background: rgba(255, 255, 255, 0.98) !important;
    box-shadow: 0 14px 30px rgba(15, 23, 42, 0.16);
    overflow: hidden !important;
  }

  .nursery-sidebar .collapse-header {
    color: #0f766e !important;
    font-size: 11px;
    font-weight: 900;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    padding: 8px 10px;
    white-space: normal;
  }

  .nursery-sidebar .collapse-item {
    border-radius: 12px;
    padding: 10px 10px !important;
    color: #334155 !important;
    font-size: 13px;
    font-weight: 700;
    margin: 3px 0;
    transition: all 0.22s ease;
    white-space: normal !important;
    line-height: 1.2;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
  }

  .nursery-sidebar .collapse-item:hover {
    background: #ecfdf5 !important;
    color: #0f766e !important;
    transform: none;
  }

  .nursery-sidebar .nav-link[data-toggle="collapse"]::after {
    color: rgba(255, 255, 255, 0.85) !important;
    margin-left: auto;
    flex-shrink: 0;
  }

  .nursery-sidebar .nav-link[data-toggle="collapse"]:hover::after {
    color: #fde68a !important;
  }

  @media (max-width: 768px) {
    .nursery-sidebar .sidebar-brand {
      padding: 14px 6px;
    }

    .nursery-sidebar .sidebar-brand-icon {
      width: 40px;
      height: 40px;
      min-width: 40px;
    }

    .nursery-sidebar .sidebar-brand-icon img {
      width: 32px;
      height: 32px;
    }

    .nursery-sidebar .sidebar-brand-text {
      font-size: 12px;
      margin-left: 6px !important;
      margin-right: 0 !important;
    }

    .nursery-sidebar .nav-item {
      margin: 5px 8px;
      max-width: calc(100% - 16px);
    }

    .nursery-sidebar .nav-link {
      text-align: center;
      justify-content: center;
      padding: 11px 6px !important;
      min-height: 44px;
    }

    .nursery-sidebar .nav-link i {
      margin-right: 0;
    }

    .nursery-sidebar .nav-link span {
      display: none;
    }

    .nursery-sidebar .sidebar-heading {
      text-align: center;
      padding-left: 6px;
      padding-right: 6px;
      font-size: 9px;
    }
  }
</style>

<ul class="navbar-nav sidebar sidebar-light accordion nursery-sidebar" id="accordionSidebar">

  <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.php">
    <div class="sidebar-brand-icon">
      <img src="img/logo/attnlg.jpg" alt="AMS Logo">
    </div>
    <div class="sidebar-brand-text mx-3">AMS Admin</div>
  </a>

  <hr class="sidebar-divider my-0">

  <li class="nav-item active">
    <a class="nav-link" href="index.php">
      <i class="fas fa-fw fa-tachometer-alt"></i>
      <span>Dashboard</span>
    </a>
  </li>

  <hr class="sidebar-divider">

  <div class="sidebar-heading">
    Class and Class Arms
  </div>

  <li class="nav-item">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseBootstrap"
      aria-expanded="true" aria-controls="collapseBootstrap">
      <i class="fas fa-chalkboard"></i>
      <span>Manage Classes</span>
    </a>

    <div id="collapseBootstrap" class="collapse" aria-labelledby="headingBootstrap" data-parent="#accordionSidebar">
      <div class="bg-white py-2 collapse-inner rounded">
        <h6 class="collapse-header">Manage Classes</h6>
        <a class="collapse-item" href="createClass.php">Create Class</a>
      </div>
    </div>
  </li>

  <li class="nav-item">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseBootstrapusers"
      aria-expanded="true" aria-controls="collapseBootstrapusers">
      <i class="fas fa-code-branch"></i>
      <span>Manage Class Arms</span>
    </a>

    <div id="collapseBootstrapusers" class="collapse" aria-labelledby="headingBootstrapusers" data-parent="#accordionSidebar">
      <div class="bg-white py-2 collapse-inner rounded">
        <h6 class="collapse-header">Manage Class Arms</h6>
        <a class="collapse-item" href="createClassArms.php">Create Class Arms</a>
      </div>
    </div>
  </li>

  <hr class="sidebar-divider">

  <div class="sidebar-heading">
    Teachers
  </div>

  <li class="nav-item">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseBootstrapassests"
      aria-expanded="true" aria-controls="collapseBootstrapassests">
      <i class="fas fa-chalkboard-teacher"></i>
      <span>Manage Teachers</span>
    </a>

    <div id="collapseBootstrapassests" class="collapse" aria-labelledby="headingBootstrapassests" data-parent="#accordionSidebar">
      <div class="bg-white py-2 collapse-inner rounded">
        <h6 class="collapse-header">Manage Class Teachers</h6>
        <a class="collapse-item" href="createClassTeacher.php">Create Class Teachers</a>
      </div>
    </div>
  </li>

  <li class="nav-item">
    <a class="nav-link" href="createAdmin.php">
      <i class="fas fa-user-shield"></i>
      <span>Create Admin</span>
    </a>
  </li>

  <hr class="sidebar-divider">

  <div class="sidebar-heading">
    Students
  </div>

  <li class="nav-item">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseBootstrap2"
      aria-expanded="true" aria-controls="collapseBootstrap2">
      <i class="fas fa-user-graduate"></i>
      <span>Manage Students</span>
    </a>

    <div id="collapseBootstrap2" class="collapse" aria-labelledby="headingBootstrap2" data-parent="#accordionSidebar">
      <div class="bg-white py-2 collapse-inner rounded">
        <h6 class="collapse-header">Manage Students</h6>
        <a class="collapse-item" href="createStudents.php">Create Students</a>
      </div>
    </div>
  </li>

  <hr class="sidebar-divider">

  <div class="sidebar-heading">
    Session & Term
  </div>

  <li class="nav-item">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseBootstrapcon"
      aria-expanded="true" aria-controls="collapseBootstrapcon">
      <i class="fa fa-calendar-alt"></i>
      <span>Manage Session & Term</span>
    </a>

    <div id="collapseBootstrapcon" class="collapse" aria-labelledby="headingBootstrapcon" data-parent="#accordionSidebar">
      <div class="bg-white py-2 collapse-inner rounded">
        <h6 class="collapse-header">Manage Session & Term</h6>
        <a class="collapse-item" href="createSessionTerm.php">Create Session and Term</a>
      </div>
    </div>
  </li>

  <hr class="sidebar-divider">

</ul>