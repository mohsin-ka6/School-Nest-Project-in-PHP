<?php
require_once '../config.php';
require_once '../functions.php';

// The session is already started by config.php

// 1. Unset all of the session variables.
$_SESSION = array();

// 2. Destroy the session.
session_destroy();

// 3. Redirect to login page.
redirect(BASE_URL . '/auth/login.php?logged_out=true');
exit();