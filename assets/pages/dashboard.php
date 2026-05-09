<?php
session_start();
$message = "";
$message_Type = "";
if (isset($_SESSION['success'])) {
    $message = $_SESSION['success'];
    $messageType = 'success';
    unset($_SESSION['success']);
    unset($_SESSION['user_data']);
} elseif (isset($_SESSION['error'])) {
    $message = $_SESSION['error'];
    $messageType = 'error';
    unset($_SESSION['error']);
}
echo $message ,  $message_Type;

?>