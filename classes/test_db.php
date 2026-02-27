<?php
require "Master.php";

$db = new DBConnection();
$connection = $db->conn;

$prepared = $connection->prepare("INSERT INTO po_list(required_date, username, po_no, discount_percentage, discount_amount, tax_percentage, tax_amount, notes, sub_total, total, ruta_adjunto) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$prepared->bind_param("sssddddsdds", $required_date, $username, $po_no, $discount_percentage, $discount_amount, $tax_percentage, $tax_amount, $notes, $sub_total, $total, $ruta_adjunto);
$prepared->execute();


//$result = $prepared->get_result();
//var_dump($result->fetch_all());

?>