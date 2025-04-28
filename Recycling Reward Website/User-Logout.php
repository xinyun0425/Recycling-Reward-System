<?php
    session_start();
    session_destroy();
    
    echo '<script>window.location.href="User-Homepage.php";</script>';
?>