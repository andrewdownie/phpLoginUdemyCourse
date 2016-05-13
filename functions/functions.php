<?php
//======
//====== HELPER FUNCTIONS ------------------------------------------------------
//======
function clean($string){
    return htmlentities($string);
}
function redirect($location){
    return header("Location: {$location}");
}
function set_message($message){
    if(!empty($message)){
        $_SESSION['message'] = $message;
    }
    else{
        $message = "";
    }
}
function display_message(){
    if(isset($_SESSION['message'])){
        echo $_SESSION['message'];
        unset($_SESSION['message']);
    }
}
function token_generator(){
    $token = $_SESSION['token'] = md5(uniqid(mt_rand(), true));
    return $token;
}
function validation_errors($error_message){
    $alert_error_message = "
        <div class='alert alert-danger alert-dismissible' role='alert'>
            <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
            <strong>Warning!</strong>
            {$error_message}
        </div>
    ";
    return $alert_error_message;
}

function email_exists($email){
    $sql = "SELECT id FROM users WHERE email = '$email'";
    $result = query($sql);

    if(row_count($result) >= 1){
        return true;
    }
    return false;
}
function username_exists($username){
    $sql = "SELECT id FROM users WHERE username = '$username'";
    $result = query($sql);

    if(row_count($result) >= 1){
        return true;
    }
    return false;
}

//======
//====== VALIDATION FUNCTIONS --------------------------------------------------
//======


function validate_user_registration(){
    $errors = [];

    $min = 3;
    $max = 50;


    if($_SERVER['REQUEST_METHOD'] == "POST"){

        $first_name        = clean($_POST['first_name']);
        $last_name         = clean($_POST['last_name']);
        $username          = clean($_POST['username']);
        $email             = clean($_POST['email']);
        $password          = clean($_POST['password']);
        $confirm_password  = clean($_POST['confirm_password']);


        if(email_exists($email)){
            $errors[] = "Sorry that email is already taken.";
        }
        if(username_exists($username)){
            $errors[] = "Sorry that username is already taken.";
        }

        $errors = validate_length($errors, $first_name, "first name", $min, $max);
        $errors = validate_length($errors, $last_name, "last name", $min, $max);
        $errors = validate_length($errors, $email, "email", $min, $max);

        if($password !== $confirm_password){
            $errors[] = "Your passwords do not match.";
        }

        if(!empty($errors)){
            foreach($errors as $error){
                echo validation_errors($error);
            }
        }
        else{
            if(register_user($first_name, $last_name, $username, $email, $password)){
                echo "user registered";
            }
        }
    }
}

function validate_length($errors, $string, $label, $min, $max){

    if(strlen($string) < $min){
        $errors[] = "Your {$label} cannot be less than {$min} characters";
    }
    else if(strlen($string) > $max){
        $errors[] = "Your {$label} cannot be greater than {$max} characters";
    }

    return $errors;
}

function register_user($first_name, $last_name, $username, $email, $password){
    $first_name = escape($first_name);
    $last_name  = escape($last_name);
    $username   = escape($username);
    $email      = escape($email);
    $password   = escape($password);

    if(email_exists($email) || username_exists($username)){
        return false;
    }
    
    $password = md5($password);
    $validation = md5($username + microtime());

    $sql = "INSERT INTO users (first_name, last_name, username, email, password, validation_code, active) ";
    $sql.= "values ('{$first_name}', '{$last_name}', '{$username}', '{$email}', '{$password}', '{$validation}', 0)";
    $result = query($sql);
    confirm($result);

    return true;

}

?>
