<?php
/**
 * Logout Page
 */

session_start();
require_once '../src/config/config.php';
require_once '../src/Core/Auth.php';

use App\Core\Auth;

Auth::logout();
header("Location: login.php");
exit;
