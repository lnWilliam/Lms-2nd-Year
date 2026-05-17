<?php
declare(strict_types=1); // ADDED: PHP strict types must be the first PHP statement.

session_start();
session_unset();
session_destroy();
header('Location: login.php');
