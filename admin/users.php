<?php
session_start();
require_once '../config.php';

$messages = array(
    'success' => array(),
    'error' => array()
);

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: ../adminlogin/');
    exit();
}

if (isset($_GET['del']) && !empty($_GET['del'])) {
    $del = (int) mysqli_real_escape_string($connection, $_GET['del']);

    // Delete
    $sql_string = "UPDATE users SET deleted = 1, deleted_at = '{$now}' WHERE deleted = 0 AND id = {$del}";
    if (mysqli_query($connection, $sql_string)) {
        $messages['success'][] = 'Successfull delete!';
    }
    else {
        $messages['error'][] = 'Something went wrong!';
    }
}

if (isset($_POST['save'])) {
    $error = false;
    $user_id = isset($_POST['id']) ? (int) mysqli_real_escape_string($connection, $_POST['id']) : 0;

    $required = array('first_name', 'last_name', 'email');

    foreach ($required as $input_name) {
        if (!isset($_POST[$input_name]) || empty($_POST[$input_name])) {
            $error = true;
            $messages['error'][] = 'The inputs with * are required!';
        }
    }

    $post = array();
    if (!$error) {
        foreach ($_POST as $key => $value) {
            $post[$key] = mysqli_real_escape_string($connection, $value);
        }
    }

    if (!$error) {
        if (!filter_var($post['email'], FILTER_SANITIZE_EMAIL)) {
            $error = true;
            $messages['error'][] = 'Invalid e-mail address!';
        }
    }

    if (!$error && $user_id == 0) {
        if (strlen($post['password']) < 6) {
            $error = true;
            $messages['error'][] = 'Tha password minimum length is 6 character!';
        }
    }

    if (!$error && $user_id == 0) {
        if ($post['password'] != $post['confirm_password']) {
            $error = true;
            $messages['error'][] = 'The passwords must be the same!';
        }
    }

    if (!$error) {
        if ($user_id == 0) {
            $post['password'] = md5(SALT . $post['password']);
            $post['status_code'] = '';
            unset($post['confirm_password']);
        }
        unset($post['save']);
        $sql_params = array();
        foreach ($post as $column => $c_value) {
            $sql_params[] = "{$column} = '{$c_value}'";
        }

        if ($user_id > 0) {
            // Update
            $type = 'UPDATE';
            $cond = " WHERE id = {$user_id}";
        }
        else {
            // Insert
            $type = 'INSERT INTO';
            $sql_params[] = "reg_date = '{$now}'";
            $cond = '';
        }

        if (!empty($sql_params)) {
            $sql_string = "{$type} users SET " . implode(',', $sql_params) . " {$cond}";
            mysqli_query($connection, $sql_string);
            if (mysqli_affected_rows($connection) > 0) {
                $messages['success'][] = 'Successfull!';
            }
            else {
                $messages['error'][] = 'Error!';
            }
        }
    }
}

$sql_string = "SELECT id, first_name, last_name, email FROM users WHERE deleted = 0";
$query = mysqli_query($connection, $sql_string);

$all_user = array();
while ($row = mysqli_fetch_assoc($query)) {
    $all_user[] = $row;
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

    <title>Users - Admin</title>

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

    <!-- nav -->
    <?php require_once 'navbar.html'; ?>


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

    <div id="edit-modal" class="modal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Edit user</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div id="edit-body" class="modal-body">
          </div>
        </div>
      </div>
    </div>

    <div id="wrapper">

      <div id="content-wrapper">

        <div class="container-fluid">
            <h2 class="text-center">Users - Admin</h2>

            <div class="card mb-3">
                <div class="card-header">
                  <i class="fas fa-user"></i>
                  Users list
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
                                if (!empty($all_user)) {
                                    foreach ($all_user as $user) {
                                        echo '<tr>';
                                        foreach ($user as $col => $data) {
                                            if ($col != 'id') {
                                                echo '<td>' . $data . '</td>';
                                            }
                                        }
                                        echo '<td class="text-right">';
                                        echo '<a class="edit-user" href="javascript:void(0)" data-id="' . $user['id'] . '"><img src="../img/edit.png" alt="edit" /></a>&nbsp;';
                                        echo '<a class="confirm_delete" href="users.php?del=' . $user['id'] . '"><img src="../img/delete.png" alt="edit" /></a>';
                                        echo '</td>';
                                        echo '</tr>';
                                    }
                                }
                            ?>
                        </tbody>
                        <tfoot class="text-right">
                            <tr>
                                <td colspan="4"><a href="javascript:void(0)" class="btn btn-primary add-user">Add user</a></td>
                            </tr>
                        </tfoot>
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

  </body>

</html>
<?php if (!empty($messages['error']) || !empty($messages['success'])) { ?>
<script type="text/javascript">
    $(document).ready(function() {
      $('#alerts').modal('show');
    });
</script>
<?php } ?>

<script type="text/javascript">
    $('document').ready(function() {
        $('body').on('click', '.edit-user', function() {
            var user_id = $(this).data('id');
            var type = 'edit';
            $.ajax({
                url: "../ajax/get_user.php",
                data: {id: user_id, type: type},
                method: "POST",
                success: function(response) {
                    if (response.status == 'success') {
                        $('#edit-modal').modal('show');
                        $('#edit-modal .modal-title').html('Edit user');
                        $('#edit-body').html(response.data);
                    }
                },
                error: function() {

                },
                dataType: "json"
            });
        });

        $('body').on('click', '.add-user', function() {
            // console.log('clicked');
            var type = 'new';
            $.ajax({
                url: "../ajax/get_user.php",
                data: {type: type},
                method: "POST",
                success: function(response) {
                    if (response.status == 'success') {
                        $('#edit-modal').modal('show');
                        $('#edit-modal .modal-title').html('Add user');
                        $('#edit-body').html(response.data);
                    }
                },
                error: function() {

                },
                dataType: "json"
            });
        });

        $('body').on('click', '.close-edit', function() {
            console.log('asdas');
            $('#edit-modal').modal('hide');
        });
    });
</script>

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