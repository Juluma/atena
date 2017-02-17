<?php
if ($_GET['randomId'] != "erEXch99BSV9exPVvFb7mLCDLYaoNpo9FqVdae6cl7eCvXi2nCU8R3If9FIfaQ76") {
    echo "Access Denied";
    exit();
}

// display the HTML code:
echo stripslashes($_POST['wproPreviewHTML']);

?>  
