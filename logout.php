<?php
session_start();
session_destroy();
header('Location: front_page.php');
exit();
