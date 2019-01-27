<?php
session_start();
require_once '../config.php';
$messages = array(
    'success' => array(),
    'error' => array()
);


if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    header('Location: ../admin/');
    exit();
}

if (isset($_POST['login'])) {
  $email = mysqli_real_escape_string($connection, $_POST['email']);
  $password = mysqli_real_escape_string($connection, $_POST['password']);
  $password = md5(SALT . $password);
  $sql_string = "SELECT id, first_name FROM users WHERE email = '{$email}' AND password = '{$password}'";

  $user_data = mysqli_query($connection, $sql_string);
  $user_data = mysqli_fetch_assoc($user_data);

  if (!empty($user_data)) {
    $_SESSION['user_id'] = $user_data['id'];
    $_SESSION['first_name'] = $user_data['first_name'];
    // $messages['success'][] = 'Successfull login!';
    header('Location: ../admin/');
    exit();
  }
  else {
    $messages['error'][] = 'This user does not exist!';
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

    <title>SB Admin - Login</title>

    <!-- Bootstrap core CSS-->
    <link href="../admin/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom fonts for this template-->
    <link href="../admin/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">

    <!-- Custom styles for this template-->
    <link href="../admin/css/sb-admin.css" rel="stylesheet">

  </head>

  <body class="bg-dark">
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

    <div class="container">
      <div class="card card-login mx-auto mt-5">
        <div class="card-header">Login</div>
        <div class="card-body">
          <form action="" method="post">
            <div class="form-group">
              <div class="form-label-group">
                <input type="email" id="inputEmail" class="form-control" placeholder="Email address" required="required" autofocus="autofocus" name="email">
                <label for="inputEmail">Email address</label>
              </div>
            </div>
            <div class="form-group">
              <div class="form-label-group">
                <input type="password" id="inputPassword" class="form-control" placeholder="Password" required="required" name="password">
                <label for="inputPassword">Password</label>
              </div>
            </div>
            <input type="submit" class="btn btn-primary btn-block" value="Login" name="login" />
          </form>
        </div>
      </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="../admin/vendor/jquery/jquery.min.js"></script>
    <script src="../admin/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="../admin/vendor/jquery-easing/jquery.easing.min.js"></script>

  </body>

</html>
<?php if (!empty($messages['error']) || !empty($messages['success'])) { ?>
<script type="text/javascript">
    $(document).ready(function() {
      $('#alerts').modal('show');
    });
</script>
<?php } ?>