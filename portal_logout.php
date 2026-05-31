<?php session_start(); unset($_SESSION['client'], $_SESSION['supplier']); header('Location: choose_role.php'); exit; ?>
