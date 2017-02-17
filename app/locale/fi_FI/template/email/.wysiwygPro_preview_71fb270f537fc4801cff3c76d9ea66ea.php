<?php
if ($_GET['randomId'] != "KkKWZBln5Rr55aRX1wCYmPhQpezmfZ5gu5x9h_HNvNt7XDJc_NCfo7HUSqgWt5ni") {
    echo "Access Denied";
    exit();
}

// display the HTML code:
echo stripslashes($_POST['wproPreviewHTML']);

?>  
