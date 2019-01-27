<?php
session_start();
require_once '../config.php';

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


// Statistics
$sql_string = "SELECT COUNT(id) as count, logged_at FROM login_statistics WHERE deleted = 0 GROUP BY logged_at ORDER BY logged_at DESC LIMIT 0, 20";
$query = mysqli_query($connection, $sql_string);
$all_visited = array();
while ($row = mysqli_fetch_assoc($query)) {
    $all_visited[] = $row;
}

$all_count = array();
$all_date = array();

if (!empty($all_visited)) {
    foreach ($all_visited as $visited) {
        $all_count[] = $visited['count'];
        $all_date[] = '"' . $visited['logged_at'] . '"';
    }
    krsort($all_date);
    krsort($all_count);
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

    <!-- nav -->
    <?php require_once 'navbar.html'; ?>

    <div id="wrapper">

      <div id="content-wrapper">

        <div class="container-fluid">
            <h2 class="text-center">MyWallet - Admin</h2>

            <div class="row">
                <div class="col-md-1"></div>
                <div class="col-md-10">
                    <canvas id="myChart"></canvas>
                </div>
                <div class="col-md-1"></div>
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

    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.3/Chart.min.js"></script>


    <script>
            var ctx = document.getElementById("myChart").getContext('2d');
            var myChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [<?php echo implode(',', $all_date); ?>],
                    datasets: [{
                        label: 'Page visitors (Last 20 day)',
                        data: [<?php echo implode(',', $all_count); ?>],
                        backgroundColor: [
                            <?php echo "'rgba(" . rand(0, 255) . ", " . rand(0, 255) . ", " . rand(0, 255) . ", 0.4)'"; ?>
                        ],
                        borderColor: [
                            <?php echo "'rgba(" . rand(0, 255) . ", " . rand(0, 255) . ", " . rand(0, 255) . ", 0.4)'"; ?>
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
