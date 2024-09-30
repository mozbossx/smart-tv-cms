<?php
// check if the user is not Super Admin or Admin
if ($user_type !== 'Super Admin' && $user_type !== 'Admin') {
    echo '
        <script>
            alert("You are not authorized to access this page!"); 
            window.location.href = "user_home.php";
        </script>';
    exit;
}

?>