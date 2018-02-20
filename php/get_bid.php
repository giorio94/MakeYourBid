<?php

/* 
 * This script queries the database and returns 
 * the highest bid and the associated username.
 * Requires no authentication to be executed.
 * No parameters are taken from the request.
 */
    
include('common.php');

$handle = @mysqli_connect(db_location, db_username, db_password, db_name) or echo_error_die('db-error');
@mysqli_set_charset($handle, db_charset) or echo_error_die('db-error');
$result = @mysqli_query($handle, query_product_auction) or echo_error_die('db-error');
$record = @mysqli_fetch_array($result) or echo_error_die('db-error');

$array = [
    'bid' => $record['pra_bid'],
    'bidder' => $record['pra_user'],
];
echo_json($array);

@mysqli_free_result($result);
@mysqli_close($handle);

?>