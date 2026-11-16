<?php
/**
 * Root Router for School Attendance Management System
 */
$sub_index = __DIR__ . '/School_Attendance_Management_System/attendance/index.php';

if (file_exists($sub_index)) {
    $uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    header("Location: {$uri}/School_Attendance_Management_System/attendance/index.php");
    exit;
} else {
    // If DocumentRoot is set to attendance folder directly
    header("Location: index.php");
    exit;
}
?>
