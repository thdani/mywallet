<?php
session_start();
require_once '../config.php';

$messages = array(
    'success' => array(),
    'error' => array()
);

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ../adminlogin/');
    exit();
}

if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $sql_string = "SELECT id FROM admin WHERE deleted = 0 AND user_id = {$_SESSION['user_id']}";
    $query = mysqli_query($connection, $sql_string);
    $query = mysqli_fetch_assoc($query);
    if (empty($query)) {
        header('Location: ../adminlogin/');
        exit();
    }
}
else {
    header('Location: ../adminlogin/');
    exit();
}

if (isset($_GET['del']) && !empty($_GET['del'])) {
    $user_id = (int) mysqli_real_escape_string($connection, $_GET['del']);

    $sql_string = "UPDATE admin SET deleted = 1, deleted_at = '{$now}' WHERE deleted = 0 AND user_id = {$user_id}";

    if (mysqli_query($connection, $sql_string)) {
        $messages['success'][] = 'Success!';
    }
    else {
        $messages['error'][] = 'Error!';
    }
}

if (isset($_POST['add_admin'])) {
    $user_id = isset($_POST['user_id']) && !empty($_POST['user_id']) ? (int) mysqli_real_escape_string($connection, $_POST['user_id']) : 0;

    if ($user_id > 0) {
        $sql_string = "SELECT id FROM admin WHERE deleted = 0 AND user_id = {$user_id}";
        $query = mysqli_query($connection, $sql_string);
        $query = mysqli_fetch_assoc($query);

        if (empty($query)) {
            $sql_string = "INSERT INTO admin SET user_id = {$user_id}, added_at = '{$now}'";
            mysqli_query($connection, $sql_string);

            if (mysqli_affected_rows($connection) > 0) {
                $messages['success'][] = 'Success!';
            }
            else {
                $messages['error'][] = 'Error!';
            }
        }
        else {
            $messages['error'][] = 'This user is already an admin';
        }
    }
}

$sql_string = "SELECT id, first_name, last_name FROM users WHERE deleted = 0";
$query = mysqli_query($connection, $sql_string);

$all_user = array();
while ($row = mysqli_fetch_assoc($query)) {
    $all_user[] = $row;
}

$sql_string = "SELECT admin.id, users.first_name, users.last_name, users.email
FROM admin
LEFT JOIN users
ON users.id = admin.user_id
WHERE admin.deleted = 0 AND users.deleted = 0";
$query = mysqli_query($connection, $sql_string);

$all_admin = array();
while ($row = mysqli_fetch_assoc($query)) {
    $all_admin[] = $row;
}

?>
<!DOCTYPE html>
<html lang="en">

  <head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="The best way to check out your cash flow!">
    <meta name="author" content="Daniel Toth">
    <meta name="keywords" content="money,wallet,money transfer,money control">

    <title>MyWallet - Admin</title>

    <!-- Bootstrap core CSS-->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">

    <!-- Page level plugin CSS-->
    <link href="vendor/datatables/dataTables.bootstrap4.css" rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="css/sb-admin.css" rel="stylesheet">

  </head>

  <body id="page-top">

    <div id="alerts" class="modal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Messages</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <?php
            foreach ($messages['success'] as $msg) {
            ?>
            <div class="alert alert-success" role="alert">
              <?php echo $msg; ?>
            </div>
            <?php
            }
            ?>
            <?php
            foreach ($messages['error'] as $msg) {
            ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $msg; ?>
            </div>
            <?php
            }
            ?>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-primary" data-dismiss="modal">Got it!</button>
          </div>
        </div>
      </div>
    </div>

    <div id="confirm" class="modal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Confirm delete!</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <p>Are you sure?</p>
          </div>
          <div class="modal-footer">
            <button id="btn_yes" type="button" class="btn btn-primary">Yes</button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          </div>
        </div>
      </div>
    </div>

    <!-- nav -->
    <?php require_once 'navbar.html'; ?>

    <div id="wrapper">

      <div id="content-wrapper">

        <div class="container-fluid">
            <h2 class="text-center">MyWallet - Administrators</h2>

            <div class="col-md-3 mx-auto my-4 text-center">
                <form action="" method="post">
                    <h4 class="text-center">Add admin</h4>

                    <label>Users</label>
                    <select name="user_id" class="form-control">
                        <option value="">Choose</option>
                        <?php
                        if (!empty($all_user)) {
                            foreach ($all_user as $user) {
                                echo '<option value="'. $user['id'] . '">'. $user['first_name'] . ' '. $user['last_name'] . '</option>';
                            }
                        }
                        ?>
                    </select>
                    <br />
                    <input type="submit" class="btn btn-primary" value="Add admin" name="add_admin" />
                </form>
            </div>


            <div class="card mb-3">
                <div class="card-header">
                  <i class="fas fa-user"></i>
                  Admin list
              </div>
              <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>E-mail</th>
                                <th class="text-right">Options</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                if (!empty($all_admin)) {
                                    foreach ($all_admin as $admin) {
                                        echo '<tr>';
                                        foreach ($admin as $col => $data) {
                                            if ($col != 'id') {
                                                echo '<td>' . $data . '</td>';
                                            }
                                        }
                                        echo '<td class="text-right">';
                                        echo '<a class="confirm_delete" href="admin.php?del=' . $admin['id'] . '"><img src="../img/delete.png" alt="edit" /></a>';
                                        echo '</td>';
                                        echo '</tr>';
                                    }
                                }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            </div>


        </div>
        <!-- /.container-fluid -->

        <?php require_once 'footer.html'; ?>

      </div>
      <!-- /.content-wrapper -->

    </div>
    <!-- /#wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
      <i class="fas fa-angle-up"></i>
    </a>

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Page level plugin JavaScript-->
    <script src="vendor/chart.js/Chart.min.js"></script>
    <script src="vendor/datatables/jquery.dataTables.js"></script>
    <script src="vendor/datatables/dataTables.bootstrap4.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin.min.js"></script>

    <!-- Demo scripts for this page-->
    <script src="js/demo/datatables-demo.js"></script>
    <script src="js/demo/chart-area-demo.js"></script>

    <?php if (!empty($messages['error']) || !empty($messages['success'])) { ?>
    <script type="text/javascript">
        $(document).ready(function() {
          $('#alerts').modal('show');
        });
    </script>
    <?php } ?>

    <script>
        $('body').on('click', '.confirm_delete', function(e) {
            var btn_pressed = $(this).attr('href');
            e.preventDefault();
            $('#confirm').modal('show');
            $('body').on('click', '#btn_yes', function() {
                window.location.replace(btn_pressed);
            });
        });
    </script>

  </body>
</html>