"use strict";

/*************************************/
/* Global variables                  */
/*************************************/

/* Tab currently shown */
var current_tab = "product";

/* Current bid value */
var current_bid = 0;
var current_bidder;

/* Current user info */
var username;
var current_thr = 0;

/*************************************/


/*************************************/
/* Validation functions              */
/*************************************/

/* Not too smart function to validate the e-mail address */
function validate_email(email) {
    var regex_email = /^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$/;
    return email.length <= 255 && regex_email.test(email);
}

/* Function for password validation */
function validate_password(password, password2) {
    if (password2 && password !== password2) {
        return false;
    }

    var regex_alpha = /[a-zA-Z]/;
    var regex_number = /[0-9]/;
    return password.length <= 16 &&
        regex_alpha.test(password) &&
        regex_number.test(password);
}

/* Function for decimal number validation */
function validate_decimal(number) {
    var regex_decimal = /^[0-9]{1,9}(\.[0-9]+)?$/;
    return number &&
        regex_decimal.test(number);
}

/*************************************/


/*************************************/
/* Ajax functions                    */
/*************************************/

/* Function called when an error occurs during 
 * an AJAX call */
function error_formatter(error, callback, data) {
    var message;
    switch (error) {
    case 'invalid-parameters':
    case 'login-failed':
        message = "e-mail or password not valid";
        break;
    case 'user-already-exists':
        message = "e-mail already used by another user";
        break;
    case 'invalid-parameters-thr':
    case 'thr-lower-than-old-error':
    case 'thr-lower-than-bid-error':
        message = "invalid value";
        break;
    case 'session-timed-out':
    case 'not-logged-in':
        message = "session timed out";
        var to_login = true;
        break;
    case 'server-error':
    case 'db-error':
    default:
        message = "something went wrong";
        break;
    }

    callback(message, data, to_login);
}

/* Prevents multiple requests */
var in_request = {
    'get': false,
    'set': false
};
/* Function performing an AJAX request using post */
function do_ajax(url, data, on_success, on_error, type) {
    if (!in_request[type]) {
        in_request[type] = true;
        $.post(url, data, function (data, status) {
            if (status === "success") {
                if (data.err_code === 'OK') {
                    on_success(data);
                } else {
                    error_formatter(data.err_code, on_error, data);
                }
            } else {
                error_formatter('server-error', on_error);
            }
            in_request[type] = false;
        }, "json").fail(function () {
            error_formatter('server-error', on_error);
            in_request[type] = false;
        });
    }
}

/*************************************/


/*************************************/
/* Bid update                        */
/*************************************/

/* Update the current bid value */
function update_bid_values(bidder, bid, thr_updated) {
    bid = parseFloat(bid).toFixed(2);
    var bid_updated = (current_bid !== bid);

    current_bidder = bidder;
    current_bid = bid;

    $("#bidder").text(current_bidder || "No bidders yet");
    $("#offer").text(current_bid);

    /* if logged in and updated */
    if (username && (thr_updated || bid_updated)) {
        var message = "Your bid has been exceeded";
        var css_class = "bid-message-ko";

        if (current_bidder === username) {
            message = "You are the highest bidder";
            css_class = "bid-message-ok";
        } else if (current_thr == 0) {
            message = "You haven't done any bids yet";
            css_class = "bid-message-ko";
        }

        if (!$("#bid-message").hasClass(css_class)) {
            $("#bid-message").removeClass();
            $("#bid-message").addClass(css_class);
        }
        $("#bid-message").text(message);

        /* Recompute the new minimum THR */
        /*var new_thr_min = Math.max(current_bid, current_thr);*/
        var new_thr_min = Math.max(current_bid, 0);
        var new_value = Math.max(new_thr_min, current_thr);
        if (current_bidder) {
            new_thr_min += 0.01;
            new_value += 0.01;
        }

        $("#cur-thr").val(current_thr);
        if (!$("#new-thr").val() || (thr_updated &&
            parseFloat($("#new-thr").val()) < new_value)) {

            $("#new-thr").val(new_value.toFixed(2));
        }
        $("#new-thr").attr('min', new_thr_min.toFixed(2));
    }
}

/* Request current bid and update it */
var bid_err_cnt = 10;
function request_bid_values() {
    var bid_err_thresh = 2;
    var bid_err_trans = 300;

    function on_success(data) {
        update_bid_values(data.bidder, data.bid, false);

        if (bid_err_cnt > bid_err_thresh) {
            $("#bid-loader").fadeOut(bid_err_trans, function () {
                $("#bid-values").fadeIn(bid_err_trans);
            });
        }
        bid_err_cnt = 0;
    }

    function on_error(message) {
        bid_err_cnt += 1;
        if (bid_err_cnt > bid_err_thresh) {
            $("#bid-values").fadeOut(bid_err_trans, function () {
                $("#bid-loader").fadeIn(bid_err_trans);
            });
        }
    }

    do_ajax("php/get_bid.php", undefined, on_success, on_error, 'get');
}

/*************************************/


/*************************************/
/* THR update                        */
/*************************************/

/* Updates the thr for the current user */
function set_thr() {
    var new_thr = $("#new-thr").val();
    var thr_min = $("#new-thr").attr('min');

    function on_error(message, data, to_login) {
        /* Session expired */
        if (to_login) {
            after_logout('login');

            $("#login-error").text(message);
            $("#login-error").removeClass("hidden");
        /* Invalid value */
        } else {
            $("#thr-error").text(message);
            $("#thr-error").removeClass("hidden");

            if (data) {
                /* Update according to the new values */
                current_thr = data.thr;
            }
            update_bid_values(current_bidder, current_bid, true);
        }
    }
    function on_success(data) {
        $("#thr-error").addClass("hidden");
        /* Update according to the new values */
        current_thr = data.thr;
        update_bid_values(data.bidder, data.bid, true);
    }

    /* Check if the parameter is valid */
    if (!validate_decimal(new_thr) ||
       parseFloat(new_thr) < parseFloat(thr_min)) {

        error_formatter('invalid-parameters-thr', on_error);
        return;
    }

    /* Set up request data */
    var data = {
        username: username,
        value: parseFloat(new_thr).toFixed(2)
    };
    /* Do the request */
    do_ajax("php/set_thr.php", data, on_success, on_error, 'set');
}

/*************************************/


/*************************************/
/* Login / Registration              */
/*************************************/

/* Function to be executed after the login */
function after_login(new_user, new_thr) {
    username = new_user;
    current_thr = new_thr;

    $("#username").text(username);
    $("#welcome").fadeIn(600);

    $("#nb-login, #nb-register").addClass("hidden");
    $("#nb-logout").removeClass("hidden");
    $("#auction-no-logged").addClass("hidden");
    $("#auction-logged").removeClass("hidden");

    update_bid_values(current_bidder, current_bid, true);
    change_tab("auction");
}

/* Function to be executed after the logout */
function after_logout(new_tab) {
    username = undefined;
    current_thr = 0;

    $("#welcome").fadeOut(600, function () {
        $("#username").text('-');
        $("#new-thr").attr('min', '0');
        $("#cur-thr, #new-thr").val('0');
        $("#bid-message").removeClass();
        $("#bid-message").text('');
    });

    $("#nb-logout").addClass("hidden");
    $("#nb-login, #nb-register").removeClass("hidden");
    $("#auction-logged").addClass("hidden");
    $("#auction-no-logged").removeClass("hidden");

    change_tab(new_tab || 'product');
}

/* Function actually performing the login */
function login() {
    var username = $("#login-email").val();
    var password = $("#login-pass").val();

    function on_error(message) {
        $("#login-error").text(message);
        $("#login-error").removeClass("hidden");
        $("#login-pass").val("");
        $("#login-email").focus();
    }
    function on_success(data) {
        $("#login-error").addClass("hidden");
        after_login(data.username, data.thr);
    }

    /* Check if parameters are valid */
    if (!validate_email(username) ||
        !validate_password(password)) {

        error_formatter('invalid-parameters', on_error);
        return;
    }

    /* Prepare the request */
    var data = {
        action: 'login',
        username: username,
        password: password
    };
    /* Do the request */
    do_ajax("php/authentication.php", data, on_success, on_error, 'set');
}

/* Function actually performing the registration */
function register() {
    var username = $("#reg-email").val();
    var password1 = $("#reg-pass1").val();
    var password2 = $("#reg-pass2").val();

    function on_error(message) {
        $("#reg-error").text(message);
        $("#reg-error").removeClass("hidden");
        $("#reg-pass1, #reg-pass2").val("");
        $("#reg-email").focus();
    }
    function on_success(data) {
        $("#reg-error").addClass("hidden");
        after_login(data.username, data.thr);
    }

    /* Check if parameters are valid */
    if (!validate_email(username) ||
        !validate_password(password1, password2)) {

        error_formatter('invalid-parameters', on_error);
        return;
    }

    /* Prepare the request */
    var data = {
        action: 'register',
        username: username,
        password: password1
    };
    /* Do the request */
    do_ajax("php/authentication.php", data, on_success, on_error, 'set');
}

/* Funciton actually performing the logout */
function logout() {
    function on_success() {
        after_logout();
    }
    function on_error() {
        return;
    }

    /* Prepare the request */
    var data = {
        action: 'logout'
    };
    /* Do the request */
    do_ajax("php/authentication.php", data, on_success, on_error, 'set');
}

/*************************************/


/*************************************/
/* Others                            */
/*************************************/

/* Switch the shown tab */
var in_change = false;
function change_tab(new_tab) {
    if (new_tab && new_tab !== current_tab && !in_change) {
        in_change = true;

        /* Switch the selected item in navbar */
        $("#nb-" + current_tab).removeClass("nb-selected");
        $("#nb-" + new_tab).addClass("nb-selected");

        /* Switch te selected tab */
        $("#" + current_tab).fadeOut(300, function () {
            $("#" + new_tab).fadeIn(300);
            in_change = false;
        });

        /* Do some clean-up */
        if (current_tab === "login") {
            $("#login-error").addClass("hidden");
            $("#login-email, #login-pass").val("");
        } else if (current_tab === "register") {
            $("#reg-error").addClass("hidden");
            $("#reg-email, #reg-pass1, #reg-pass2").val("");
        } else if (current_tab === "auction") {
            $("#thr-error").addClass("hidden");
        }

        /* Set the current tab */
        current_tab = new_tab;
    }
}

/* Disable page navigation */
function on_fatal_error() {
    change_tab('product');
    $("*").removeAttr('onclick');
}

/* Function executed when the document is loaded */
$(document).ready(function () {

    /* Clear form values in case of refresh */
    $("#login-email, #login-pass").val("");
    $("#reg-email, #reg-pass1, #reg-pass2").val("");
    $("#login-error, #reg-error").addClass("hidden");

    /* Check if cookies are enabled */
    if (!navigator.cookieEnabled) {
        on_fatal_error();
        $("#nocookie").removeClass("hidden");
    }

    /* Avoid refresh at submit */
    $("form").submit(function () {
        return false;
    });

    /* Set timeout for ajax calls */
    $.ajaxSetup({ timeout: 5000 });

    /* Get current bid value */
    request_bid_values();
    /* Set timer for requests */
    window.setInterval(request_bid_values, 2000);

    /* Check if already logged in */
    do_ajax("php/authentication.php", {action: 'login'}, function (data) {
        after_login(data.username, data.thr);
    }, function () {
        $("#username").text('-');
        $("#new-thr").attr('min', '0');
        $("#cur-thr, #new-thr").val('0');
        $("#bid-message").removeClass();
        $("#bid-message").text('');
    }, 'set');
});

/*************************************/