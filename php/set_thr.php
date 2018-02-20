<?php

/* 
 * This script handles the update of the
 * value of THR by an user.
 * Authentication is required.
 * Parameter: 
 * ?username=
 *   specifies the user requesting the update
 * ?value=
 *   specifies the new value of THR 
 */

define('decimal_regex', '/^[0-9]{1,9}(\.[0-9]{1,2})?$/');

define('base_bid', 1);
define('min_increment', 0.01);

include('common.php');

function throw_ex($message) {
    throw new Exception($message);
}

/* Recompute the highest bid according to the rules */
function recompute_bid($handle) {
    /* Select highest bidders */
    $query_highest = "SELECT bid_user, bid_thr FROM bid WHERE bid_product = "
        .product_id. " ORDER BY bid_thr DESC, bid_timestamp".
        " ASC LIMIT 2 FOR UPDATE;";
    
    $result = @mysqli_query($handle, $query_highest) or throw_ex('db-error');
    
    /* Get the highest */
    $highest = @mysqli_fetch_array($result) or throw_ex('db-error');
    $second = @mysqli_fetch_array($result);
    @mysqli_free_result($result);
    
    if($highest['bid_thr'] == 0) {
        throw_ex('db-error');
    }
    /* Only one thr */
    if(!$second || $second['bid_thr'] == 0) {
        $new_bid = base_bid;
        $new_user = $highest['bid_user']; 
    }
    else {
        $new_user = $highest['bid_user']; 
        $new_bid = $second['bid_thr'];
        
        /* Two highest THR are not equal */
        if($highest['bid_thr'] != $second['bid_thr']) {
            $new_bid = $new_bid + min_increment;
        }
    }
    $new_bid = number_format((float)($new_bid), 2, '.', '');
    
    /* Update the highest bid */
    $query_update = "UPDATE product_auction SET pra_bid = ".
        $new_bid.", pra_user = '".
        mysqli_real_escape_string($handle, $new_user).
        "' WHERE pra_id = ".product_id.";";
    $result = @mysqli_query($handle, $query_update) or throw_ex('db-error');
    
    return [
        'bid' => $new_bid,
        'bidder' => $new_user,  
    ];
}

/****************************************/

/* Check if the input is correct */
if(!isset($_REQUEST['value']) ||
   !preg_match(decimal_regex, $_REQUEST['value']) ||
   !isset($_REQUEST['username'])) {
    echo_error_die('invalid-parameters-thr');
}
$thr = $_REQUEST['value'];

/* Start the session */
session_start();

/* Check if already logged in */
$username = session_get_username($error);
/* If not destroy the session and return
 * the error */
if(!$username) {
    session_destr();
    echo_error_die($error);
}
/* Check if the request is done believing
 * to be the correct user */
if($username !== $_REQUEST['username']) {
    session_destr();
    echo_error_die('not-logged-in');
}

/* Connect to the database */
$handle = @mysqli_connect(db_location, db_username, db_password, db_name) or echo_error_die('db-error');
@mysqli_set_charset($handle, db_charset) or echo_error_die('db-error');

/* Start a transaction */
@mysqli_autocommit($handle, false) or echo_error_die('db-error');

try {
    /* Query highest bid and current THR */
    $query_check = "SELECT pra_bid, pra_user, bid_thr FROM product_auction, bid".
    " WHERE pra_id = bid_product AND pra_id = ".product_id.
    " AND bid_user = '".mysqli_real_escape_string($handle, $username).
    "' FOR UPDATE;";

    $result = @mysqli_query($handle, $query_check) or throw_ex('db-error');
    $record = @mysqli_fetch_array($result) or throw_ex('db-error');
    $highest_bid = $record['pra_bid'];
    $bidder = $record['pra_user'];
    $old_thr = $record['bid_thr'];
    @mysqli_free_result($result);
    
    /* Check if the new THR is valid */
    $_SESSION[session_prefix.'thr_'.product_id] = $old_thr;
    /*if($thr <= $old_thr) {
        throw_ex('thr-lower-than-old-error');
    }*/
    if($thr <= $highest_bid && !(!$bidder && $thr == base_bid)) {
        throw_ex('thr-lower-than-bid-error');
    }
    
    /* Query to update THR */
    $query_update ="UPDATE bid SET bid_thr = '".$thr.
        "' WHERE bid_product = ".product_id." AND bid_user = '".
        mysqli_real_escape_string($handle, $username)."';";
    $result = @mysqli_query($handle, $query_update) or throw_ex('db-error');
    $_SESSION[session_prefix.'thr_'.product_id] = $thr;
    
    /* Recompute the bid and generate the response */
    $json = array_merge(
        recompute_bid($handle),
        get_user_info()
    );
    
    /* Commit all the changes */
    @mysqli_commit($handle) or throw_ex('db-error');
    @mysqli_close($handle);
    
    /* Return the new values */
    echo_json($json);
}
catch (Exception $e) {
    /* In case of exception rollback and return the error */
    mysqli_rollback($handle);
    @mysqli_close($handle);
    
    if(isset($old_thr)) {
       $_SESSION[session_prefix.'thr_'.product_id] = $old_thr; 
    }
    echo_error_die($e->getMessage(), get_user_info());
}

?>