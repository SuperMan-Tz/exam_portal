<?php
// Handle getStudentDetails endpoint
if (isset($_GET['student_id'])) {
  $studentId = trim($_GET['student_id']);
  if (!empty($studentId)) {
    // Redirect to main page with student_id parameter for auto-loading
    header('Location: index.php?student_id=' . urlencode($studentId));
    exit;
  }
} else {
  // If no student_id provided, redirect to main page
  header('Location: index.php');
  exit;
}
?>
