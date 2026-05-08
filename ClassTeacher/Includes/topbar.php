<?php 

  $query = "SELECT * FROM tblclassteacher WHERE Id = ".$_SESSION['userId']."";
  $rs = $conn->query($query);
  $num = $rs->num_rows;
  $rows = $rs->fetch_assoc();
  $fullName = $rows['firstName']." ".$rows['lastName'];

?>

<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow-sm border-bottom">

  <!-- Sidebar Toggle -->
  <button id="sidebarToggleTop" class="btn btn-success rounded-circle mr-3 shadow-sm">
    <i class="fa fa-bars text-white"></i>
  </button>

  <!-- System Title -->
  <div class="d-none d-md-flex align-items-center">
    <span class="badge badge-success px-3 py-2 shadow-sm">
      <i class="fas fa-child mr-1"></i>
      Nursery Attendance Management
    </span>
  </div>

  <ul class="navbar-nav ml-auto">

    <!-- Search Dropdown -->
    <li class="nav-item dropdown no-arrow mx-1">
      <a class="nav-link dropdown-toggle text-success" href="#" id="searchDropdown" role="button" data-toggle="dropdown"
        aria-haspopup="true" aria-expanded="false">
        <i class="fas fa-search fa-fw"></i>
      </a>

      <div class="dropdown-menu dropdown-menu-right p-3 shadow animated--grow-in border-0"
        aria-labelledby="searchDropdown">

        <form class="navbar-search">
          <div class="input-group">
            <input 
              type="text" 
              class="form-control bg-light border-success small" 
              placeholder="What do you want to look for?"
              aria-label="Search" 
              aria-describedby="basic-addon2">

            <div class="input-group-append">
              <button class="btn btn-success" type="button">
                <i class="fas fa-search fa-sm"></i>
              </button>
            </div>
          </div>
        </form>

      </div>
    </li>

    <div class="topbar-divider d-none d-sm-block"></div>

    <!-- User Dropdown -->
    <li class="nav-item dropdown no-arrow">

      <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown"
        aria-haspopup="true" aria-expanded="false">

        <img class="img-profile rounded-circle border border-success shadow-sm" src="img/user-icn.png" style="max-width: 60px">

        <span class="ml-2 d-none d-lg-inline text-gray-800 small">
          <b>Welcome <?php echo $fullName;?></b>
        </span>

      </a>

      <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in border-0" aria-labelledby="userDropdown">

        <div class="dropdown-header bg-success text-white">
          <i class="fas fa-user-circle mr-1"></i>
          Class Teacher Account
        </div>

        <div class="dropdown-item-text small text-gray-700">
          <?php echo $fullName;?>
        </div>

        <div class="dropdown-divider"></div>

        <a class="dropdown-item text-danger" href="logout.php">
          <i class="fas fa-power-off fa-fw mr-2 text-danger"></i>
          Logout
        </a>

      </div>

    </li>

  </ul>

</nav>