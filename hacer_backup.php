<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'ordenes_compra';

date_default_timezone_set("America/Panama");

// Create backup directory if it doesn't exist
$backup_dir = __DIR__ . DIRECTORY_SEPARATOR . 'backup';
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

// Generate backup file path
$backup_file = $backup_dir . DIRECTORY_SEPARATOR . 'backup_' . $dbname . '_' . date("Y-m-d_h_i") . '.sql';

// Path to mysqldump.exe (adjust path if needed for XAMPP)
$mysqldump = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';

$command = "\"$mysqldump\" -u $user " . ($pass ? "-p$pass" : "") . " $dbname > \"$backup_file\"";

system($command, $result);

if ($result === 0) {
    echo "Backup successful: $backup_file";
} else {
    echo "Backup failed.";
}
?>
