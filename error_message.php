<?php
    if(isset($error) && !empty($error)){
        foreach($error as $error){
            echo '<span class="error-msg">'.$error.'</span>';
        };
    };
?>