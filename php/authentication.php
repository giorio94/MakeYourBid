<?php

/* 
 * This script handles the functions related
 * to authentication. Request parameters:
 * ?action=
 *   - login: given username and password
 *     checks if they are correct and in that
 *     case authenticates the user.
 *   - register: given username and password
 *     checks if they are correct and in that
 *     case registers the user.
 *   - logout: destroys the current session.
 */

define('max_username_len', 254);
define('max_passw_len', 16);
define('salt_len', 16);
    
include('common.php');

/* Check if username and password follow the rules */
function check_parameters_correctness() {
    if(isset($_REQUEST['username']) && isset($_REQUEST['password']) &&
       strlen($_REQUEST['username']) <= max_username_len &&
       filter_var($_REQUEST['username'], FILTER_VALIDATE_EMAIL)) {
        
        $pass = $_REQUEST['password'];
        if(strlen($pass) <= max_passw_len &&
           preg_match('/[a-zA-Z]/', $pass) &&
           preg_match('/[0-9]/', $pass)) {
            
            global $username, $password;
            $username = $_REQUEST['username'];
            $password = $pass;
            return true;
        }
    }
    echo_error_die('invalid-parameters');
}

/* Try to perform the login */
function do_login() {
    /* Check if already logged in */
    if(!isset($_REQUEST['username']) && !isset($_REQUEST['password'])) {
        if(session_get_username($error)) {
            echo_user_info();  
        }
        else {
            session_destr();
            echo_error_die('login-failed');
        }
        return;
    }
    
    /* For security reasons destroy the previous session 
     * to avoid reusing old cookies */
    session_destr();
    
    /* Check if parameters are correct */
    check_parameters_correctness();
    
    /* Query the database */
    $handle = @mysqli_connect(db_location, db_username, db_password, db_name) or echo_error_die('db-error');
    @mysqli_set_charset($handle, db_charset) or echo_error_die('db-error');
    
    global $username, $password;
    $query_login = "SELECT u_email, bid_thr FROM user, bid WHERE u_email = bid_user"
        ." AND bid_product = ".product_id." AND u_email='"
        .mysqli_real_escape_string($handle, $username)
        ."' AND u_password="
        ."SHA2(CONCAT('".mysqli_real_escape_string($handle, $password)."', u_salt), 256);";
    $result = @mysqli_query($handle, $query_login) or echo_error_die('db-error');
    $record = @mysqli_fetch_array($result) or echo_error_die('login-failed');
    
    session_start();
    /* Regenerate the session if a cookie was received
     * to avoid problems in case of double login */
    if(isset($_COOKIE['PHPSESSID'])) {
        session_regenerate_id();
    }
    
    /* Set the session variables */
    $_SESSION[session_prefix.'username'] = $record['u_email'];
    $_SESSION[session_prefix.'thr_'.product_id] = $record['bid_thr'];
    $_SESSION[session_prefix.'last_access'] = time();
    
    @mysqli_free_result($result);
    @mysqli_close($handle);
    
    /* Send them to the user */
    echo_user_info();
}

/* Try to perform the registration */
function do_register() {
    /* For security reasons destroy the previous session 
     * to avoid reusing old cookies */
    session_destr();
    
    /* Check if parameters are correct */
    check_parameters_correctness();
    
    /* Query the database */
    $handle = @mysqli_connect(db_location, db_username, db_password, db_name) or echo_error_die('db-error');
    @mysqli_set_charset($handle, db_charset) or echo_error_die('db-error');
    
    /* Start a transaction */
    @mysqli_autocommit($handle, false) or echo_error_die('db-error');
    
    global $username, $password;
    /* Generate a random string to better hash the password */
    $salt = bin2hex(random_bytes(salt_len/2));
    $query_register_1 = "INSERT INTO user (u_email, u_password, u_salt) VALUES ('"
        .mysqli_real_escape_string($handle, $username)."',"
        ."SHA2('".mysqli_real_escape_string($handle, $password).$salt."', 256),'"
        .$salt."');";
    
    $query_register_2 = "INSERT INTO bid (bid_product, bid_user, bid_thr) VALUES ('".
        product_id."','".mysqli_real_escape_string($handle, $username)."','0');";
    
    $result = @mysqli_query($handle, $query_register_1);
    if(!$result) {
        /* In case of error rollback the changes */
        mysqli_rollback($handle);
        echo_error_die('user-already-exists');
    }
    
    $result = @mysqli_query($handle, $query_register_2);
    if(!$result) {
        /* In case of error rollback the changes */
        mysqli_rollback($handle);
        echo_error_die('user-already-exists');
    }
    
    /* Commit the changes */
    @mysqli_commit($handle) or echo_error_die('db-error');
    @mysqli_close($handle);
    
    session_start();
    /* Regenerate the session if a cookie was received */
    if(isset($_COOKIE['PHPSESSID'])) {
        session_regenerate_id();
    }
    
    /* Set the session variables */
    $_SESSION[session_prefix.'username'] = $username;
    $_SESSION[session_prefix.'thr_'.product_id] = 0;
    $_SESSION[session_prefix.'last_access'] = time();
    
    /* Send them to the user */
    echo_user_info();
}

/****************************************/

/* Check if the parameter is valid */
if(!isset($_REQUEST['action'])) {
    echo_error_die('invalid-parameters');
}

session_start();
switch($_REQUEST['action']) {
    case 'login':
        do_login();
        die();
        
    case 'register':
        do_register();
        die();
        
    case 'logout':
        session_destr();
        echo_json();
        die();
        
    default:
        session_destr();
        echo_error_die('invalid-parameters');
}

?>