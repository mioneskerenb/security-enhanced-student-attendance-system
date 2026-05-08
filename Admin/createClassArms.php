<?php 
error_reporting(0);
include '../Includes/dbcon.php';
include '../Includes/session.php';

//------------------------SAVE--------------------------------------------------

if(isset($_POST['save'])){
    
    $classId=$_POST['classId'];
    $classArmName=$_POST['classArmName'];
   
    $query=mysqli_query($conn,"select * from tblclassarms where classArmName ='$classArmName' and classId = '$classId'");
    $ret=mysqli_fetch_array($query);

    if($ret > 0){ 
        $statusMsg = "<div class='alert alert-danger shadow-sm' role='alert'>
                        <i class='fas fa-exclamation-circle mr-2'></i>
                        This Class Arm Already Exists!
                      </div>";
    }
    else{

        $query=mysqli_query($conn,"insert into tblclassarms(classId,classArmName,isAssigned) value('$classId','$classArmName','0')");

        if ($query) {
            $statusMsg = "<div class='alert alert-success shadow-sm' role='alert'>
                            <i class='fas fa-check-circle mr-2'></i>
                            Created Successfully!
                          </div>";
        }
        else
        {
            $statusMsg = "<div class='alert alert-danger shadow-sm' role='alert'>
                            <i class='fas fa-exclamation-circle mr-2'></i>
                            An error Occurred!
                          </div>";
        }
    }
}

//--------------------EDIT------------------------------------------------------------

if (isset($_GET['Id']) && isset($_GET['action']) && $_GET['action'] == "edit")
{
    $Id= $_GET['Id'];

    $query=mysqli_query($conn,"select * from tblclassarms where Id ='$Id'");
    $row=mysqli_fetch_array($query);

    //------------UPDATE-----------------------------

    if(isset($_POST['update'])){
    
        $classId=$_POST['classId'];
        $classArmName=$_POST['classArmName'];

        $query=mysqli_query($conn,"update tblclassarms set classId = '$classId', classArmName='$classArmName' where Id='$Id'");

        if ($query) {
            
            echo "<script type = \"text/javascript\">
            window.location = (\"createClassArms.php\")
            </script>"; 
        }
        else
        {
            $statusMsg = "<div class='alert alert-danger shadow-sm' role='alert'>
                            <i class='fas fa-exclamation-circle mr-2'></i>
                            An error Occurred!
                          </div>";
        }
    }
}

//--------------------------------DELETE------------------------------------------------------------------

if (isset($_GET['Id']) && isset($_GET['action']) && $_GET['action'] == "delete")
{
    $Id= $_GET['Id'];

    $query = mysqli_query($conn,"DELETE FROM tblclassarms WHERE Id='$Id'");

    if ($query == TRUE) {

        echo "<script type = \"text/javascript\">
        window.location = (\"createClassArms.php\")
        </script>";  
    }
    else{

        $statusMsg = "<div class='alert alert-danger shadow-sm' role='alert'>
                        <i class='fas fa-exclamation-circle mr-2'></i>
                        An error Occurred!
                      </div>"; 
    }
}
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

  <?php include 'includes/title.php';?>

  <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="css/ruang-admin.min.css" rel="stylesheet">
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
                Create Class Arms
              </h1>

              <p class="mb-0 text-muted">
                Add, update, and manage class sections or class arms for nursery classes.
              </p>
            </div>

            <ol class="breadcrumb bg-white shadow-sm border">
              <li class="breadcrumb-item">
                <a href="./" class="text-success">Home</a>
              </li>
              <li class="breadcrumb-item active" aria-current="page">
                Create Class Arms
              </li>
            </ol>
          </div>

          <!-- Header Card -->
          <div class="card bg-success text-white shadow mb-4 border-0">
            <div class="card-body py-4">
              <div class="row align-items-center">
                <div class="col-lg-8">
                  <span class="badge badge-light text-success mb-3 px-3 py-2">
                    <i class="fas fa-code-branch mr-1"></i>
                    Nursery Class Arm Management
                  </span>

                  <h3 class="font-weight-bold mb-2">
                    Manage Class Arms
                  </h3>

                  <p class="mb-0">
                    Create and maintain class arms or sections used for assigning teachers and nursery students.
                  </p>
                </div>

                <div class="col-lg-4 text-lg-right mt-4 mt-lg-0">
                  <div class="bg-white text-success rounded shadow-sm p-3 d-inline-block">
                    <i class="fas fa-layer-group fa-2x mb-2"></i>
                    <div class="font-weight-bold">
                      Class Arm Setup
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="row">

            <div class="col-lg-12">

              <!-- Form Card -->
              <div class="card shadow mb-4 border-0">

                <div class="card-header bg-white py-3 d-flex flex-row align-items-center justify-content-between border-bottom">
                  <h6 class="m-0 font-weight-bold text-success">
                    <i class="fas fa-plus-circle mr-2"></i>
                    <?php echo isset($Id) ? "Update Class Arm" : "Create Class Arm"; ?>
                  </h6>

                  <span class="badge badge-success px-3 py-2">
                    <i class="fas fa-edit mr-1"></i>
                    Class Arm Form
                  </span>
                </div>

                <div class="card-body">

                  <?php echo $statusMsg; ?>

                  <form method="post">

                    <div class="form-group row mb-3">

                      <div class="col-xl-6">
                        <label class="form-control-label font-weight-bold">
                          Select Class<span class="text-danger ml-2">*</span>
                        </label>

                        <div class="input-group">
                          <div class="input-group-prepend">
                            <span class="input-group-text bg-success text-white">
                              <i class="fas fa-chalkboard"></i>
                            </span>
                          </div>

                          <?php
                          $qry= "SELECT * FROM tblclass ORDER BY className ASC";
                          $result = $conn->query($qry);
                          $num = $result->num_rows;		

                          if ($num > 0){
                            echo '<select required name="classId" class="form-control">';
                            echo '<option value="">--Select Class--</option>';

                            while ($rows = $result->fetch_assoc()){
                              $selected = "";
                              if (isset($row['classId']) && $row['classId'] == $rows['Id']) {
                                $selected = "selected";
                              }

                              echo '<option value="'.$rows['Id'].'" '.$selected.'>'.$rows['className'].'</option>';
                            }

                            echo '</select>';
                          }
                          ?>  
                        </div>
                      </div>

                      <div class="col-xl-6">
                        <label class="form-control-label font-weight-bold">
                          Class Arm Name<span class="text-danger ml-2">*</span>
                        </label>

                        <div class="input-group">
                          <div class="input-group-prepend">
                            <span class="input-group-text bg-success text-white">
                              <i class="fas fa-code-branch"></i>
                            </span>
                          </div>

                          <input 
                            type="text" 
                            class="form-control" 
                            name="classArmName" 
                            value="<?php echo $row['classArmName'];?>" 
                            id="exampleInputFirstName" 
                            placeholder="Class Arm Name"
                            required>
                        </div>
                      </div>

                    </div>

                    <?php
                    if (isset($Id))
                    {
                    ?>
                      <button type="submit" name="update" class="btn btn-warning shadow-sm px-4">
                        <i class="fas fa-save mr-1"></i>
                        Update
                      </button>

                      <a href="createClassArms.php" class="btn btn-secondary shadow-sm px-4 ml-2">
                        <i class="fas fa-times mr-1"></i>
                        Cancel
                      </a>
                    <?php
                    } else {           
                    ?>
                      <button type="submit" name="save" class="btn btn-success shadow-sm px-4">
                        <i class="fas fa-save mr-1"></i>
                        Save
                      </button>
                    <?php
                    }         
                    ?>

                  </form>
                </div>
              </div>

              <!-- Table Card -->
              <div class="row">
                <div class="col-lg-12">

                  <div class="card shadow mb-4 border-0">

                    <div class="card-header bg-white py-3 d-flex flex-row align-items-center justify-content-between border-bottom">
                      <h6 class="m-0 font-weight-bold text-success">
                        <i class="fas fa-list mr-2"></i>
                        All Class Arms
                      </h6>

                      <span class="badge badge-success px-3 py-2">
                        <i class="fas fa-table mr-1"></i>
                        Records
                      </span>
                    </div>

                    <div class="card-body">

                      <div class="alert alert-success border-left-success shadow-sm" role="alert">
                        <i class="fas fa-info-circle mr-2"></i>
                        This table displays all class arms and their assignment status.
                      </div>

                      <div class="table-responsive">
                        <table class="table table-bordered table-hover table-striped align-items-center" id="dataTableHover">
                          <thead class="thead-light">
                            <tr>
                              <th>#</th>
                              <th>Class Name</th>
                              <th>Class Arm Name</th>
                              <th>Status</th>
                              <th>Edit</th>
                              <th>Delete</th>
                            </tr>
                          </thead>
                        
                          <tbody>

                            <?php
                            $query = "SELECT tblclassarms.Id,tblclassarms.isAssigned,tblclass.className,tblclassarms.classArmName 
                            FROM tblclassarms
                            INNER JOIN tblclass ON tblclass.Id = tblclassarms.classId";

                            $rs = $conn->query($query);
                            $num = $rs->num_rows;
                            $sn=0;
                            $status="";

                            if($num > 0)
                            { 
                              while ($rows = $rs->fetch_assoc())
                              {
                                if($rows['isAssigned'] == '1'){
                                  $status = "Assigned";
                                  $badgeClass = "badge badge-success px-3 py-2";
                                } else {
                                  $status = "UnAssigned";
                                  $badgeClass = "badge badge-secondary px-3 py-2";
                                }

                                $sn = $sn + 1;

                                echo"
                                <tr>
                                  <td>
                                    <span class='badge badge-success'>".$sn."</span>
                                  </td>

                                  <td>
                                    <span class='badge badge-info px-3 py-2'>".$rows['className']."</span>
                                  </td>

                                  <td>
                                    <span class='font-weight-bold text-gray-800'>".$rows['classArmName']."</span>
                                  </td>

                                  <td>
                                    <span class='".$badgeClass."'>".$status."</span>
                                  </td>

                                  <td>
                                    <a href='?action=edit&Id=".$rows['Id']."' class='btn btn-sm btn-warning shadow-sm'>
                                      <i class='fas fa-fw fa-edit'></i>
                                      Edit
                                    </a>
                                  </td>

                                  <td>
                                    <a href='?action=delete&Id=".$rows['Id']."' class='btn btn-sm btn-danger shadow-sm' onclick=\"return confirm('Are you sure you want to delete this class arm?');\">
                                      <i class='fas fa-fw fa-trash'></i>
                                      Delete
                                    </a>
                                  </td>
                                </tr>";
                              }
                            }
                            else
                            {
                              echo   
                              "<tr>
                                <td colspan='6'>
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