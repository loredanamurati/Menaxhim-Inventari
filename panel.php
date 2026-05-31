<?php
session_start();
require 'includes/functions.php';
if(!empty($_SESSION['user'])) redirect('index.php');
if(!empty($_SESSION['client'])) redirect('client_portal.php');
if(!empty($_SESSION['supplier'])) redirect('supplier_portal.php');
redirect('choose_role.php');
?>
