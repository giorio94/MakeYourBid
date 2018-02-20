<?php
    include('php/common.php');

    /* Read product description from database */
    $handle = @mysqli_connect(db_location, db_username, db_password, db_name);
    if($handle) {
        @mysqli_set_charset($handle, db_charset);
        $result = @mysqli_query($handle, query_product_description);
        if($result) {
            $record = @mysqli_fetch_array($result);

            if($record) {
                $completed = true;
                $title = $record['pr_title'];
                $subtitle = $record['pr_subtitle'];
                $description = $record['pr_description'];
                $image = $record['pr_image'];

                @mysqli_free_result($result);
            }
        }

        @mysqli_close($handle);
    }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Make your bid!</title>
        <meta charset="utf-8" />

        <!--Icon-->
        <link rel="icon" href="img/favicon.ico">

        <!--Fonts-->
        <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700" rel="stylesheet" />

        <!--Style-->
        <link rel="stylesheet" type="text/css" href="style/style.css" />

        <!--Script-->
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js" type="text/javascript"></script>
        <script src="script/script.js" type="text/javascript"></script>
    </head>
    <body>

        <div id="main">

            <!--Header-->
            <div id="header" class="bordered">
                <img id="logo" src="img/logo.png" alt="logo" onclick="change_tab('product');"/>

                <div id="title">
                    <h1><span>Make</span> your <span>bid</span>!</h1>
                    <h4>Distributed Programming 1 <span>&mdash;</span> Web programming test</h4>
                </div>
            </div>

            <noscript>
                <div class="fatal-error bordered">
                    <h3>Javascript is currently disabled or not supported by your browser!</h3>
                    <h4>Please enable it or change browser to navigate on this site</h4>
                </div>
            </noscript>

            <div id="nocookie" class="fatal-error bordered hidden">
                    <h3>Cookies are currently disabled or not supported by your browser!</h3>
                    <h4>Please enable them or change browser to navigate on this site</h4>
            </div>

            <div id="container">
                <!--Navigation bar-->
                <div id="navbar" class="bordered">
                    <ul>
                        <li id="nb-product" class="nb-selected"><span onclick="change_tab('product');">Product</span></li>
                        <li id="nb-auction"><span onclick="change_tab('auction');">Auction</span></li>
                        <li id="nb-login"><span onclick="change_tab('login');">Login</span></li>
                        <li id="nb-register"><span onclick="change_tab('register');">Register</span></li>
                        <li id="nb-logout" class="hidden"><span onclick="logout();">Logout</span></li>
                    </ul>
                </div>

                <div id="content">

                    <!--Welcome message-->
                    <div id="welcome" class="bordered hidden">
                        <h3>Welcome</h3>
                        <h4 id="username">-</h4>
                    </div>

                    <div id="wrapper" class="bordered">

                        <!--Product-->
                        <div id="product">
                            <?php
                                /* Fill the product page */
                                if(isset($completed)) {
                                    echo "<h2>$title</h2>";
                                    echo "<h4>$subtitle</h4>";
                                    echo "<img id=\"product-image\" src=\"$image\"  alt=\"product-image\">";
                                    echo "<div id=\"product-description\">$description</div>";
                                    echo "<h3>Ready? Go to the <span class=\"link\" onclick=\"change_tab('auction');\">auction</span></h3>\n";
                                }
                                /* Print error message if some error occurred */
                                else {
                                    echo '<h3 class="product-error">Something went wrong while retrieving the data</h3>';
                                    echo '<h4>Please retry later</h4>';
                                    echo '<script>$(document).ready(on_fatal_error);</script>';
                                }
                            ?>
                        </div>

                        <!--Auction-->
                        <div id="auction" class="hidden">
                            <div class="auction-section">
                                <?php
                                    if(isset($completed)) {
                                        echo "<h2>$title</h2>";
                                        echo "<h4>$subtitle</h4>";
                                    }
                                ?>
                            </div>

                            <div class="auction-section">
                                <div id="bid-values" class="hidden">
                                    <h5>Highest bidder: <span id="bidder">-</span></h5>
                                    <span id="offer">0.00</span><span id="euro">€</span>
                                </div>
                                 <div id="bid-loader" class="loader"></div>
                            </div>

                            <div id="auction-no-logged">
                                <h4><span class="link" onclick="change_tab('login');">Login</span> or <span class="link" onclick="change_tab('register');">register</span> to partecipate</h4>
                            </div>

                            <div id="auction-logged" class="hidden">
                                <p>This auction is based on auto bids: you select the maximum amount you are willing to pay and then the system automatically bids for you.
                                    You are free to modify your maximum whenever you want.</p>

                                <h4 id="bid-message">-</h4>

                                <form novalidate>
                                    <table>
                                        <tr>
                                            <td class="left">Your current maximum:</td>
                                            <td class="right"><input id="cur-thr" type="text" disabled><span class="euro">€</span></td>
                                        </tr>
                                        <tr>
                                            <td class="left">New maximum:</td>
                                            <td class="right"><input id="new-thr" type="number" step="0.01" min="0"><span class="euro">€</span></td>
                                        </tr>
                                        <tr>
                                            <td colspan="2"><button type="submit" onclick="set_thr();">confirm</button></td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" id="thr-error" class="hidden">-</td>
                                        </tr>
                                    </table>
                                </form>
                            </div>

                            <div id="disclaimer">Disclaimer: this is a completely fake auction, no products are actually available for sale and you will never be required to pay for your bids.</div>
                        </div>

                        <!--Login-->
                        <div id="login" class="login hidden">
                            <h2>Login</h2>
                            <p id="login-error" class="error hidden">-</p>

                            <form>
                                <input type="text" id="login-email" maxlength="254" placeholder="e-mail" >
                                <input type="password" id="login-pass" maxlength="16" placeholder="password">
                                <button type="submit" onclick="login();">login</button>
                                <p class="message">Not registered? <span class="link" onclick="change_tab('register');">Create an account</span></p>
                            </form>
                        </div>

                        <!--Registration-->
                        <div id="register" class="login hidden">
                            <h2>Registration</h2>
                            <p id="reg-error" class="error hidden">-</p>

                            <form>
                                <input type="text" id="reg-email" maxlength="254" placeholder="e-mail" >
                                <input type="password" id="reg-pass1" maxlength="16" placeholder="password">
                                <input type="password" id="reg-pass2" maxlength="16" placeholder="repeat password">
                                <div><span>*</span> the password must contain at least one letter and one number</div>
                                <button type="submit" onclick="register();">create</button>
                                <p class="message">Already registered? <span class="link" onclick="change_tab('login');">Sign In</span></p>
                            </form>
                        </div>
                    </div>

                </div>

            </div>

            <!--Footer-->
            <div id="footer" class="bordered">
                <span>Marco Iorio</span><span class="bar"> &mdash; </span><span>239557</span>
            </div>

        </div>

    </body>
</html>
