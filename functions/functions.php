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

?>
