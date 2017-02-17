<?php
if ($_GET['randomId'] != "R5ZKHR97KVqafeyfY7anK3iyUyMO9eNZub8TojG79Amr9AGs_gvZAxKi6thz2Mcf") {
    echo "Access Denied";
    exit();
}

// display the HTML code:
echo stripslashes($_POST['wproPreviewHTML']);

?>  
