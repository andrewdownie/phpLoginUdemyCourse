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
    confirm($result);

    if(row_count($result) >= 1){
        return true;
    }
    return false;
}
function username_exists($username){
    $sql = "SELECT id FROM users WHERE username = '$username'";
    $result = query($sql);
    confirm($result);

    if(row_count($result) >= 1){
        return true;
    }
    return false;
}

function send_email($email, $subject, $msg, $headers){
    return mail($email, $subject, $msg, $headers);
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
                set_message("<p class='bg-success text-center'> Please check your email or spam folder for an activation link.</p>");
                redirect("index.php");
            }
            else{
                set_message("<p class='bg-success text-center'> Sorry, we could not register you.</p>");
                redirect("index.php");
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




//======
//====== REGISTER USER FUNCTIONS -----------------------------------------------
//======

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


    $subject = "Activate Account - portfolio";
    $msg = "Please click the link below to activate your Account
    http://localhost/login/activate.php?email=$email&code=$validation_code
    ";

    $header = "From: noreply@thiswebsite.com";

    send_email($email, $subject, $msg, $headers);

    return true;

}

//======
//====== ACTIVATE USER FUNCTIONS -----------------------------------------------
//======

function activate_user(){
    if($_SERVER['REQUEST_METHOD'] == "GET"){
        if(isset($_GET['email'])){
            $email = clean($_GET['email']);

            $validation_code = clean($_GET['code']);

            $sql = "SELECT id FROM users WHERE email = '".escape($_GET['email'])."' AND validation_code = '".escape($_GET['code'])."'";
            $result = query($sql);
            confirm($result);

            if(row_count($result) == 1){
                $sql = "UPDATE users SET active = 1, validation_code = 0 WHERE email='".escape($email)."' AND validation_code='".escape($validation_code)."'";
                $result2 = query($sql);
                confirm($result2);


                set_message("<p class'bg-success'>Your account has been activated, please login.</p>");
                redirect("login.php");
            }
            else{
                set_message("<p class'bg-danger'>Sorry, your account could not be activated.</p>");
                redirect("login.php");
            }
        }
    }
}

//======
//====== VALIDATE USER LOGIN FUNCTIONS -----------------------------------------
//======

function validate_user_login(){
    $errors = [];

    $min = 3;
    $max = 50;

    if(!isset($_POST['email']) && !isset($_POST['password'])){
        return;
    }

    $email             = clean($_POST['email']);
    $password          = clean($_POST['password']);
    $remember          = clean($_POST['remember']);

    if(empty($email)){
        $errors[] = "Email field cannot be empty.";
    }

    if(empty($password)){
        $errors[] = "Password field cannot be empty.";
    }


    if(!empty($errors)){
        foreach($errors as $error){
            echo validation_errors($error);
        }
    }
    else{
        if(login_user($email, $password, $remember)){
            redirect("admin.php");
        }
        else{
            echo validation_errors("Your credentials are not correct");
        }
    }
}

//======
//====== USER LOGIN FUNCTIONS --------------------------------------------------
//======

function login_user($email, $password, $remember){
    $sql = "SELECT password, id FROM users WHERE email = '".escape($email)."' AND active = 1";
    $result = query($sql);
    if(row_count($result) == 1){
        $row = fetch_array($result);

        $db_password = $row['password'];

        if(md5($password) == $db_password){

            if($remember == "on"){
                setcookie('email', $email, time() + 1200);
            }

            $_SESSION['email'] = $email;

            return true;
        }
    }

    return false;
}

//======
//====== LOGGED IN FUNCTION ----------------------------------------------------
//======

function logged_in(){
    if(isset($_SESSION['email']) || isset($_COOKIE['email'])){
        return true;
    }
    else{
        return false;
    }
}

//======
//====== RECOVER PASSWORD ------------------------------------------------------
//======

function recover_password(){
    if($_SERVER['REQUEST_METHOD'] == "POST"){

        if(isset($_SESSION['token']) && $_POST['token'] === $_SESSION['token'])
        {
            $email = clean($_POST['email']);

            if(email_exists($email)){
                $validation_code = md5($email + microtime());

                setcookie('temp_access_code', $validation_code, time() + 900);

                $sql = "UPDATE users SET validation_code = '".escape($validation_code)."' WHERE email = '".escape($email)."'";
                $result = query($sql);
                confirm($result);

                $subject = "Please reset your password.";
                $message = "Here is your password reset code: {$validation_code}
                click here to reset your password: http://localhost/code.php?email=$email&code=validation_code";
                $header = "From: noreply@yourwebsite.com";

                if(!send_email($email, $subject, $message, $header)){
                    echo validation_errors("Email count not be send");
                }

                set_message("<p class='bg-success text-center'>Please check your email (including spam folder) for a password reset code.</p>");
                redirect("index.php");

            }
            else{
                echo validation_errors("This email does not exist");
            }
        }

    }
    else{//redirect if the token is invalid
        //redirect("index.php");//this seems to cause problems while testing
    }

    if(isset($_POST['cancel_submit'])){
        redirect("login.php");
    }
}
//======
//====== CODE VALIDATION -------------------------------------------------------
//======
function validate_code(){
    if(isset($_COOKIE['temp_access_code'])){
        if(!isset($_GET['email']) && !isset($_GET['code'])){
            redirect("index.php");
        }
        else if(empty($_GET['email']) || empty($_GET['code'])){
            redirect("index.php");
        }
        else{
            if(isset($_POST['code'])){
                $email = clean($_GET['email']);
                $validation_code = clean($_POST['code']);
                $sql = "SELECT id FROM users WHERE validation_code = '".escape($validation_code)."' AND email = '".escape($email)."'";
                $result = query($sql);

                if(row_count($result) == 1){
                    setcookie('temp_access_code', $validation_code, time() + 300);
                    redirect("reset.php?email=$email&code=$validation_code");
                }
                else{
                    echo validation_errors("Sorry, wrong validation code");
                }
            }
        }

    }
    else{
        set_message("<p class='bg-danger text-center'>Sorry your validation cookie was expired.</p>");
        redirect("recover.php");
    }
}

//======
//====== PASSWORD RESET --------------------------------------------------------
//======

function password_reset(){
    if(isset($_COOKIE['temp_access_code'])){



        if( isset($_GET['email']) && isset($_GET['code']) ){
            if( isset($_SESSION['token']) && isset($_POST['token']) ){
                if($_POST['token'] === $_SESSION['token']){

                    if($_POST['password'] === $_POST['confirm_password']){
                        $updated_password = md5($_POST['password']);


                        $sql = "UPDATE users SET password='".escape($updated_password)."', validation_code = 0 WHERE email ='".escape($_GET['email'])."'";
                        query($sql);
                        set_message("<p class='bg-success text-center'>Your password has been updated, please login.</p>");
                        redirect("login.php");
                    }


                }
            }
        }
    }
    else{
        set_message("<p class='bg-danger text-center'> Sorry your time has expired.  </p>");
        redirect("recover.php");
    }



}
?>
