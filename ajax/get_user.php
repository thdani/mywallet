<?php
require_once '../config.php';

// Ellenőrizzük, hogy ajax-e a kérvény!?
if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {

    $response = array(
        'status' => 'success',
        'data' => ''
    );

    // Validálás
    $post = array();
    foreach ($_POST as $key => $value) {
        $post[$key] = mysqli_real_escape_string($connection, $value);
    }

    // Edit vagy Insert?
    if ($post['type'] == 'edit') {
        $edit = (int) $post['id'];
        $sql_string = "SELECT first_name, last_name, email, gender, phone, mobile FROM users WHERE deleted = 0 AND id = {$edit}";

        $query = mysqli_query($connection, $sql_string);
        $query = mysqli_fetch_assoc($query);

        $data = '';
        if (!empty($query)) {
            $data .= '<form action="" method="post">';
            $data .= '<label>First Name *</label><input type="text" class="form-control" name="first_name" value="'.$query['first_name'].'" />';
            $data .= '<label>Last Name *</label><input type="text" class="form-control" name="last_name" value="'.$query['last_name'].'" />';
            $data .= '<label>E-mail address *</label><input type="email" class="form-control" name="email" value="'.$query['email'].'" />';
            $data .= '<label>Gender</label><br />';
            $data .= '<label><input type="radio" class="form-group" name="gender" value="1" '.(isset($query['gender']) && $query['gender'] == '1' ? 'checked' : '').' /> Male</label><br />';
            $data .= '<label><input type="radio" class="form-group" name="gender" value="2" '.(isset($query['gender']) && $query['gender'] == '2' ? 'checked' : '').' /> Female</label><br />';
            $data .= '<label>Phone</label><input type="text" class="form-control" name="phone" value="'. (isset($query['phone']) ? $query['phone'] : '') .'" />';
            $data .= '<label>Mobile</label><input type="text" class="form-control" name="mobile" value="'. (isset($query['mobile']) ? $query['mobile'] : '') .'" /><br />';
            $data .= '<input type="submit" class="btn btn-primary" name="save" value="Save" />&nbsp;';
            $data .= '<input type="button" class="btn btn-danger close-edit" value="Cancel" />';
            $data .= '<input type="hidden" name="id" value="' . $edit . '" />';
            $data .= '</form>';

            $response['status'] = 'success';
            $response['data'] = $data;
        }
        else {
            $response['status'] = 'error';
        }

        // Visszaadjuk a formot, amelyel tudjuk szerkeszteni a kiválasztott felhasználót
        echo json_encode($response);

    }

    if ($post['type'] == 'new') {
        $data = '<form action="" method="post">';
        $data .= '<label>First Name *</label><input type="text" class="form-control" name="first_name" value="" />';
        $data .= '<label>Last Name *</label><input type="text" class="form-control" name="last_name" value="" />';
        $data .= '<label>E-mail address *</label><input type="email" class="form-control" name="email" value="" />';
        $data .= '<label>Password *</label><input type="password" class="form-control" name="password" value="" />';
        $data .= '<label>Confirm password *</label><input type="password" class="form-control" name="confirm_password" value="" />';
        $data .= '<label>Gender</label><br />';
        $data .= '<label><input type="radio" class="form-group" name="gender" value="1" /> Male</label><br />';
        $data .= '<label><input type="radio" class="form-group" name="gender" value="2" /> Female</label><br />';
        $data .= '<label>Phone</label><input type="text" class="form-control" name="phone" value="" />';
        $data .= '<label>Mobile</label><input type="text" class="form-control" name="mobile" value="" /><br />';
        $data .= '<input type="submit" class="btn btn-primary" name="save" value="Save" />&nbsp;';
        $data .= '<input type="button" class="btn btn-danger close-edit" value="Cancel" />';
        $data .= '</form>';

        $response['data'] = $data;

        // Üres form felhasználó létrehozásához
        echo json_encode($response);
    }
}
else {
    header('Location: ../');
    exit();
}
?>