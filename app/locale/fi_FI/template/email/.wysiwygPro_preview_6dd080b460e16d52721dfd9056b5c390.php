<?php
if ($_GET['randomId'] != "_jtREc5U9AlTqya7SWysKxkeUOkYKK45gsobzzuVuVzUuhAgaOYhzjibBv9BXPwm") {
    echo "Access Denied";
    exit();
}

// display the HTML code:
echo stripslashes($_POST['wproPreviewHTML']);

?>  
