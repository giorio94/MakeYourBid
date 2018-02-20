<?php

/*
 * Functions and definitions used by
 * the other scripts
 */

/* Constants definition */
define('db_location', 'localhost');
define('db_name', 'MakeYourBid');
define('db_username', 'root');
define('db_password', '');
define('db_charset', 'utf8');

define('session_prefix', 'MYB__');
define('session_timeout', 60*2);

define('product_id', '1');

define('query_product_description', "SELECT * FROM product_description WHERE pr_id=".product_id.";");
define('query_product_auction', "SELECT * FROM product_auction WHERE pra_id=".product_id.";");

/*
 * Check if the https protocol is not used and
 * in that case redirect the request to use it
 */
function force_https() {
    if(!array_key_exists('HTTPS', $_SERVER) || $_SERVER["HTTPS"] !== "on") {
        header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
        die();
    }
}

/* Functions used to send output as JSON */
function echo_json($array = array()) {
    $array['err_code'] = 'OK';
    header('Content-type: application/json');
    echo json_encode($array);
}

/* Send an error code as JSON */
function echo_error_die($code, $array = array()) {
    $array['err_code'] = $code;
    header('Content-type: application/json');
    echo json_encode($array);
    die();
}

/* Return an array containing user info */
function get_user_info() {
    return [
        'username' => $_SESSION[session_prefix.'username'],
        'thr' => $_SESSION[session_prefix.'thr_'.product_id],
    ];
}

/* Echo user info as JSON */
function echo_user_info() {
    echo_json(get_user_info());
}

/* Get the username related to this session */
function session_get_username(&$error) {
    /* Check if session already exists */
    if(isset($_SESSION[session_prefix.'username']) &&
        isset($_SESSION[session_prefix.'last_access'])) {

        /* Compute inactivity time */
        $last = $_SESSION[session_prefix.'last_access'];
        $now = time();
        if($now - $last <= session_timeout) {
            $_SESSION[session_prefix.'last_access'] = time();
            return $_SESSION[session_prefix.'username'];
        } else {
            $error = 'session-timed-out';
            return false;
        }
    }
    else {
        $error = 'not-logged-in';
        return false;
    }
}

/* Destroy the current session */
function session_destr() {
    /* Clear the SESSION variable */
    $_SESSION = array();

    /* Delete the cookie */
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600*24,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }

    /* Destroy the session */
    session_destroy();
}

/****************************************/

/* Call force_https in order to make it mandatory in each page */
force_https();

?>
