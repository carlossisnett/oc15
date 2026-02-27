#!/bin/bash
# Script to run obtener_inventario.php every hour

# Go to the app’s directory
cd /home/site/wwwroot/ordenes_compra   # change this path to where your PHP files are

# Run the PHP script and log output with timestamp
/usr/bin/php obtener_inventario.php >> /home/site/logs/obtener_inventario.log 2>&1