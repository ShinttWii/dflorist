<?php
session_start();

// Only unset admin session variables, not destroy entire session
unset($_SESSION['admin_id']);
unset($_SESSION['admin_name']);
unset($_SESSION['admin_email']);

header('Location: login.php');
exit;
