<?php
session_start();
require_once 'config.php';


$show_form = array(
    'transfer_manager' => false,
    'wallet_manager' => false,
    'category_manager' => false,
    'category_show' => false
);


$messages = array(
    'success' => array(),
    'error' => array()
);


if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ../home/');
    exit();
}


if (isset($_POST['login']) && !isset($_SESSION['user_id'])) {
    
  $email = mysqli_real_escape_string($connection, $_POST['email']);
  $password = mysqli_real_escape_string($connection, $_POST['password']);
  
  $password = md5(SALT . $password);
  $sql_string = "SELECT id, first_name FROM users WHERE email = '{$email}' AND password = '{$password}'";

  $user_data = mysqli_query($connection, $sql_string);
  $user_data = mysqli_fetch_assoc($user_data);

  if (!empty($user_data)) {
    
    $_SESSION['user_id'] = $user_data['id'];
    $_SESSION['first_name'] = $user_data['first_name'];
    $messages['success'][] = 'Successfull login!';
    $sql_string = "INSERT INTO login_statistics SET logged_at = '{$now}', added_at = '{$now}'";
    mysqli_query($connection, $sql_string);
  }
  else {
    $messages['error'][] = 'This user does not exist!';
  }
}


if (isset($_POST['registration'])) {
  $error = false;

  
  $required = array('first_name', 'last_name', 'email', 'password', 'confirm_password');

 
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


  if (!$error) {
    if (strlen($post['password']) < 6) {
        $error = true;
        $messages['error'][] = 'Tha password minimum length is 6 character!';
    }
  }

  
  if (!$error) {
    if ($post['password'] != $post['confirm_password']) {
        $error = true;
        $messages['error'][] = 'The passwords must be the same!';
    }
  }

  if (!$error) {
    
    
    $post['password'] = md5(SALT . $post['password']);
    
    unset($post['confirm_password']);
    unset($post['registration']);
    $sql_params = array();
    foreach ($post as $column => $c_value) {
    
        $sql_params[] = "{$column} = '{$c_value}'";
    }

    if (!empty($sql_params)) {
       
        $sql_params[] = "reg_date = '{$now}'";
        $sql_params[] = "status_code = '" . md5($now) . "'";
        $sql_string = "INSERT INTO users SET " . implode(',', $sql_params);
        mysqli_query($connection, $sql_string);
        if (mysqli_affected_rows($connection) > 0) {
            $messages['success'][] = 'Registration successfull!';
        }
        else {
            $messages['error'][] = 'Error during the registration!';
        }
    }
  }
}


if (isset($_GET['transfer'])) {
    
    resetScroll();
    $scroll_to['transfer_manager'] = true;
    $show_form['transfer_manager'] = true;

    
    $id = isset($_GET['id']) ? (int) mysqli_real_escape_string($connection, $_GET['id']) : 0;

    if (!empty($id)) {
        $sql_string = "SELECT category_id, wallet_id, title, description, value FROM money_transfer WHERE deleted = 0 AND id = {$id}";
        $query = mysqli_query($connection, $sql_string);

        $current_transfer = mysqli_fetch_assoc($query);
    }
}


if (isset($_POST['add_transfer'])) {
    $error = false;
    $id = isset($_GET['id']) ? (int) mysqli_real_escape_string($connection, $_GET['id']) : 0;

   
    $required = array('category_id', 'wallet_id', 'title', 'value');

    foreach ($required as $input_name) {
        if (!isset($_POST[$input_name]) || empty($_POST[$input_name])) {
            $error = true;
            $messages['error'][] = 'The inputs with * are required!';
        }
    }

    
    $post = array();
    if (!$error) {
        foreach ($_POST as $key => $value) {
            if ($key != 'add_transfer') {
                $post[$key] = mysqli_real_escape_string($connection, $value);
            }
        }
    }

   
    if (!$error) {
        if ($post['value'] <= 0) {
            $error = true;
            $messages['error'][] = 'The price must be greater than 0!';
        }

        if (!is_numeric($post['value'])) {
            $error = true;
            $messages['error'][] = 'The price must be a positive number!';
        }
    }

    if (!$error) {
        
        if (!empty($id)) {
            
            $type = 'UPDATE';
            $transfer_cond = 'deleted = 0 AND id = ' . $id;
        }
        else {
           
            $type = 'INSERT INTO';
            $post['added_at'] = $now;
            $transfer_cond = '';
        }

        $sql_params = array();
        foreach ($post as $column => $value) {
            if ($column == 'value') {
                $sql_params[] = "{$column} = {$value}";
            }
            else {
                $sql_params[] = "{$column} = '{$value}'";
            }
        }

        $sql_string = "{$type} money_transfer SET " . implode(',', $sql_params) . (!empty($transfer_cond) ? ' WHERE ' . $transfer_cond : '');

        if (mysqli_query($connection, $sql_string)) {
            $messages['success'][] = 'Successfull ' . (empty($id) ? 'insert!' : 'update!');
            resetScroll();
            $scroll_to['money'] = true;
            $show_form['transfer_manager'] = false;
        }
        else {
            $messages['error'][] = 'Something went wrong! Please try it later!';
            $show_form['transfer_manager'] = true;
            $current_transfer = $_POST;
        }
    }
}



if (isset($_GET['wallet']) && !empty($_GET['wallet'])) {
    resetScroll();
    $scroll_to['wallet_manager'] = true;
    $show_form['wallet_manager'] = true;

    $edit_id = $_GET['wallet'] != 'new' ? (int) mysqli_real_escape_string($connection, $_GET['wallet']) : 0;

    if (!empty($edit_id)) {
        $sql_string = "SELECT title, currency FROM wallets WHERE deleted = 0 AND id = {$edit_id}";
        $query = mysqli_query($connection, $sql_string);

        $edit_wallet = mysqli_fetch_assoc($query);
    }
}

if (isset($_POST['add_wallet'])) {
    $error = false;
    $id = (isset($_GET['wallet']) && $_GET['wallet'] != 'new') ? (int) mysqli_real_escape_string($connection, $_GET['wallet']) : 0;

    $required = array('title', 'currency');

    foreach ($required as $input_name) {
        if (!isset($_POST[$input_name]) || empty($_POST[$input_name])) {
            $error = true;
            $messages['error'][] = 'The inputs with * are required!';
        }
    }

    $post = array();
    if (!$error) {
        foreach ($_POST as $key => $value) {
            if ($key != 'add_wallet') {
                $post[$key] = mysqli_real_escape_string($connection, $value);
            }
        }
    }

    if (!$error) {
        if (!isset($all_currency[$post['currency']])) {
            $error = true;
            $messages['error'][] = 'Invalid currency!';
        }
    }

    if (!$error) {
        // Execute insert / update
        if (!empty($id)) {
            // Update
            $type = 'UPDATE';
            $wallet_cond = ' WHERE id = ' . $id;
        }
        else {
            // Insert
            $type = 'INSERT INTO';
            $post['added_at'] = $now;
            $wallet_cond = '';
        }

        $sql_params = array();
        foreach ($post as $column => $value) {
            $sql_params[] = "{$column} = '{$value}'";
        }

        $sql_string = "{$type} wallets SET " . implode(',', $sql_params) . (!empty($wallet_cond) ? $wallet_cond : '');

        if (mysqli_query($connection, $sql_string)) {
            $messages['success'][] = 'Successfull ' . (empty($id) ? 'insert!' : 'update!');
            resetScroll();
            $scroll_to['money'] = true;
            $show_form['wallet_manager'] = false;
        }
        else {
            $messages['error'][] = 'Something went wrong! Please try it later!';
            $show_form['wallet_manager'] = true;
            $edit_wallet = $_POST;
        }
    }
}

if (isset($_GET['category']) && $_GET['category'] == 'show') {
    resetScroll();
    $scroll_to['category_show'] = true;
    $show_form['category_show'] = true;
}
else if (isset($_GET['category']) && !empty($_GET['category'])) {
    resetScroll();
    $scroll_to['category_manager'] = true;
    $show_form['category_show'] = true;
    $show_form['category_manager'] = true;

    $cat_id = ($_GET['category'] != 'new') ? (int) mysqli_real_escape_string($connection, $_GET['category']) : 0;

    if (!empty($cat_id)) {
        $sql_string = "SELECT title, type, description FROM transfer_category WHERE deleted = 0 AND id = {$cat_id}";
        $query = mysqli_query($connection, $sql_string);
        $current_category = mysqli_fetch_assoc($query);
    }
}

if (isset($_POST['add_category'])) {
    $error = false;
    $id = (isset($_GET['category']) && $_GET['category'] != 'new') ? (int) mysqli_real_escape_string($connection, $_GET['category']) : 0;

    $required = array('title', 'type');

    foreach ($required as $input_name) {
        if (!isset($_POST[$input_name]) || empty($_POST[$input_name])) {
            $error = true;
            $messages['error'][] = 'The inputs with * are required!';
        }
    }

    $post = array();
    if (!$error) {
        foreach ($_POST as $key => $value) {
            if ($key != 'add_category') {
                $post[$key] = mysqli_real_escape_string($connection, $value);
            }
        }
    }

    if (!$error) {
        if (!isset($category_types[$post['type']])) {
            $error = true;
            $messages['error'][] = 'Invalid category type!';
        }
    }

    if (!$error) {
        // Execute insert / update
        if (!empty($id)) {
            // Update
            $type = 'UPDATE';
            $category_cond = ' WHERE id = ' . $id;
        }
        else {
            // Insert
            $type = 'INSERT INTO';
            $post['added_at'] = $now;
            $post['user_id'] = $_SESSION['user_id'];
            $category_cond = '';
        }

        $sql_params = array();
        foreach ($post as $column => $value) {
            $sql_params[] = "{$column} = '{$value}'";
        }

        $sql_string = "{$type} transfer_category SET " . implode(',', $sql_params) . (!empty($category_cond) ? $category_cond : '');

        if (mysqli_query($connection, $sql_string)) {
            $messages['success'][] = 'Successfull ' . (empty($id) ? 'insert!' : 'update!');
            resetScroll();
            $scroll_to['category_show'] = true;
            $show_form['category_manager'] = false;
        }
        else {
            $messages['error'][] = 'Something went wrong! Please try it later!';
            $show_form['category_manager'] = true;
            $current_category = $_POST;
        }
    }
}

if (isset($_GET['del'])) {
    $del_id = !empty($_GET['del']) ? (int) mysqli_real_escape_string($connection, $_GET['del']) : 0;

    if (!empty($del_id)) {
        $sql_string = "UPDATE money_transfer SET deleted = 1, deleted_at = '{$now}' WHERE deleted = 0 AND id = {$del_id}";
        mysqli_query($connection, $sql_string);
        if (mysqli_affected_rows($connection) > 0) {
            $messages['success'][] = 'Successfull delete!';
        }
        else {
            $messages['error'][] = 'This transfer does not exist!';
        }
    }
    else {
        $messages['error'][] = 'This transfer does not exist!';
    }

    resetScroll();
    $scroll_to['money'] = true;
}

if (isset($_GET['del_cat'])) {
    $del_id = !empty($_GET['del_cat']) ? (int) mysqli_real_escape_string($connection, $_GET['del_cat']) : 0;

    if (!empty($del_id)) {
        $sql_string = "UPDATE transfer_category SET deleted = 1, deleted_at = '{$now}' WHERE deleted = 0 AND id = {$del_id}";
        mysqli_query($connection, $sql_string);
        if (mysqli_affected_rows($connection) > 0) {
            $messages['success'][] = 'Successfull delete!';
        }
        else {
            $messages['error'][] = 'This category does not exist!';
        }
    }
    else {
        $messages['error'][] = 'This category does not exist!';
    }

    resetScroll();
    $scroll_to['category_show'] = true;
}

if (isset($_GET['del_wallet'])) {
    $del_id = !empty($_GET['del_wallet']) ? (int) mysqli_real_escape_string($connection, $_GET['del_wallet']) : 0;

    if (!empty($del_id)) {
        $sql_string = "UPDATE wallets SET deleted = 1, deleted_at = '{$now}' WHERE deleted = 0 AND id = {$del_id}";
        mysqli_query($connection, $sql_string);
        if (mysqli_affected_rows($connection) > 0) {
            $messages['success'][] = 'Successfull delete!';
        }
        else {
            $messages['error'][] = 'This wallet does not exist!';
        }
    }
    else {
        $messages['error'][] = 'This wallet does not exist!';
    }

    resetScroll();
    $scroll_to['money'] = true;
}

if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    // Logged in

    // Getting all wallet
    $sql_string = "SELECT id, title, currency FROM wallets WHERE deleted = 0 ORDER BY title";
    $query = mysqli_query($connection, $sql_string);
    $wallets = array();
    while ($row = mysqli_fetch_assoc($query)) {
        $wallets[] = $row;
    }

    if (!empty($wallets)) {
        $current_wallet = current($wallets);
    }

    $wallet_filter = isset($current_wallet) && !empty($current_wallet) ? " AND wallets.id = {$current_wallet['id']}" : "";

    // Getting all category
    $sql_string = "SELECT id, title, type, added_at FROM transfer_category WHERE deleted = 0 AND user_id = {$_SESSION['user_id']} ORDER BY title";
    $query = mysqli_query($connection, $sql_string);
    $categories = array();
    while ($row = mysqli_fetch_assoc($query)) {
        $categories[] = $row;
    }

    // Getting all money transfer data
    if (!empty($wallet_filter)) {
        $sql_string = "
        SELECT money_transfer.id, transfer_category.title as category_title, money_transfer.title, CONCAT(money_transfer.value,' ', wallets.currency) as price, money_transfer.added_at
        FROM money_transfer
        LEFT JOIN transfer_category
        ON money_transfer.category_id=transfer_category.id
        LEFT JOIN wallets
        ON wallets.id = money_transfer.wallet_id
        WHERE money_transfer.deleted=0 {$wallet_filter}
        ORDER BY money_transfer.added_at DESC";

        $query = mysqli_query($connection, $sql_string);

        $all_transfer = array();
        while ($row = mysqli_fetch_assoc($query)) {
            $all_transfer[] = $row;
        }
    }

    // Statistics
    $sql_string = "SELECT money_transfer.wallet_id, CONCAT(wallets.title, ' (', wallets.currency,')') as title, SUM(money_transfer.value) as price, transfer_category.type
    FROM money_transfer
    LEFT JOIN transfer_category
    ON transfer_category.id = money_transfer.category_id
    LEFT JOIN wallets
    ON wallets.id = money_transfer.wallet_id
    WHERE transfer_category.deleted = 0 AND money_transfer.deleted = 0 AND wallets.deleted = 0
    GROUP BY money_transfer.wallet_id, transfer_category.type";
    $query = mysqli_query($connection, $sql_string);

    $calculated_stat = array();
    $all_statistics = array();
    $stat_labels = array();
    while ($row = mysqli_fetch_assoc($query)) {
        $all_statistics[] = $row;
    }

    foreach ($all_statistics as $key => $statistics) {

        $new_price = ($statistics['type'] == 2) ? $statistics['price'] * (-1) : $statistics['price'];

        if (!isset($calculated_stat[$statistics['wallet_id']])) {
            $calculated_stat[$statistics['wallet_id']] = $statistics;
        }
        else {
            $calculated_stat[$statistics['wallet_id']]['price'] += $new_price;
        }
    }

    if (!empty($calculated_stat)) {
        foreach ($calculated_stat as $key => $value) {
            $stat_labels[] = '"' . $value['title'] . '"';
        }
    }
}

$messages['success'] = array_unique($messages['success']);
$messages['error'] = array_unique($messages['error']);
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="The best way to check out your cash flow!">
    <meta name="author" content="Daniel Toth">
    <meta name="keywords" content="money,wallet,money transfer,money control">

    <title>MyWallet</title>
    <base href="http://localhost/vts/mywallet/">

    <!-- Bootstrap core CSS -->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom fonts for this template -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="vendor/simple-line-icons/css/simple-line-icons.css">
    <link href="https://fonts.googleapis.com/css?family=Lato" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Catamaran:100,200,300,400,500,600,700,800,900" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Muli" rel="stylesheet">

    <!-- Plugin CSS -->
    <link rel="stylesheet" href="device-mockups/device-mockups.min.css">

    <!-- Custom styles for this template -->
    <link href="css/new-age.min.css" rel="stylesheet">

</head>

<body id="page-top">

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top" id="mainNav">
      <div class="container">
        <a class="navbar-brand js-scroll-trigger" href="home/">MyWallet</a>
        <button class="navbar-toggler navbar-toggler-right" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
          Menu
          <i class="fas fa-bars"></i>
      </button>
      <div class="collapse navbar-collapse" id="navbarResponsive">
          <ul class="navbar-nav ml-auto">
            <?php
            if (!isset($_SESSION['user_id'])) {
                ?>
                <li class="nav-item">
                  <a class="nav-link js-scroll-trigger" href="home/#login">Login</a>
              </li>
              <li class="nav-item">
                  <a class="nav-link js-scroll-trigger" href="home/#registration">Registration</a>
              </li>
              <?php
          }
          else {
            ?>
            <li class="nav-item">
              <a class="nav-link js-scroll-trigger" href="home/#money">Money</a>
          </li>
          <li class="nav-item">
              <a class="nav-link js-scroll-trigger" href="home/#statistics">Statistics</a>
          </li>
          <li class="nav-item">
              <a class="nav-link js-scroll-trigger" href="logout/">Logout</a>
          </li>
          <?php if (isset($_SESSION['first_name'])) { ?>
            <li class="nav-item">
              <a class="nav-link js-scroll-trigger" href="#">Hi, <?php echo $_SESSION['first_name']; ?>!</a>
            </li>
          <?php
        }
      }
      ?>
  </ul>
</div>
</div>
</nav>

<header class="masthead">
  <div class="container h-100">
    <div class="row h-100">
      <div class="col-lg-7 my-auto">
        <div class="header-content mx-auto">
          <h1 class="mb-5">The best way to check out your cash flow!</h1>
          <a href="home/#<?php echo !isset($_SESSION['user_id']) ? 'login' : 'money'; ?>" class="btn btn-outline btn-xl js-scroll-trigger">Start Now for Free!</a>
      </div>
  </div>
  <div class="col-lg-5 my-auto">
    <img src="img/money_box.png" class="img-fluid" alt="">
</div>
</div>
</div>
</header>

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

<?php if (!isset($_SESSION['user_id'])) { ?>
    <section class="bg-primary text-center" id="login">
      <div class="container">
        <div class="section-heading text-center">
          <h2>Login</h2>
          <hr>
      </div>
      <div class="row">
          <div class="col-md-4 m-auto">
            <form action="" method="post">
              <label>E-mail address</label>
              <input type="text" name="email" class="form-control">
              <label>Password</label>
              <input type="password" name="password" class="form-control">
              <br>
              <input type="submit" name="login" value="Login" class="btn btn-primary">
          </form>
      </div>
  </div>
</div>
</section>

<section class="text-center" id="registration">
  <div class="container">
    <div class="section-heading text-center">
      <h2>Registration</h2>
      <p class="text-muted">The input with * are required!</p>
      <hr>
  </div>
  <div class="row">
      <div class="col-md-4 m-auto">
        <form action="" method="post">
          <label>First Name *</label>
          <input type="text" name="first_name" class="form-control" value="<?php echo isset($_POST['first_name']) ? $_POST['first_name'] : ''; ?>">
          <label>Last Name *</label>
          <input type="text" name="last_name" class="form-control" value="<?php echo isset($_POST['last_name']) ? $_POST['last_name'] : ''; ?>">
          <label>E-mail address *</label>
          <input type="text" name="email" class="form-control" value="<?php echo isset($_POST['email']) ? $_POST['email'] : ''; ?>">
          <label>Password *</label>
          <input type="password" name="password" class="form-control" id="passwd">
          <label>Confirm Password *</label>
          <input type="password" name="confirm_password" class="form-control">
          <label>Gender</label><br>
          <label><input type="radio" name="gender" value="1" class="form-group" <?php echo isset($_POST['gender']) && $_POST['gender'] == '1' ? 'checked="checked"' : ''; ?>> Male</label><br>
          <label><input type="radio" name="gender" value="2" class="form-group" <?php echo isset($_POST['gender']) && $_POST['gender'] == '2' ? 'checked="checked"' : ''; ?>> Female</label><br>
          <label>Phone</label>
          <input type="text" name="phone" class="form-control" value="<?php echo isset($_POST['phone']) ? $_POST['phone'] : ''; ?>">
          <label>Mobile</label>
          <input type="text" name="mobile" class="form-control" value="<?php echo isset($_POST['mobile']) ? $_POST['mobile'] : ''; ?>">
          <br>
          <input type="submit" name="registration" value="Registration" class="btn btn-primary">
      </form>
  </div>
</div>
</div>
</section>
<?php
}
else {
?>
<section class="bg-primary" id="money">
    <div class="container">
        <div class="section-heading text-center">
            <h2>Wallets & Money data</h2>
            <hr>
        </div>
        <div class="col-md-4 m-auto">
            <select id="select_wallet" name="wallet_id" class="form-control">
            <?php
            if (isset($wallets) && !empty($wallets)) {
                foreach ($wallets as $wallet) {
                    echo '<option value="' . $wallet['id'] . '">' . $wallet['title'] . ' (' . $wallet['currency'] . ')' . '</option>';
                }
            }
            else {
                echo '<option value="">No wallet</option>';
            }
            ?>
            </select>
        </div>
        <hr>
        <br>
        <div id="transfer_data_header" class="col-md-12 m-auto">
           <div class="row">
            <div class="col-md-2 font-weight-bold border-bottom">
                Category
            </div>
            <div class="col-md-4 font-weight-bold border-bottom">
                Title
            </div>
            <div class="col-md-2 font-weight-bold border-bottom">
                Price
            </div>
            <div class="col-md-2 font-weight-bold border-bottom">
                Date
            </div>
            <div class="col-md-2 font-weight-bold border-bottom text-right">
                Options
            </div>
        </div>
        </div>
        <div id="transfer_data" class="col-md-12">
        <?php
            if (!empty($all_transfer)) {
                foreach ($all_transfer as $transfer) {
                    echo '<div class="row">';
                    foreach ($transfer as $column => $value) {
                        if ($column == 'title') {
                            echo '<div class="col-md-4 border-bottom mt-2">';
                            echo $value;
                            echo '</div>';
                        }
                        else if ($column != 'id') {
                            echo '<div class="col-md-2 border-bottom mt-2">';
                            echo $value;
                            echo '</div>';
                        }
                    }
                    echo '<div class="col-md-2 border-bottom mt-2 text-right">';
                    echo '<a href="transfer/' . $transfer['id'] . '/" title="Edit"><img src="img/edit.png" alt="edit_ico" /></a>&nbsp;';

                    echo '<a class="confirm_delete" href="delete/' . $transfer['id'] . '/" title="Delete"><img src="img/delete.png" alt="delete_ico" /></a>';
                    echo '</div>';
                    echo '</div>';
                }
            }
            else {
            ?>
            <div class="col-md-12 text-center h4 mt-2">
                No data in database
            </div>
            <?php
            }
        ?>
        </div>
        <hr>
        <br>
        <div class="col-md-12">
            <form action="index.php" method="get">
                <a href="transfer/" class="btn btn-secondary">New transfer</a>

                <a href="addwallet/" class="btn btn-secondary" type="submit">New wallet</a>
                <a href="editwallet/<?php echo (isset($current_wallet['id']) && !empty($current_wallet['id']) ? $current_wallet['id'] : 0); ?>/" id="edit_wallet" class="btn btn-secondary" type="submit">Edit wallet</a>

                <a href="categories/" class="btn btn-secondary" type="submit">Categories</a>
            </form>
        </div>
    </div>
</section>
<?php if ($show_form['transfer_manager']) { ?>
<section id="transfer_manager">
    <div class="container">
        <div class="section-heading text-center">
            <h2><?php echo isset($_GET['id']) ? 'Edit' : 'Insert'; ?> transfer</h2>
            <hr>
            <br>
        </div>
        <div class="col-md-4 m-auto text-center">
            <form action="" method="post">
                <label>Wallet *</label>
                <select name="wallet_id" class="form-control">
                <option value="">Choose</option>
                <?php
                if (isset($wallets) && !empty($wallets)) {
                    foreach ($wallets as $wallet) {
                        echo '<option value="' . $wallet['id'] . '" ' . (isset($current_transfer['wallet_id']) && $current_transfer['wallet_id'] == $wallet['id'] ? 'selected="selected"' : '') . '>' . $wallet['title'] . ' (' . $wallet['currency'] . ')' . '</option>';
                    }
                }
                ?>
                </select>
                <label>Category *</label>
                <select name="category_id" class="form-control">
                <option value="">Choose</option>
                <?php
                if (isset($categories) && !empty($categories)) {
                    foreach ($categories as $category) {
                        echo '<option value="' . $category['id'] . '" ' . (isset($current_transfer['category_id']) && $current_transfer['category_id'] == $category['id'] ? 'selected="selected"' : '') . '>' . $category['title'] . '</option>';
                    }
                }
                ?>
                </select>
                <label>Title *</label>
                <input type="text" name="title" class="form-control" value="<?php echo isset($current_transfer['title']) ? $current_transfer['title'] : '' ?>">
                <label>Description</label>
                <textarea name="description" class="form-control"><?php echo isset($current_transfer['description']) ? $current_transfer['description'] : '' ?></textarea>
                <label>Price *</label>
                <input type="number" name="value" class="form-control" value="<?php echo isset($current_transfer['value']) ? $current_transfer['value'] : '' ?>">
                <br>
                <input type="submit" name="add_transfer" value="Save" class="btn btn-primary">
            </form>
        </div>
    </div>
</section>
<?php
}
?>

<?php if ($show_form['wallet_manager']) { ?>
<section id="wallet_manager">
    <div class="container text-center">
        <div class="section-heading">
            <h2><?php echo (isset($_GET['wallet']) && $_GET['wallet'] == 'new') ? 'Insert' : 'Edit'; ?> wallet</h2>
            <hr>
            <br>
        </div>
        <div class="col-md-4 m-auto">
            <form action="" method="post">
                <label>Title *</label>
                <input type="text" name="title" class="form-control" value="<?php echo isset($edit_wallet['title']) ? $edit_wallet['title'] : ''; ?>">
                <label>Currency *</label>
                <select name="currency" class="form-control">
                    <option value="">Choose</option>
                    <?php
                    foreach ($all_currency as $short => $name) {
                          echo '<option value="' . $short . '" ' . (isset($edit_wallet['currency']) && $edit_wallet['currency'] == $short ? 'selected="selected"' : '') . '>' . $name . '</option>';
                    }
                    ?>
                </select>
                <hr>
                <br>
                <?php
                if (isset($_GET['wallet']) && $_GET['wallet'] != 'new') {
                    echo '<a href="deletewallet/' . $_GET['wallet'] . '/" class="confirm_delete btn btn-danger">Delete</a><br><br>';
                }
                ?>
                <input type="submit" name="add_wallet" class="btn btn-primary" value="Save">
            </form>
        </div>
    </div>
</section>
<?php
}
?>

<?php if ($show_form['category_show']) { ?>
<section id="category_show">
    <div class="container text-center">
        <div class="section-heading">
            <h2>Categories</h2>
            <hr>
            <br>
        </div>
        <div class="row text-left">
            <div class="col-md-3 font-weight-bold border-bottom">
                Title
            </div>
            <div class="col-md-3 font-weight-bold border-bottom">
                Type
            </div>
            <div class="col-md-3 font-weight-bold border-bottom">
                Date
            </div>
            <div class="col-md-3 font-weight-bold border-bottom text-right">
                Options
            </div>
        </div>
        <?php
            if (!empty($categories)) {
                foreach ($categories as $category) {
                    echo '<div class="row text-left">';
                    foreach ($category as $column => $value) {
                        if ($column == 'type') {
                            echo '<div class="col-md-3 border-bottom mt-2 ' . $category_types[$value]['class'] . '">';
                            echo $category_types[$value]['title'];
                            echo '</div>';
                        }
                        else if ($column != 'id') {
                            echo '<div class="col-md-3 border-bottom mt-2">';
                            echo $value;
                            echo '</div>';
                        }
                    }
                    echo '<div class="col-md-3 border-bottom mt-2 text-right">';
                    echo '<a href="editcategory/' . $category['id'] . '/" title="Edit"><img src="img/edit.png" alt="edit_ico" /></a>&nbsp;';

                    echo '<a class="confirm_delete" href="categories/delete/' . $category['id'] . '/" title="Delete"><img src="img/delete.png" alt="delete_ico" /></a>';
                    echo '</div>';
                    echo '</div>';
                }
            }
            else {
            ?>
            <div class="col-md-12 text-center h4 mt-2">
                No data in database
            </div>
            <?php
            }
        ?>
        <hr>
        <br>
        <div class="col-md-12 text-left">
            <a href="addcategory/" class="btn btn-secondary">New category</a>
        </div>
    </div>
</section>
<?php
}
?>


<?php if ($show_form['category_manager']) { ?>
<section class="bg-primary" id="category_manager">
    <div class="container text-center">
        <div class="section-heading">
            <h2><?php echo $_GET['category'] == 'new' ? 'Insert' : 'Edit'; ?> category!</h2>
            <hr>
            <br>
        </div>
        <div class="col-md-4 m-auto text-center">
            <form action="" method="post">
                <label>Title *</label>
                <input type="text" name="title" class="form-control" value="<?php echo isset($current_category['title']) ? $current_category['title'] : ''; ?>">
                <label>Type *</label><br>
                <?php
                foreach ($category_types as $key => $c_type) {
                    echo '<label><input class="form-group" type="radio" name="type" value="' . $key . '" ' . (isset($current_category['type']) && $current_category['type'] == $key ? 'checked="checked"' : '') . '> ' . $c_type['title'] . '</label><br>';
                }
                ?>
                <label>Description</label>
                <textarea name="description" class="form-control"><?php echo isset($current_category['description']) ? $current_category['description'] : ''; ?></textarea>
                <hr>
                <br>
                <input type="submit" name="add_category" value="Save" class="btn btn-primary">
            </form>
        </div>
    </div>
</section>
<?php
}
?>

<section id="statistics">
    <div class="row">
        <div class="col-md-2"></div>
        <div class="col-md-8">
            <canvas id="wallet_chart"></canvas>
        </div>
        <div class="col-md-2"></div>
    </div>
</section>

<?php
}
?>

<footer>
  <div class="container">
    <p>&copy; MyWallet 2018. All Rights Reserved.</p>
    <ul class="list-inline">
      <li class="list-inline-item">
        <a href="#">Privacy</a>
    </li>
    <li class="list-inline-item">
        <a href="#">Terms</a>
    </li>
    <li class="list-inline-item">
        <a href="#">FAQ</a>
    </li>
</ul>
</div>
</footer>

<!-- Bootstrap core JavaScript -->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- Plugin JavaScript -->
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>

<!-- Custom scripts for this template -->
<script src="js/new-age.min.js"></script>

<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.3/Chart.min.js"></script>

<?php if (!empty($messages['error']) || !empty($messages['success'])) { ?>
<script type="text/javascript">
    $(document).ready(function() {
      $('#alerts').modal('show');
    });
</script>
<?php } ?>

<script type="text/javascript">
    $('body').on('change', '#select_wallet', function() {
        var id = $(this).val();
        $.ajax({
            url: "ajax/load_transfer.php",
            data: {id: id},
            method: "POST",
            success: function(data) {
                $("#transfer_data").html(data);
                $("#edit_wallet").attr("value", id);
            },
            error: function() {

            },
            dataType: "HTML"
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

<?php
foreach ($scroll_to as $key => $value) {
    if ($value) {
        ?>
        <script type="text/javascript">
            var element = $("#<?php echo $key; ?>");
            $('html, body').animate({
              scrollTop: element.offset().top
            }, 1000);
        </script>
        <?php
    }
}
?>
<script>
    var ctx = document.getElementById("wallet_chart").getContext('2d');
    var myChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: [<?php echo implode(',', $stat_labels); ?>],
            datasets: [{
              label: 'Money in wallet',
              data: [<?php echo implode(',', array_column($calculated_stat, 'price')); ?>],
              backgroundColor: [
                <?php
                foreach ($calculated_stat as $stat) {
                    echo "'rgba(" . rand(0, 255) . ", " . rand(0, 255) . ", " . rand(0, 255) . ")',";
                }
                ?>
              ],
              borderColor: [
              <?php
                foreach ($calculated_stat as $stat) {
                    echo "'rgba(" . rand(0, 255) . ", " . rand(0, 255) . ", " . rand(0, 255) . ")',";
                }
                ?>
              ],
              borderWidth: 1
            }]
        },
        options: {
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero:true
                    }
                }]
            }
        }
    });
</script>
</body>
</html>