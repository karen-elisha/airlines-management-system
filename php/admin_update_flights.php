<?php
$conn = new mysqli("localhost", "root", "", "your_database_name");

if (isset($_POST['id']) && isset($_POST['status'])) {
  $id = $_POST['id'];
  $status = $_POST['status'];

  $stmt = $conn->prepare("UPDATE flights SET status = ? WHERE id = ?");
  $stmt->bind_param("si", $status, $id);
  $stmt->execute();

  echo "Updated successfully!";
}
?>
