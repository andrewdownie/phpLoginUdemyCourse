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

//======
//====== VALIDATION FUNCTIONS --------------------------------------------------
//======


function validate_user_registration(){
    $errors = [];

    $min = 3;
    $max = 20;


    if($_SERVER['REQUEST_METHOD'] == "POST"){

        $first_name        = clean($_POST['first_name']);
        $last_name         = clean($_POST['last_name']);
        $username          = clean($_POST['username']);
        $email             = clean($_POST['email']);
        $password          = clean($_POST['password']);
        $confirm_password  = clean($_POST['confirm_password']);

        $errors[] = validate_length($first_name, "first name", $min, $max);
        $errors[] = validate_length($last_name, "last name", $min, $max);


        if(!empty($errors)){
            foreach($errors as $error){
                echo $error . "<br>";
            }
        }
    }
}

function validate_length($string, $label, $min, $max){
    $msg = "";

    if(strlen($string) < $min){
        $msg = "Your {$label} cannot be less than {$min} characters";
    }
    else if(strlen($string) > $max){
        $msg = "Your {$label} cannot be greater than {$max} characters";
    }

    return $msg;
}

?>
