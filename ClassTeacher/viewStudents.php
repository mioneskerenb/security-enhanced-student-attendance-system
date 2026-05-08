<?php 
error_reporting(0);
include '../Includes/dbcon.php';
include '../Includes/session.php';

$query = "SELECT tblclass.className,tblclassarms.classArmName 
    FROM tblclassteacher
    INNER JOIN tblclass ON tblclass.Id = tblclassteacher.classId
    INNER JOIN tblclassarms ON tblclassarms.Id = tblclassteacher.classArmId
    Where tblclassteacher.Id = '$_SESSION[userId]'";

    $rs = $conn->query($query);
    $num = $rs->num_rows;
    $rrw = $rs->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  
  <meta name="description" content="">
  <meta name="author" content="">

  <link href="img/logo/attnlg.jpg" rel="icon">
  <title>All Students in Class</title>

  <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="css/ruang-admin.min.css" rel="stylesheet">

  <script>
    function classArmDropdown(str) {
      if (str == "") {
          document.getElementById("txtHint").innerHTML = "";
          return;
      } else { 
          if (window.XMLHttpRequest) {
              // code for IE7+, Firefox, Chrome, Opera, Safari
              xmlhttp = new XMLHttpRequest();
          } else {
              // code for IE6, IE5
              xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
          }
          xmlhttp.onreadystatechange = function() {
              if (this.readyState == 4 && this.status == 200) {
                  document.getElementById("txtHint").innerHTML = this.responseText;
              }
          };
          xmlhttp.open("GET","ajaxClassArms2.php?cid="+str,true);
          xmlhttp.send();
      }
    }
  </script>
</head>

<body id="page-top">

  <div id="wrapper">

    <!-- Sidebar -->
    <?php include "Includes/sidebar.php";?>
    <!-- Sidebar -->

    <div id="content-wrapper" class="d-flex flex-column bg-light">

      <div id="content">

        <!-- TopBar -->
        <?php include "Includes/topbar.php";?>
        <!-- Topbar -->

        <!-- Container Fluid-->
        <div class="container-fluid" id="container-wrapper">

          <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <div>
              <h1 class="h3 mb-0 text-gray-800 font-weight-bold">
                All Students in Class
              </h1>

              <p class="mb-0 text-muted">
                <?php echo $rrw['className'].' - '.$rrw['classArmName'];?> Class
              </p>
            </div>

            <ol class="breadcrumb bg-white shadow-sm border">
              <li class="breadcrumb-item">
                <a href="./" class="text-success">Home</a>
              </li>
              <li class="breadcrumb-item active" aria-current="page">
                All Student in Class
              </li>
            </ol>
          </div>

          <!-- Nursery Header Card -->
          <div class="card bg-success text-white shadow mb-4 border-0">
            <div class="card-body py-4">
              <div class="row align-items-center">
                <div class="col-lg-8">
                  <span class="badge badge-light text-success mb-3 px-3 py-2">
                    <i class="fas fa-user-graduate mr-1"></i>
                    Nursery Student Records
                  </span>

                  <h3 class="font-weight-bold mb-2">
                    Student List Management
                  </h3>

                  <p class="mb-0">
                    View all nursery students assigned to your class and class arm.
                    This page displays student names, admission numbers, and class information.
                  </p>
                </div>

                <div class="col-lg-4 text-lg-right mt-4 mt-lg-0">
                  <div class="bg-white text-success rounded shadow-sm p-3 d-inline-block">
                    <i class="fas fa-school fa-2x mb-2"></i>
                    <div class="font-weight-bold">
                      <?php echo $rrw['className'].' - '.$rrw['classArmName'];?>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-lg-12">

              <div class="card shadow mb-4 border-0">

                <div class="card-header bg-white py-3 d-flex flex-row align-items-center justify-content-between border-bottom">
                  <h6 class="m-0 font-weight-bold text-success">
                    <i class="fas fa-users mr-2"></i>
                    All Student In Class
                  </h6>

                  <span class="badge badge-success px-3 py-2">
                    <i class="fas fa-list mr-1"></i>
                    Student Records
                  </span>
                </div>

                <div class="card-body">

                  <div class="alert alert-success border-left-success shadow-sm" role="alert">
                    <i class="fas fa-info-circle mr-2"></i>
                    This table shows only the students under your assigned class and class arm.
                  </div>

                  <div class="table-responsive">
                    <table class="table table-bordered table-hover table-striped align-items-center" id="dataTableHover">
                      <thead class="thead-light">
                        <tr>
                          <th>#</th>
                          <th>First Name</th>
                          <th>Last Name</th>
                          <th>Other Name</th>
                          <th>Admission No</th>
                          <th>Class</th>
                          <th>Class Arm</th>
                        </tr>
                      </thead>
                      
                      <tbody>

                        <?php
                          $query = "SELECT tblstudents.Id,tblclass.className,tblclassarms.classArmName,tblclassarms.Id AS classArmId,tblstudents.firstName,
                          tblstudents.lastName,tblstudents.otherName,tblstudents.admissionNumber,tblstudents.dateCreated
                          FROM tblstudents
                          INNER JOIN tblclass ON tblclass.Id = tblstudents.classId
                          INNER JOIN tblclassarms ON tblclassarms.Id = tblstudents.classArmId
                          where tblstudents.classId = '$_SESSION[classId]' and tblstudents.classArmId = '$_SESSION[classArmId]'";
                          $rs = $conn->query($query);
                          $num = $rs->num_rows;
                          $sn=0;
                          $status="";
                          if($num > 0)
                          { 
                            while ($rows = $rs->fetch_assoc())
                              {
                                $sn = $sn + 1;
                                echo"
                                  <tr>
                                    <td>
                                      <span class='badge badge-success'>".$sn."</span>
                                    </td>
                                    <td>".$rows['firstName']."</td>
                                    <td>".$rows['lastName']."</td>
                                    <td>".$rows['otherName']."</td>
                                    <td>
                                      <span class='badge badge-primary px-3 py-2'>".$rows['admissionNumber']."</span>
                                    </td>
                                    <td>
                                      <span class='badge badge-info px-3 py-2'>".$rows['className']."</span>
                                    </td>
                                    <td>
                                      <span class='badge badge-success px-3 py-2'>".$rows['classArmName']."</span>
                                    </td>
                                  </tr>";
                              }
                          }
                          else
                          {
                            echo   
                            "<tr>
                              <td colspan='7'>
                                <div class='alert alert-danger mb-0' role='alert'>
                                  <i class='fas fa-exclamation-circle mr-2'></i>
                                  No Record Found!
                                </div>
                              </td>
                            </tr>";
                          }
                        ?>

                      </tbody>
                    </table>
                  </div>

                </div>
              </div>

            </div>
          </div>
          <!--Row-->

        </div>
        <!---Container Fluid-->

      </div>

      <!-- Footer -->
      <?php include "Includes/footer.php";?>
      <!-- Footer -->

    </div>
  </div>

  <!-- Scroll to top -->
  <a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
  </a>

  <script src="../vendor/jquery/jquery.min.js"></script>
  <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
  <script src="js/ruang-admin.min.js"></script>

  <!-- Page level plugins -->
  <script src="../vendor/datatables/jquery.dataTables.min.js"></script>
  <script src="../vendor/datatables/dataTables.bootstrap4.min.js"></script>

  <!-- Page level custom scripts -->
  <script>
    $(document).ready(function () {
      $('#dataTable').DataTable(); // ID From dataTable 
      $('#dataTableHover').DataTable(); // ID From dataTable with Hover
    });
  </script>

</body>

</html>