<?php
require_once '../config.php';

// Ajax-e??
// Ebben a file-ban változtassuk a wallet értékét, ha a select megváltozik
if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {

    $wallet_id = isset($_POST['id']) ? (int) mysqli_real_escape_string($connection, $_POST['id']) : 0;

    // Getting all money transfer data
    $sql_string = "
    SELECT money_transfer.id, transfer_category.title as category_title, money_transfer.title, CONCAT(money_transfer.value,' ', wallets.currency) as price, money_transfer.added_at
    FROM money_transfer
    LEFT JOIN transfer_category
    ON money_transfer.category_id=transfer_category.id
    LEFT JOIN wallets
    ON wallets.id = money_transfer.wallet_id
    WHERE money_transfer.deleted=0 AND wallets.id = {$wallet_id}
    ORDER BY money_transfer.added_at DESC";

    $query = mysqli_query($connection, $sql_string);

    $all_transfer = array();
    while ($row = mysqli_fetch_assoc($query)) {
        $all_transfer[] = $row;
    }

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
        echo '<div class="col-md-12 text-center h4 mt-2">
        No data in database
        </div>';
    }
}
else {
    header('Location: ../');
    exit();
}

?>