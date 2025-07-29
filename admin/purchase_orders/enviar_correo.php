<?php

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    use PHPMailer\PHPMailer\Exception;
    
    //require '../../vendor/autoload.php';
    
    //require __DIR__ . '/vendor/autoload.php';
    require __DIR__ . '/../../vendor/autoload.php';
    /*
    Envia correos a los destinatarios que recibe en el array $to
    
    Array, String, String -> ___
    
    Ejemplo: 
    $to = ['desarrollo@prensa.com'], $title = "Reporte de 2024", $body = "este es el reporte..."
    */
    function enviar_email($to, $title, $body) {
    
        #date_default_timezone_set('America/Panama');
        #$todayDate = date("d-M-y");
    
        //echo $to;

        try {
            # Correos a quien se enviará
            #$to = array('desarrollo@prensa.com');
    
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            //$mail->SMTPDebug = SMTP::DEBUG_SERVER;
            //$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->SMTPOptions = array(
            'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
            )
            );
    
            $mail->Host = 'simon.prensa.com';
            $mail->Port = 25;
            $mail->SMTPAuth = false;
            $mail->setFrom('noreply@prensa.com', 'Solicitud de Compra');
    
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $title;
            $mail->Body = $body;
    
            foreach ($to as $email) {
                $mail->addAddress($email);  
            }
        
            $mail->send();
            
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    
    }

    function enviar_email_oc15($to, $title, $body) {
    
        #date_default_timezone_set('America/Panama');
        #$todayDate = date("d-M-y");
    
        //echo $to;

        try {
            # Correos a quien se enviará
            #$to = array('desarrollo@prensa.com');
    
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            //$mail->SMTPDebug = SMTP::DEBUG_SERVER;
            //$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->SMTPOptions = array(
            'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
            )
            );
    
            $mail->Host = 'simon.prensa.com';
            $mail->Port = 25;
            $mail->SMTPAuth = false;
            $mail->setFrom('noreply@prensa.com', 'Sistema OC15');
    
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $title;
            $mail->Body = $body;
    
            foreach ($to as $email) {
                $mail->addAddress($email);  
            }
        
            $mail->send();
            
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    
    }

function enviar_email2($id, $numero_sap) {

        /*if (!is_null($numero_sap) && $numero_sap > 0) 
        {
            // Aquí va la lógica de la función cuando $numero_sap cumple con la condición
            return json_encode(['status' => 'failed', 'err' => 'No se encontró el número SAP.']);
            // Agrega el código correspondiente para enviar el email
        }*/ 

        //echo 'enviar_email2';

        // Incluir la conexión a la base de datos
        GLOBAL $conn;

        // 1. Consultar la tabla po_list
        $stmt_po = $conn->prepare("SELECT a.username, a.date_created, a.required_date, a.notes, b.email,concat(b.firstname,' ',b.lastname) as nombre_solicitante  FROM po_list a inner join users b on a.username = b.username WHERE a.id = ?");
        $stmt_po->bind_param("i", $id);
        $stmt_po->execute();
        $result_po = $stmt_po->get_result();
    
        if ($result_po->num_rows === 0) {
            return json_encode(['status' => 'failed', 'err' => 'No se encontró la orden de compra.']);
        }
    
        $po = $result_po->fetch_assoc();
        $username = htmlspecialchars($po['username']);
        $date_created = htmlspecialchars($po['date_created']);
        $required_date = htmlspecialchars($po['required_date']);
        $notes = htmlspecialchars($po['notes']);
        $destinatario = $po['email'];
        $nombre_solicitante = $po['nombre_solicitante'];
        
        
    
        // 2. Consultar la tabla order_items
        $stmt_items = $conn->prepare("SELECT o.*,i.name, i.description, i.codSAP,concat(i.codSAP,' ',i.description) as nombre_item, concat(ma.codigo_ccosto,' ',ma.nombre_ccosto) as nombre_marca,concat(de.codigo_ccosto,' ',de.nombre_ccosto) as nombre_departamento
        FROM `order_items` o 
        inner join item_list i on o.item_id = i.id 
        inner join centro_costo ma on o.codigo_marca = ma.codigo_ccosto
        inner join centro_costo de on o.codigo_departamento = de.codigo_ccosto
        where o.`po_id` = ?");
        $stmt_items->bind_param("i", $id);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();
    
        if ($result_items->num_rows === 0) {
            return json_encode(['status' => 'failed', 'err' => 'No se encontraron items para la orden de compra.']);
        }

        // 3. Construir la tabla HTML para order_items
        $items_table = '
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Cantidad</th>
                    <th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Artículo</th>
                    <th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Marca</th>
                    <th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Departamento</th>
                </tr>
            </thead>
            <tbody>';
    
        while ($item = $result_items->fetch_assoc()) {
            $quantity = htmlspecialchars($item['quantity']);
            $item_id = htmlspecialchars($item['nombre_item']);
            $codigo_marca = htmlspecialchars($item['nombre_marca']);
            $codigo_departamento = htmlspecialchars($item['nombre_departamento']);
    
            //echo 'codigo_marca ' . $codigo_marca;
            //echo 'item_id ' . $item_id;

            $items_table .= "
                <tr>
                    <td style=\"border: 1px solid #dddddd; text-align: left; padding: 8px;\">$quantity</td>
                    <td style=\"border: 1px solid #dddddd; text-align: left; padding: 8px;\">$item_id</td>
                    <td style=\"border: 1px solid #dddddd; text-align: left; padding: 8px;\">$codigo_marca</td>
                    <td style=\"border: 1px solid #dddddd; text-align: left; padding: 8px;\">$codigo_departamento</td>
                </tr>";
        }
    
        $items_table .= '
            </tbody>
        </table>';
        //echo $items_table;
        $url_orden = base_url . "admin/?page=purchase_orders/view_po&id=" . $id;
        $link_element = "<a href='$url_orden'>Ver Solicitud de Compra $numero_sap</a>";
        

        // 4. Construir el cuerpo del correo en HTML
        $body = "
        <html>
        <head>
            <style>
                /* Estilos para hacer la tabla responsive */
                @media only screen and (max-width: 600px) {
                    table, thead, tbody, th, td, tr { 
                        display: block; 
                    }
                    thead tr { 
                        position: absolute;
                        top: -9999px;
                        left: -9999px;
                    }
                    tr { margin: 0 0 1rem 0; }
                    td { 
                        border: none;
                        position: relative;
                        padding-left: 50%; 
                    }
                    td:before { 
                        position: absolute;
                        top: 0;
                        left: 6px;
                        width: 45%; 
                        padding-right: 10px; 
                        white-space: nowrap;
                        font-weight: bold;
                    }
                    td:nth-of-type(1):before { content: 'Cantidad'; }
                    td:nth-of-type(2):before { content: 'Artículo'; }
                    td:nth-of-type(3):before { content: 'Marca'; }
                    td:nth-of-type(4):before { content: 'Departamento'; }
                }
            </style>
        </head>
        <body>
            <p><strong>Usuario:</strong> $username</p>
            <p><strong>Fecha de Creación:</strong> $date_created</p>
            <p><strong>Fecha Requerida:</strong> $required_date</p>
            <h3> $link_element </h3>
            <h3>Detalles de la Solicitud de Compra</h3>
            $items_table
           
            <p><strong>Comentarios: </strong>$notes</p>
        </body>
        </html>";
    
        // 5. Configurar PHPMailer
        $mail = new PHPMailer(true);
        try {
            // Configuración del servidor SMTP
            $mail->isSMTP();
            $mail->Host = 'simon.prensa.com';
            $mail->Port = 25;
            $mail->SMTPAuth = false;


            $mail->SMTPOptions = array(
                'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
                )
                );
            //$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // O utiliza PHPMailer::ENCRYPTION_SMTPS si es necesario
    
            // Remitente y destinatarios
                   $mail->setFrom('noreply@prensa.com', 'Solicitud de Compra');
    
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
    
            $asunto = '';
            if ($_SESSION['userdata']['codSAP'] == '1877')
            {
                $mail->addAddress($destinatario);
                $asunto = '(TEST) ';
            }
            else
            {
            $mail->addAddress('compras@prensa.com');
            $mail->addCC($destinatario);
            $mail->addCC('desarrollo@prensa.com');
            }
            // Asunto y cuerpo del correo
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $asunto . "::: Nueva Solicitud de Compra. Número SAP: " . $numero_sap . ". Solicitante: " . $nombre_solicitante;
            $mail->Body = $body; //"Nueva de Orden de Compra Body";
    
            // 6. Adjuntar archivos
            // Construir la ruta de la carpeta uploads
            // Asumiendo que la fecha es la fecha_created, ajusta el formato según tus necesidades
            $fecha = date('Y-m-d', strtotime($date_created)); // Formato: 2023-10-13
            $carpeta = "../uploads/{$fecha}_OCID_{$id}/"; // Asegúrate de que la ruta sea correcta
    
            //echo 'carpeta ' . $carpeta;

            if (is_dir($carpeta)) {
                // Obtener todos los archivos de la carpeta
                $archivos = glob($carpeta . '*');
    
                foreach ($archivos as $archivo) {
                    if (is_file($archivo)) {
                        //console.log('is file');
                        $mail->addAttachment($archivo);
                    }
                }
            } else {

                //echo 'carpeta no existe';
                // Puedes manejar el caso donde la carpeta no existe
                // Por ejemplo, registrar un mensaje o continuar sin adjuntos
                // echo "La carpeta de adjuntos no existe: $carpeta";
            }
    
            // 7. Enviar el correo
            $mail->send();
            // Puedes retornar una respuesta exitosa
            //echo json_encode(['status' => 'success', 'message' => 'Correo enviado correctamente.']);
        } catch (Exception $e) {
            // Manejo de errores
            //echo json_encode(['status' => 'failed', 'err' => "Error al enviar el correo: {$mail->ErrorInfo}"]);
        }
    }

function detalles_orden_de_compra($id, $conn){

        // 2. Consultar la tabla order_items
        $stmt_items = $conn->prepare("SELECT o.*,i.name, i.description, i.codSAP,concat(i.codSAP,' ',i.description) as nombre_item, concat(ma.codigo_ccosto,' ',ma.nombre_ccosto) as nombre_marca,concat(de.codigo_ccosto,' ',de.nombre_ccosto) as nombre_departamento
        FROM `order_items` o 
        inner join item_list i on o.item_id = i.id 
        inner join centro_costo ma on o.codigo_marca = ma.codigo_ccosto
        inner join centro_costo de on o.codigo_departamento = de.codigo_ccosto
        where o.`po_id` = ?");
        $stmt_items->bind_param("i", $id);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();
    
        if ($result_items->num_rows === 0) {
            return json_encode(['status' => 'failed', 'err' => 'No se encontraron items para la orden de compra.']);
        }

        // 3. Construir la tabla HTML para order_items
        $items_table = '
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Cantidad</th>
                    <th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Artículo</th>
                    <th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Marca</th>
                    <th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Departamento</th>
                </tr>
            </thead>
            <tbody>';
    
        while ($item = $result_items->fetch_assoc()) {
            $quantity = htmlspecialchars($item['quantity']);
            $item_id = htmlspecialchars($item['nombre_item']);
            $codigo_marca = htmlspecialchars($item['nombre_marca']);
            $codigo_departamento = htmlspecialchars($item['nombre_departamento']);
    
            //echo 'codigo_marca ' . $codigo_marca;
            //echo 'item_id ' . $item_id;

            $items_table .= "
                <tr>
                    <td style=\"border: 1px solid #dddddd; text-align: left; padding: 8px;\">$quantity</td>
                    <td style=\"border: 1px solid #dddddd; text-align: left; padding: 8px;\">$item_id</td>
                    <td style=\"border: 1px solid #dddddd; text-align: left; padding: 8px;\">$codigo_marca</td>
                    <td style=\"border: 1px solid #dddddd; text-align: left; padding: 8px;\">$codigo_departamento</td>
                </tr>";
        }
    
        $items_table .= '
            </tbody>
        </table>';

        return $items_table;
}

function detalles_salida_de_inventario($id, $conn){

    // 2. Consultar la tabla order_items
    $stmt_items = $conn->prepare("SELECT o.*,i.name, i.ubicacion_almacen, i.description, i.codSAP,concat(i.codSAP,' ',i.description) as nombre_item, concat(ma.codigo_ccosto,' ',ma.nombre_ccosto) as nombre_marca,concat(de.codigo_ccosto,' ',de.nombre_ccosto) as nombre_departamento
    FROM `inventory_items` o 
    inner join item_list i on o.item_id = i.id 
    inner join centro_costo ma on o.codigo_marca = ma.codigo_ccosto
    inner join centro_costo de on o.codigo_departamento = de.codigo_ccosto
    where o.`solicitud_id` = ?");
    $stmt_items->bind_param("i", $id);
    $stmt_items->execute();
    $result_items = $stmt_items->get_result();

    if ($result_items->num_rows === 0) {
        return json_encode(['status' => 'failed', 'err' => 'No se encontraron items para la salida de inventario.']);
    }

    // 3. Construir la tabla HTML para order_items
    $items_table = '
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr>
                <th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Cantidad</th>
                <th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Artículo</th>
                <th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Ubicación</th>
                <th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Marca</th>
                <th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Departamento</th>
            </tr>
        </thead>
        <tbody>';

    while ($item = $result_items->fetch_assoc()) {
        $quantity = htmlspecialchars($item['quantity']);
        $item_id = htmlspecialchars($item['nombre_item']);
        $ubicacion_almacen = htmlspecialchars($item['ubicacion_almacen']);
        $codigo_marca = htmlspecialchars($item['nombre_marca']);
        $codigo_departamento = htmlspecialchars($item['nombre_departamento']);

        //echo 'codigo_marca ' . $codigo_marca;
        //echo 'item_id ' . $item_id;

        $items_table .= "
            <tr>
                <td style=\"border: 1px solid #dddddd; text-align: left; padding: 8px;\">$quantity</td>
                <td style=\"border: 1px solid #dddddd; text-align: left; padding: 8px;\">$item_id</td>
                <td style=\"border: 1px solid #dddddd; text-align: left; padding: 8px;\">$ubicacion_almacen</td>
                <td style=\"border: 1px solid #dddddd; text-align: left; padding: 8px;\">$codigo_marca</td>
                <td style=\"border: 1px solid #dddddd; text-align: left; padding: 8px;\">$codigo_departamento</td>
            </tr>";
    }

    $items_table .= '
        </tbody>
    </table>';

    return $items_table;
}

/*
    Esta funcion retorna un array con los emails de aquellos que aprobaron/pueden aprobar la orden de compra
*/

function determinar_aprobadores($po_id, $conn){
    $query = $conn->query("SELECT codigo_departamento FROM order_items where po_id = $po_id;");
    if(gettype($query) == "boolean"){
        return false;
    }
   while($row = $query->fetch_assoc()) {
           $departamentos[] = $row['codigo_departamento'];
       }
    //echo $rows;
    //$codigo_departamento = $rows['codigo_departamento'];
    $codigo_departamento_list = "'" . implode("', '", $departamentos) . "'";

   $aprobador = $conn->query("SELECT u.email FROM aprobadores a join users u on a.user_id = u.id
 WHERE departamento IN ({$codigo_departamento_list})");

 $lista_aprobadores = array(); // Initialize an empty array to store rows
   
   if($aprobador->num_rows == 0){
       return false;
   } else if($aprobador->num_rows > 0){
    $lista_aprobadores = $aprobador->fetch_array();
    return $lista_aprobadores;
    }
}

/*
    Esta funcion retorna un array con los emails de aquellos que aprobaron/pueden aprobar la salide de inventario
*/

function determinar_aprobadores_inventario($si_id, $conn){
    $query = $conn->query("SELECT codigo_departamento FROM inventory_items where solicitud_id = $si_id;");
    if(gettype($query) == "boolean"){
        return false;
    }
   while($row = $query->fetch_assoc()) {
           $departamentos[] = $row['codigo_departamento'];
       }
    //echo $rows;
    //$codigo_departamento = $rows['codigo_departamento'];
    $codigo_departamento_list = "'" . implode("', '", $departamentos) . "'";

   $aprobador = $conn->query("SELECT u.email FROM aprobadores a join users u on a.user_id = u.id
 WHERE departamento IN ({$codigo_departamento_list})");

 $lista_aprobadores = array(); // Initialize an empty array to store rows
   
   if($aprobador->num_rows == 0){
       return false;
   } else if($aprobador->num_rows > 0){
    $lista_aprobadores = $aprobador->fetch_array();
    return $lista_aprobadores;
    }
}

/*
    Esta funcion retorna un array con los emails de aquellos a los que se les debe enviar la notificacion
    de que la orden de compra ha sido aprobada
    Number, Connection -> Array
*/

function destinatarios_orden_de_compra($id, $conn){
    $lista_final = determinar_aprobadores($id, $conn);
    $solicitante_email = $conn->query("SELECT email FROM users u join po_list p on p.username = u.username and p.id = $id;");
    $lista_final[] = $solicitante_email->fetch_array()['email'];
    $lista_final[] = "compras@prensa.com";
    $lista_final[] = "desarrollo@prensa.com";
    return $lista_final;
}

/*
    Esta funcion retorna un array con los emails de aquellos a los que se les debe enviar la notificacion
    de que la salida de inventario ha sido aprobada
    Number, Connection -> Array
*/

function destinatarios_salida_de_inventario($id, $conn){
    $lista_final = determinar_aprobadores_inventario($id, $conn);
    $solicitante_email = $conn->query("SELECT email FROM users u join solicitud_de_inventario p on p.username = u.username and p.id = $id;");
    $lista_final[] = $solicitante_email->fetch_array()['email'];
    $lista_final[] = "almacen@prensa.com";
    $lista_final[] = "desarrollo@prensa.com";
    return $lista_final;
}



/*
Dada una orden de compra aprobada esta funcion retorna un mensaje html de los que la aprobaron
*/

function aprobaciones_orden_de_compra($id, $conn){
    //$query = $conn->query("SELECT  from aprobaciones where orden_compra_id = $id");
    //$destinatarios = array();
    $mensaje_final = "";
    $historial = $conn->query("SELECT u.name , a.estado, a.hora_creacion FROM aprobaciones a JOIN users u ON u.id = a.user_id where orden_compra_id = $id and a.estado = 1");
    if(gettype($historial) == "boolean"){
        //echo "";
    } else {
        if ($historial && $historial->num_rows > 0) {
            $mensaje_final = $mensaje_final . "Aprobado por: <br>";
            $mensaje_final = $mensaje_final . "<ul>";
        
            while ($row = $historial->fetch_assoc()) {
                $mensaje_final = $mensaje_final ."<li>" . htmlspecialchars($row['name']) . " " . " el " . date("Y-m-d H:i:s", strtotime($row['hora_creacion'])) . "</li>";
            }
        
            $mensaje_final = $mensaje_final . "</ul>";
    }
    return $mensaje_final;
    }

}

/*
Dada una salida de inventario aprobada esta funcion retorna un mensaje html de los que la aprobaron
*/

function aprobaciones_salida_de_inventario($id, $conn){
    //$query = $conn->query("SELECT  from aprobaciones where orden_compra_id = $id");
    //$destinatarios = array();
    $mensaje_final = "";
    $historial = $conn->query("SELECT u.name , a.estado, a.hora_creacion FROM aprobaciones_inventario a JOIN users u ON u.id = a.user_id where solicitud_inventario_id = $id and a.estado = 1");
    if(gettype($historial) == "boolean"){
        //echo "";
    } else {
        if ($historial && $historial->num_rows > 0) {
            $mensaje_final = $mensaje_final . "Aprobado por: <br>";
            $mensaje_final = $mensaje_final . "<ul>";
        
            while ($row = $historial->fetch_assoc()) {
                $mensaje_final = $mensaje_final ."<li>" . htmlspecialchars($row['name']) . " " . " el " . date("Y-m-d H:i:s", strtotime($row['hora_creacion'])) . "</li>";
            }
        
            $mensaje_final = $mensaje_final . "</ul>";
    }
    return $mensaje_final;
    }

}

/*
Number, Array ->
*/

function enviar_email_orden_de_compra_aprobada($id){
    GLOBAL $conn;

    // 1. Consultar la tabla po_list
    $stmt_po = $conn->prepare("SELECT a.username, a.date_created, a.required_date, a.notes, a.po_no, b.email,concat(b.firstname,' ',b.lastname) as nombre_solicitante  FROM po_list a inner join users b on a.username = b.username WHERE a.id = ?");
    $stmt_po->bind_param("i", $id);
    $stmt_po->execute();
    $result_po = $stmt_po->get_result();

    if ($result_po->num_rows === 0) {
        return json_encode(['status' => 'failed', 'err' => 'No se encontró la orden de compra.']);
    }

    $po = $result_po->fetch_assoc();
    $username = htmlspecialchars($po['username']);
    $date_created = htmlspecialchars($po['date_created']);
    $required_date = htmlspecialchars($po['required_date']);
    $notes = htmlspecialchars($po['notes']);
    $destinatarios = destinatarios_orden_de_compra($id, $conn);

    $po_no = htmlspecialchars($po['po_no']);
    $nombre_solicitante = $po['nombre_solicitante'];

    $items_table = detalles_orden_de_compra($id, $conn);

    $url_orden = base_url . "admin/?page=purchase_orders/view_po&id=" . $id;
    $link_element = "<a href='$url_orden'>Ver Pedido de Compra $po_no</a>";
    $aprobaciones = aprobaciones_orden_de_compra($id, $conn);
    

    // 4. Construir el cuerpo del correo en HTML
    $body = "
    <html>
    <head>
        <style>
            /* Estilos para hacer la tabla responsive */
            @media only screen and (max-width: 600px) {
                table, thead, tbody, th, td, tr { 
                    display: block; 
                }
                thead tr { 
                    position: absolute;
                    top: -9999px;
                    left: -9999px;
                }
                tr { margin: 0 0 1rem 0; }
                td { 
                    border: none;
                    position: relative;
                    padding-left: 50%; 
                }
                td:before { 
                    position: absolute;
                    top: 0;
                    left: 6px;
                    width: 45%; 
                    padding-right: 10px; 
                    white-space: nowrap;
                    font-weight: bold;
                }
                td:nth-of-type(1):before { content: 'Cantidad'; }
                td:nth-of-type(2):before { content: 'Artículo'; }
                td:nth-of-type(3):before { content: 'Marca'; }
                td:nth-of-type(4):before { content: 'Departamento'; }
            }
        </style>
    </head>
    <body>
        <p> $aprobaciones </p>
        <p><strong>Usuario del solicitante:</strong> $username</p>
        <p><strong>Fecha de Creación:</strong> $date_created</p>
        <p><strong>Fecha Requerida:</strong> $required_date</p>
        <h3> $link_element </h3>
        <h3>Detalles del Pedido de Compra</h3>
        $items_table
       
        <p><strong>Comentarios: </strong>$notes</p>
    </body>
    </html>";

    enviar_email($destinatarios, "La orden de compra $po_no ha sido aprobada", $body);

}

function enviar_email_salida_de_inventario_aprobada($id){
    GLOBAL $conn;

    // 1. Consultar la tabla po_list
    $stmt_po = $conn->prepare("SELECT a.username, a.date_created, a.required_date, a.notes, a.numero_solicitud, b.email,concat(b.firstname,' ',b.lastname) as nombre_solicitante  FROM solicitud_de_inventario a inner join users b on a.username = b.username WHERE a.id = ?");
    $stmt_po->bind_param("i", $id);
    $stmt_po->execute();
    $result_po = $stmt_po->get_result();

    if ($result_po->num_rows === 0) {
        return json_encode(['status' => 'failed', 'err' => 'No se encontró la salida de inventario.']);
    }

    $si = $result_po->fetch_assoc();
    $username = htmlspecialchars($si['username']);
    $date_created = htmlspecialchars($si['date_created']);
    $required_date = htmlspecialchars($si['required_date']);
    $notes = htmlspecialchars($si['notes']);
    $destinatarios = destinatarios_salida_de_inventario($id, $conn);

    $numero_solicitud = htmlspecialchars($si['numero_solicitud']);
    $nombre_solicitante = $si['nombre_solicitante'];

    $items_table = detalles_salida_de_inventario($id, $conn);

    $url_orden = base_url . "admin/?page=inventario/view_si&id=" . $id;
    $link_element = "<a href='$url_orden'>Ver Salida de Inventario $id </a>";
    $aprobaciones = aprobaciones_salida_de_inventario($id, $conn);
    

    // 4. Construir el cuerpo del correo en HTML
    $body = "
    <html>
    <head>
        <style>
            /* Estilos para hacer la tabla responsive */
            @media only screen and (max-width: 600px) {
                table, thead, tbody, th, td, tr { 
                    display: block; 
                }
                thead tr { 
                    position: absolute;
                    top: -9999px;
                    left: -9999px;
                }
                tr { margin: 0 0 1rem 0; }
                td { 
                    border: none;
                    position: relative;
                    padding-left: 50%; 
                }
                td:before { 
                    position: absolute;
                    top: 0;
                    left: 6px;
                    width: 45%; 
                    padding-right: 10px; 
                    white-space: nowrap;
                    font-weight: bold;
                }
                td:nth-of-type(1):before { content: 'Cantidad'; }
                td:nth-of-type(2):before { content: 'Artículo'; }
                td:nth-of-type(3):before { content: 'Ubicación'; }
                td:nth-of-type(4):before { content: 'Marca'; }
                td:nth-of-type(5):before { content: 'Departamento'; }
            }
        </style>
    </head>
    <body>
        <p> ID referencia: $id </p>
        <p> $aprobaciones </p>
        <p><strong>Usuario del solicitante:</strong> $username</p>
        <p><strong>Fecha de Creación:</strong> $date_created</p>
        <p><strong>Fecha Requerida:</strong> $required_date</p>
        <h3> $link_element </h3>
        <h3>Detalles de la Salida de Inventario</h3>
        $items_table
       
        <p><strong>Comentarios: </strong>$notes</p>
    </body>
    </html>";

    enviar_email($destinatarios, "La salida de inventario con ID referencia: $id ha sido aprobada", $body);

}

function enviar_email3($id) {

        /*if (!is_null($numero_sap) && $numero_sap > 0) 
        {
            // Aquí va la lógica de la función cuando $numero_sap cumple con la condición
            return json_encode(['status' => 'failed', 'err' => 'No se encontró el número SAP.']);
            // Agrega el código correspondiente para enviar el email
        }*/ 

        //echo 'enviar_email2';

        // Incluir la conexión a la base de datos
        GLOBAL $conn;

        // 1. Consultar la tabla po_list
        $stmt_po = $conn->prepare("SELECT a.username, a.date_created, a.required_date, a.notes, a.po_no, b.email,concat(b.firstname,' ',b.lastname) as nombre_solicitante  FROM po_list a inner join users b on a.username = b.username WHERE a.id = ?");
        $stmt_po->bind_param("i", $id);
        $stmt_po->execute();
        $result_po = $stmt_po->get_result();
    
        if ($result_po->num_rows === 0) {
            return json_encode(['status' => 'failed', 'err' => 'No se encontró la orden de compra.']);
        }
    
        $po = $result_po->fetch_assoc();
        $username = htmlspecialchars($po['username']);
        $date_created = htmlspecialchars($po['date_created']);
        $required_date = htmlspecialchars($po['required_date']);
        $notes = htmlspecialchars($po['notes']);
        $destinatario = $po['email'];
        $po_no = htmlspecialchars($po['po_no']);
        $nombre_solicitante = $po['nombre_solicitante'];
        
        
    
        // 2. Consultar la tabla order_items
        $stmt_items = $conn->prepare("SELECT o.*,i.name, i.description, i.codSAP,concat(i.codSAP,' ',i.description) as nombre_item, concat(ma.codigo_ccosto,' ',ma.nombre_ccosto) as nombre_marca,concat(de.codigo_ccosto,' ',de.nombre_ccosto) as nombre_departamento
        FROM `order_items` o 
        inner join item_list i on o.item_id = i.id 
        inner join centro_costo ma on o.codigo_marca = ma.codigo_ccosto
        inner join centro_costo de on o.codigo_departamento = de.codigo_ccosto
        where o.`po_id` = ?");
        $stmt_items->bind_param("i", $id);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();
    
        if ($result_items->num_rows === 0) {
            return json_encode(['status' => 'failed', 'err' => 'No se encontraron items para la orden de compra.']);
        }

        // 3. Construir la tabla HTML para order_items
        $items_table = '
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Cantidad</th>
                    <th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Artículo</th>
                    <th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Marca</th>
                    <th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Departamento</th>
                </tr>
            </thead>
            <tbody>';
    
        while ($item = $result_items->fetch_assoc()) {
            $quantity = htmlspecialchars($item['quantity']);
            $item_id = htmlspecialchars($item['nombre_item']);
            $codigo_marca = htmlspecialchars($item['nombre_marca']);
            $codigo_departamento = htmlspecialchars($item['nombre_departamento']);
    
            //echo 'codigo_marca ' . $codigo_marca;
            //echo 'item_id ' . $item_id;

            $items_table .= "
                <tr>
                    <td style=\"border: 1px solid #dddddd; text-align: left; padding: 8px;\">$quantity</td>
                    <td style=\"border: 1px solid #dddddd; text-align: left; padding: 8px;\">$item_id</td>
                    <td style=\"border: 1px solid #dddddd; text-align: left; padding: 8px;\">$codigo_marca</td>
                    <td style=\"border: 1px solid #dddddd; text-align: left; padding: 8px;\">$codigo_departamento</td>
                </tr>";
        }
    
        $items_table .= '
            </tbody>
        </table>';
        //echo $items_table;
        $url_orden = base_url . "admin/?page=purchase_orders/view_po&id=" . $id;
        $link_element = "<a href='$url_orden'>Ver Solicitud de Compra $po_no</a>";
        

        // 4. Construir el cuerpo del correo en HTML
        $body = "
        <html>
        <head>
            <style>
                /* Estilos para hacer la tabla responsive */
                @media only screen and (max-width: 600px) {
                    table, thead, tbody, th, td, tr { 
                        display: block; 
                    }
                    thead tr { 
                        position: absolute;
                        top: -9999px;
                        left: -9999px;
                    }
                    tr { margin: 0 0 1rem 0; }
                    td { 
                        border: none;
                        position: relative;
                        padding-left: 50%; 
                    }
                    td:before { 
                        position: absolute;
                        top: 0;
                        left: 6px;
                        width: 45%; 
                        padding-right: 10px; 
                        white-space: nowrap;
                        font-weight: bold;
                    }
                    td:nth-of-type(1):before { content: 'Cantidad'; }
                    td:nth-of-type(2):before { content: 'Artículo'; }
                    td:nth-of-type(3):before { content: 'Marca'; }
                    td:nth-of-type(4):before { content: 'Departamento'; }
                }
            </style>
        </head>
        <body>
            <p><strong>Usuario:</strong> $username</p>
            <p><strong>Fecha de Creación:</strong> $date_created</p>
            <p><strong>Fecha Requerida:</strong> $required_date</p>
            <h3> $link_element </h3>
            <h3>Detalles de la Solicitud de Compra</h3>
            $items_table
           
            <p><strong>Comentarios: </strong>$notes</p>
        </body>
        </html>";
    
        // 5. Configurar PHPMailer
        $mail = new PHPMailer(true);
        try {
            // Configuración del servidor SMTP
            $mail->isSMTP();
            $mail->Host = 'simon.prensa.com';
            $mail->Port = 25;
            $mail->SMTPAuth = false;


            $mail->SMTPOptions = array(
                'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
                )
                );
            //$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // O utiliza PHPMailer::ENCRYPTION_SMTPS si es necesario
    
            // Remitente y destinatarios
                   $mail->setFrom('noreply@prensa.com', 'Solicitud de Compra');
    
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
    
            $asunto = '';
            if ($_SESSION['userdata']['codSAP'] == '1877')
            {
                $mail->addAddress($destinatario);
                $asunto = '(TEST) ';
            }
            else
            {
            $mail->addAddress('compras@prensa.com');
            $mail->addCC($destinatario);
            $mail->addCC('desarrollo@prensa.com');
            }
            // Asunto y cuerpo del correo
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $asunto . "::: Nueva Solicitud de Compra. Número de Orden: " . $po_no . ". Solicitante: " . $nombre_solicitante;
            $mail->Body = $body; //"Nueva de Orden de Compra Body";
    
            // 6. Adjuntar archivos
            // Construir la ruta de la carpeta uploads
            // Asumiendo que la fecha es la fecha_created, ajusta el formato según tus necesidades
            $fecha = date('Y-m-d', strtotime($date_created)); // Formato: 2023-10-13
            $carpeta = "../uploads/{$fecha}_OCID_{$id}/"; // Asegúrate de que la ruta sea correcta
    
            //echo 'carpeta ' . $carpeta;

            if (is_dir($carpeta)) {
                // Obtener todos los archivos de la carpeta
                $archivos = glob($carpeta . '*');
    
                foreach ($archivos as $archivo) {
                    if (is_file($archivo)) {
                        //console.log('is file');
                        $mail->addAttachment($archivo);
                    }
                }
            } else {

                //echo 'carpeta no existe';
                // Puedes manejar el caso donde la carpeta no existe
                // Por ejemplo, registrar un mensaje o continuar sin adjuntos
                // echo "La carpeta de adjuntos no existe: $carpeta";
            }
    
            // 7. Enviar el correo
            $mail->send();
            // Puedes retornar una respuesta exitosa
            //echo json_encode(['status' => 'success', 'message' => 'Correo enviado correctamente.']);
        } catch (Exception $e) {
            // Manejo de errores
            //echo json_encode(['status' => 'failed', 'err' => "Error al enviar el correo: {$mail->ErrorInfo}"]);
        }
    }

function enviar_email_solicitud_inventario($id, $numero_sap){

        /*if (!is_null($numero_sap) && $numero_sap > 0) 
        {
            // Aquí va la lógica de la función cuando $numero_sap cumple con la condición
            return json_encode(['status' => 'failed', 'err' => 'No se encontró el número SAP.']);
            // Agrega el código correspondiente para enviar el email
        }*/ 

        // Incluir la conexión a la base de datos
        GLOBAL $conn;

        // 1. Consultar la tabla solicitud_de_inventario
        $stmt_po = $conn->prepare("SELECT a.username, a.date_created, a.required_date, a.notes, b.email,concat(b.firstname,' ',b.lastname) as nombre_solicitante  FROM solicitud_de_inventario a inner join users b on a.username = b.username WHERE a.id = ?");
        $stmt_po->bind_param("i", $id);
        $stmt_po->execute();
        $result_po = $stmt_po->get_result();
    
        if ($result_po->num_rows === 0) {
            return json_encode(['status' => 'failed', 'err' => 'No se encontró la solicitud de inventario.']);
        }
    
        $po = $result_po->fetch_assoc();
        $username = htmlspecialchars($po['username']);
        $date_created = htmlspecialchars($po['date_created']);
        $required_date = htmlspecialchars($po['required_date']);
        $notes = htmlspecialchars($po['notes']);
        $destinatario = $po['email'];
        $nombre_solicitante = $po['nombre_solicitante'];
        
        
    
        // 2. Consultar la tabla order_items
        $stmt_items = $conn->prepare("SELECT o.*,i.name, i.description, i.ubicacion_almacen, i.stock_actual, i.codSAP,concat(i.codSAP,' ',i.description) as nombre_item, concat(ma.codigo_ccosto,' ',ma.nombre_ccosto) as nombre_marca,concat(de.codigo_ccosto,' ',de.nombre_ccosto) as nombre_departamento
        FROM `inventory_items` o 
        inner join item_list i on o.item_id = i.id 
        inner join centro_costo ma on o.codigo_marca = ma.codigo_ccosto
        inner join centro_costo de on o.codigo_departamento = de.codigo_ccosto
        where o.`solicitud_id` = ?");
        $stmt_items->bind_param("i", $id);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();
    
        if ($result_items->num_rows === 0) {
            return json_encode(['status' => 'failed', 'err' => 'No se encontraron items para la salida de inventario.']);
        }

        // 3. Construir la tabla HTML para order_items
        // 
        $items_table = '
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Cantidad</th>
                    <th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Artículo</th>
                    <th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Ubicación</th>
                    <th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Marca</th>
                    <th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Departamento</th>
                </tr>
            </thead>
            <tbody>';
    
        while ($item = $result_items->fetch_assoc()) {
            $quantity = htmlspecialchars($item['quantity']);
            $item_name = htmlspecialchars($item['nombre_item']);
            $ubicacion_almacen = htmlspecialchars($item['ubicacion_almacen']);
            $stock_actual = htmlspecialchars($item['stock_actual']);
            $codigo_marca = htmlspecialchars($item['nombre_marca']);
            $codigo_departamento = htmlspecialchars($item['nombre_departamento']);
    
            //echo 'codigo_marca ' . $codigo_marca;
            //echo 'item_name ' . $item_name;
            //

            $items_table .= "
                <tr>
                    <td style=\"border: 1px solid #dddddd; text-align: left; padding: 8px;\">$quantity</td>
                    <td style=\"border: 1px solid #dddddd; text-align: left; padding: 8px;\">$item_name</td>
                    <td style=\"border: 1px solid #dddddd; text-align: left; padding: 8px;\">$ubicacion_almacen</td>
                    <td style=\"border: 1px solid #dddddd; text-align: left; padding: 8px;\">$codigo_marca</td>
                    <td style=\"border: 1px solid #dddddd; text-align: left; padding: 8px;\">$codigo_departamento</td>
                </tr>";
        }
    
        $items_table .= '
            </tbody>
        </table>';
    

        //echo $items_table;

        // 4. Construir el cuerpo del correo en HTML
        $body = "
        <html>
        <head>
            <style>
                /* Estilos para hacer la tabla responsive */
                @media only screen and (max-width: 600px) {
                    table, thead, tbody, th, td, tr { 
                        display: block; 
                    }
                    thead tr { 
                        position: absolute;
                        top: -9999px;
                        left: -9999px;
                    }
                    tr { margin: 0 0 1rem 0; }
                    td { 
                        border: none;
                        position: relative;
                        padding-left: 50%; 
                    }
                    td:before { 
                        position: absolute;
                        top: 0;
                        left: 6px;
                        width: 45%; 
                        padding-right: 10px; 
                        white-space: nowrap;
                        font-weight: bold;
                    }
                    td:nth-of-type(1):before { content: 'Cantidad'; }
                    td:nth-of-type(2):before { content: 'Artículo'; }
                    td:nth-of-type(3):before { content: 'Ubicación'; }
                    td:nth-of-type(4):before { content: 'Marca'; }
                    td:nth-of-type(5):before { content: 'Departamento'; }
                }
            </style>
        </head>
        <body>
            <p><strong>Usuario:</strong> $username</p>
            <p><strong>Fecha de Creación:</strong> $date_created</p>
            <p><strong>Fecha Requerida:</strong> $required_date</p>
            <h3>Detalles de la Salida de Inventario</h3>
            $items_table
            <p><strong>Comentarios: </strong>$notes</p>
        </body>
        </html>";
    
        // 5. Configurar PHPMailer
        $mail = new PHPMailer(true);
        try {
            // Configuración del servidor SMTP
            $mail->isSMTP();
            $mail->Host = 'simon.prensa.com';
            $mail->Port = 25;
            $mail->SMTPAuth = false;


            $mail->SMTPOptions = array(
                'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
                )
                );
            //$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // O utiliza PHPMailer::ENCRYPTION_SMTPS si es necesario
    
            // Remitente y destinatarios
                   $mail->setFrom('noreply@prensa.com', 'Salida de Inventario');
    
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
    
            $asunto = '';
            /*
            if ($_SESSION['userdata']['codSAP'] == '1833')
            {
                $mail->addAddress($destinatario);
                $asunto = '(TEST) ';
            }
            else
            {
            $mail->addAddress('almacen@prensa.com');
            $mail->addCC($destinatario);
            $mail->addCC('desarrollo@prensa.com');
            }
            */
            $mail->addAddress('almacen@prensa.com');
            $mail->addCC($destinatario);
            $mail->addCC('desarrollo@prensa.com');
            // Asunto y cuerpo del correo
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $asunto . "::: Nueva Salida de Inventario. Número SAP: " . $numero_sap . ". Solicitante: " . $nombre_solicitante;
            $mail->Body = $body; //"Nueva de Orden de Inventario Body";
    
            /*
            // 6. Adjuntar archivos
            // Construir la ruta de la carpeta uploads
            // Asumiendo que la fecha es la fecha_created, ajusta el formato según tus necesidades
            $fecha = date('Y-m-d', strtotime($date_created)); // Formato: 2023-10-13
            $carpeta = "../uploads/{$fecha}_OC_{$numero_sap}/"; // Asegúrate de que la ruta sea correcta
    
            //echo 'carpeta ' . $carpeta;

            if (is_dir($carpeta)) {
                // Obtener todos los archivos de la carpeta
                $archivos = glob($carpeta . '*');
    
                foreach ($archivos as $archivo) {
                    if (is_file($archivo)) {
                        //console.log('is file');
                        $mail->addAttachment($archivo);
                    }
                }
            } else {

                //echo 'carpeta no existe';
                // Puedes manejar el caso donde la carpeta no existe
                // Por ejemplo, registrar un mensaje o continuar sin adjuntos
                // echo "La carpeta de adjuntos no existe: $carpeta";
            }

            */
    
            // 7. Enviar el correo
            $mail->send();
            // Puedes retornar una respuesta exitosa
            //echo json_encode(['status' => 'success', 'message' => 'Correo enviado correctamente.']);
        } catch (Exception $e) {
            // Manejo de errores
            //echo json_encode(['status' => 'failed', 'err' => "Error al enviar el correo: {$mail->ErrorInfo}"]);
        }
    }

function enviar_email_salida_de_mercancia($id){

        /*if (!is_null($numero_sap) && $numero_sap > 0) 
        {
            // Aquí va la lógica de la función cuando $numero_sap cumple con la condición
            return json_encode(['status' => 'failed', 'err' => 'No se encontró el número SAP.']);
            // Agrega el código correspondiente para enviar el email
        }*/ 

        // Incluir la conexión a la base de datos
        GLOBAL $conn;

        // 1. Consultar la tabla solicitud_de_inventario
        $stmt_po = $conn->prepare("SELECT a.username, a.date_created, a.required_date, a.notes, b.email,concat(b.firstname,' ',b.lastname) as nombre_solicitante  FROM solicitud_de_inventario a inner join users b on a.username = b.username WHERE a.id = ?");
        $stmt_po->bind_param("i", $id);
        $stmt_po->execute();
        $result_po = $stmt_po->get_result();
    
        if ($result_po->num_rows === 0) {
            return json_encode(['status' => 'failed', 'err' => 'No se encontró la solicitud de inventario.']);
        }
    
        $po = $result_po->fetch_assoc();
        $username = htmlspecialchars($po['username']);
        $date_created = htmlspecialchars($po['date_created']);
        $required_date = htmlspecialchars($po['required_date']);
        $notes = htmlspecialchars($po['notes']);
        $destinatario = $po['email'];
        $nombre_solicitante = $po['nombre_solicitante'];
        
        
    
        // 2. Consultar la tabla order_items
        $stmt_items = $conn->prepare("SELECT o.*,i.name, i.description, i.ubicacion_almacen, i.stock_actual, i.codSAP,concat(i.codSAP,' ',i.description) as nombre_item, concat(ma.codigo_ccosto,' ',ma.nombre_ccosto) as nombre_marca,concat(de.codigo_ccosto,' ',de.nombre_ccosto) as nombre_departamento
        FROM `inventory_items` o 
        inner join item_list i on o.item_id = i.id 
        inner join centro_costo ma on o.codigo_marca = ma.codigo_ccosto
        inner join centro_costo de on o.codigo_departamento = de.codigo_ccosto
        where o.`solicitud_id` = ?");
        $stmt_items->bind_param("i", $id);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();
    
        if ($result_items->num_rows === 0) {
            return json_encode(['status' => 'failed', 'err' => 'No se encontraron items para la salida de inventario.']);
        }

        // 3. Construir la tabla HTML para order_items
        // <th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Ubicación</th>
        $items_table = '
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Cantidad</th>
                    <th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Artículo</th>
                    <th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Ubicación</th>
                    <th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Marca</th>
                    <th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Departamento</th>
                </tr>
            </thead>
            <tbody>';
    
        while ($item = $result_items->fetch_assoc()) {
            $quantity = htmlspecialchars($item['quantity']);
            $item_name = htmlspecialchars($item['nombre_item']);
            $ubicacion_almacen = htmlspecialchars($item['ubicacion_almacen']);
            $stock_actual = htmlspecialchars($item['stock_actual']);
            $codigo_marca = htmlspecialchars($item['nombre_marca']);
            $codigo_departamento = htmlspecialchars($item['nombre_departamento']);
    
            //echo 'codigo_marca ' . $codigo_marca;
            //echo 'item_name ' . $item_name;
            //

            $items_table .= "
                <tr>
                    <td style=\"border: 1px solid #dddddd; text-align: left; padding: 8px;\">$quantity</td>
                    <td style=\"border: 1px solid #dddddd; text-align: left; padding: 8px;\">$item_name</td>
                    <td style=\"border: 1px solid #dddddd; text-align: left; padding: 8px;\">$ubicacion_almacen</td>
                    <td style=\"border: 1px solid #dddddd; text-align: left; padding: 8px;\">$codigo_marca</td>
                    <td style=\"border: 1px solid #dddddd; text-align: left; padding: 8px;\">$codigo_departamento</td>
                </tr>";
        }
    
        $items_table .= '
            </tbody>
        </table>';
    

        //echo $items_table;

        // 4. Construir el cuerpo del correo en HTML
        $body = "
        <html>
        <head>
            <style>
                /* Estilos para hacer la tabla responsive */
                @media only screen and (max-width: 600px) {
                    table, thead, tbody, th, td, tr { 
                        display: block; 
                    }
                    thead tr { 
                        position: absolute;
                        top: -9999px;
                        left: -9999px;
                    }
                    tr { margin: 0 0 1rem 0; }
                    td { 
                        border: none;
                        position: relative;
                        padding-left: 50%; 
                    }
                    td:before { 
                        position: absolute;
                        top: 0;
                        left: 6px;
                        width: 45%; 
                        padding-right: 10px; 
                        white-space: nowrap;
                        font-weight: bold;
                    }
                    td:nth-of-type(1):before { content: 'Cantidad'; }
                    td:nth-of-type(2):before { content: 'Artículo'; }
                    td:nth-of-type(3):before { content: 'Ubicación'; }
                    td:nth-of-type(4):before { content: 'Marca'; }
                    td:nth-of-type(5):before { content: 'Departamento'; }
                }
            </style>
        </head>
        <body>
            <p> ID referencia: $id </p>
            <p><strong>Usuario:</strong> $username</p>
            <p><strong>Fecha de Creación:</strong> $date_created</p>
            <p><strong>Fecha Requerida:</strong> $required_date</p>
            <h3>Detalles de la Salida de Inventario</h3>
            $items_table
            <p><strong>Comentarios: </strong>$notes</p>
        </body>
        </html>";
    
        // 5. Configurar PHPMailer
        $mail = new PHPMailer(true);
        try {
            // Configuración del servidor SMTP
            $mail->isSMTP();
            $mail->Host = 'simon.prensa.com';
            $mail->Port = 25;
            $mail->SMTPAuth = false;


            $mail->SMTPOptions = array(
                'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
                )
                );
            //$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // O utiliza PHPMailer::ENCRYPTION_SMTPS si es necesario
    
            // Remitente y destinatarios
                   $mail->setFrom('noreply@prensa.com', 'Salida de Inventario');
    
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
    
            $asunto = '';
            /*
            if ($_SESSION['userdata']['codSAP'] == '1833')
            {
                $mail->addAddress($destinatario);
                $asunto = '(TEST) ';
            }
            else
            {
            $mail->addAddress('almacen@prensa.com');
            $mail->addCC($destinatario);
            $mail->addCC('desarrollo@prensa.com');
            }
            */
            $mail->addAddress('almacen@prensa.com');
            $mail->addCC($destinatario);
            $mail->addCC('desarrollo@prensa.com');
            // Asunto y cuerpo del correo
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $asunto . "::: Nueva Salida de Inventario. ID referencia: " . $id . ". Solicitante: " . $nombre_solicitante;
            $mail->Body = $body; //"Nueva de Orden de Inventario Body";
    
            /*
            // 6. Adjuntar archivos
            // Construir la ruta de la carpeta uploads
            // Asumiendo que la fecha es la fecha_created, ajusta el formato según tus necesidades
            $fecha = date('Y-m-d', strtotime($date_created)); // Formato: 2023-10-13
            $carpeta = "../uploads/{$fecha}_OC_{$numero_sap}/"; // Asegúrate de que la ruta sea correcta
    
            //echo 'carpeta ' . $carpeta;

            if (is_dir($carpeta)) {
                // Obtener todos los archivos de la carpeta
                $archivos = glob($carpeta . '*');
    
                foreach ($archivos as $archivo) {
                    if (is_file($archivo)) {
                        //console.log('is file');
                        $mail->addAttachment($archivo);
                    }
                }
            } else {

                //echo 'carpeta no existe';
                // Puedes manejar el caso donde la carpeta no existe
                // Por ejemplo, registrar un mensaje o continuar sin adjuntos
                // echo "La carpeta de adjuntos no existe: $carpeta";
            }

            */
    
            // 7. Enviar el correo
            $mail->send();
            // Puedes retornar una respuesta exitosa
            //echo json_encode(['status' => 'success', 'message' => 'Correo enviado correctamente.']);
        } catch (Exception $e) {
            // Manejo de errores
            //echo json_encode(['status' => 'failed', 'err' => "Error al enviar el correo: {$mail->ErrorInfo}"]);
        }
    }

function salida_de_inventario_asunto($estado_almacen, $id, $nombre_solicitante){
    switch ($estado_almacen) {
        case 1:
            return "::: Almacén ha Entregado la Salida de Inventario. ID referencia: " . $id . ". Solicitante: " . $nombre_solicitante;
            break;
        case 2:
            return "::: Almacén ha Rechazado la Salida de Inventario. ID referencia: " . $id . ". Solicitante: " . $nombre_solicitante;
            break;
        case 3:
            return "::: Su Solicitud de Inventario está lista para entregar.  ID referencia: " . $id . ". Solicitante: " . $nombre_solicitante;
            break;
        default:
        case 0: 
            return "::: Modificación de Salida de Inventario. ID referencia: " . $id . ". Solicitante: " . $nombre_solicitante;
            break;

    }
}



function enviar_email_salida_de_mercancia_actualizacion($id, $usuario_actualizador, $estado_almacen){

    /*
    Recibir estado de modificacion:
        1: Aprobada
        2: Rechazada
        3: Listo por Entregar
        4: 
    */

        /*if (!is_null($numero_sap) && $numero_sap > 0) 
        {
            // Aquí va la lógica de la función cuando $numero_sap cumple con la condición
            return json_encode(['status' => 'failed', 'err' => 'No se encontró el número SAP.']);
            // Agrega el código correspondiente para enviar el email
        }*/ 

        // Incluir la conexión a la base de datos
        $conn = new mysqli('localhost', 'root', '', 'ordenes_compra');
        //GLOBAL $conn;

        // 1. Consultar la tabla solicitud_de_inventario
        $stmt_po = $conn->prepare("SELECT a.username, a.date_created, a.required_date, a.notes, b.email,concat(b.firstname,' ',b.lastname) as nombre_solicitante  FROM solicitud_de_inventario a inner join users b on a.username = b.username WHERE a.id = ?");
        $stmt_po->bind_param("s", $id);
        $stmt_po->execute();
        $result_po = $stmt_po->get_result();
    
        if ($result_po->num_rows === 0) {
            return json_encode(['status' => 'failed', 'err' => 'No se encontró la solicitud de inventario.']);
        }
    
        $po = $result_po->fetch_assoc();
        $username = htmlspecialchars($po['username']);
        $date_created = htmlspecialchars($po['date_created']);
        $required_date = htmlspecialchars($po['required_date']);
        $notes = htmlspecialchars($po['notes']);
        $destinatario = $po['email'];
        $nombre_solicitante = $po['nombre_solicitante'];
        
        
    
        // 2. Consultar la tabla order_items
        $stmt_items = $conn->prepare("SELECT o.*,i.name, i.description, i.ubicacion_almacen, i.stock_actual, i.codSAP,concat(i.codSAP,' ',i.description) as nombre_item, concat(ma.codigo_ccosto,' ',ma.nombre_ccosto) as nombre_marca,concat(de.codigo_ccosto,' ',de.nombre_ccosto) as nombre_departamento
        FROM `inventory_items` o 
        inner join item_list i on o.item_id = i.id 
        inner join centro_costo ma on o.codigo_marca = ma.codigo_ccosto
        inner join centro_costo de on o.codigo_departamento = de.codigo_ccosto
        where o.`solicitud_id` = ?");
        $stmt_items->bind_param("s", $id);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();
    
        if ($result_items->num_rows === 0) {
            return json_encode(['status' => 'failed', 'err' => 'No se encontraron items para la salida de inventario.']);
        }

        // 3. Construir la tabla HTML para order_items
        // <th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Ubicación</th>
        $items_table = '
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Cantidad</th>
                    <th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Artículo</th>
                    <th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Ubicación</th>
                    <th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Marca</th>
                    <th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Departamento</th>
                </tr>
            </thead>
            <tbody>';
    
        while ($item = $result_items->fetch_assoc()) {
            $quantity = htmlspecialchars($item['quantity']);
            $item_name = htmlspecialchars($item['nombre_item']);
            $ubicacion_almacen = htmlspecialchars($item['ubicacion_almacen']);
            $stock_actual = htmlspecialchars($item['stock_actual']);
            $codigo_marca = htmlspecialchars($item['nombre_marca']);
            $codigo_departamento = htmlspecialchars($item['nombre_departamento']);
    
            //echo 'codigo_marca ' . $codigo_marca;
            //echo 'item_name ' . $item_name;
            //

            $items_table .= "
                <tr>
                    <td style=\"border: 1px solid #dddddd; text-align: left; padding: 8px;\">$quantity</td>
                    <td style=\"border: 1px solid #dddddd; text-align: left; padding: 8px;\">$item_name</td>
                    <td style=\"border: 1px solid #dddddd; text-align: left; padding: 8px;\">$ubicacion_almacen</td>
                    <td style=\"border: 1px solid #dddddd; text-align: left; padding: 8px;\">$codigo_marca</td>
                    <td style=\"border: 1px solid #dddddd; text-align: left; padding: 8px;\">$codigo_departamento</td>
                </tr>";
        }
    
        $items_table .= '
            </tbody>
        </table>';
    

        //echo $items_table;

        // 4. Construir el cuerpo del correo en HTML
        $body = "
        <html>
        <head>
            <style>
                /* Estilos para hacer la tabla responsive */
                @media only screen and (max-width: 600px) {
                    table, thead, tbody, th, td, tr { 
                        display: block; 
                    }
                    thead tr { 
                        position: absolute;
                        top: -9999px;
                        left: -9999px;
                    }
                    tr { margin: 0 0 1rem 0; }
                    td { 
                        border: none;
                        position: relative;
                        padding-left: 50%; 
                    }
                    td:before { 
                        position: absolute;
                        top: 0;
                        left: 6px;
                        width: 45%; 
                        padding-right: 10px; 
                        white-space: nowrap;
                        font-weight: bold;
                    }
                    td:nth-of-type(1):before { content: 'Cantidad'; }
                    td:nth-of-type(2):before { content: 'Artículo'; }
                    td:nth-of-type(3):before { content: 'Ubicación'; }
                    td:nth-of-type(4):before { content: 'Marca'; }
                    td:nth-of-type(5):before { content: 'Departamento'; }
                }
            </style>
        </head>
        <body>
            <p> ID referencia: $id </p>
            <p><strong>Usuario solicitante:</strong> $username</p>
            <p><strong>Modificado por:</strong> $usuario_actualizador</p>
            <p><strong>Fecha de Creación:</strong> $date_created</p>
            <p><strong>Fecha Requerida:</strong> $required_date</p>
            <h3>Detalles de la Salida de Inventario</h3>
            $items_table
            <p><strong>Comentarios: </strong>$notes</p>
        </body>
        </html>";
    
        // 5. Configurar PHPMailer
        $mail = new PHPMailer(true);
        try {
            // Configuración del servidor SMTP
            $mail->isSMTP();
            $mail->Host = 'simon.prensa.com';
            $mail->Port = 25;
            $mail->SMTPAuth = false;


            $mail->SMTPOptions = array(
                'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
                )
                );
            //$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // O utiliza PHPMailer::ENCRYPTION_SMTPS si es necesario
    
            // Remitente y destinatarios
                   $mail->setFrom('noreply@prensa.com', 'Salida de Inventario');
    
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
    
            $asunto = '';
            /*
            if ($_SESSION['userdata']['codSAP'] == '1833')
            {
                $mail->addAddress($destinatario);
                $asunto = '(TEST) ';
            }
            else
            {
            $mail->addAddress('almacen@prensa.com');
            $mail->addCC($destinatario);
            $mail->addCC('desarrollo@prensa.com');
            }
            */
            $mail->addAddress('almacen@prensa.com');
            $mail->addCC($destinatario);
            $mail->addCC('desarrollo@prensa.com');
            // Asunto y cuerpo del correo
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $asunto = salida_de_inventario_asunto($estado_almacen, $id, $nombre_solicitante);
            $mail->Subject = $asunto; //. "::: Modificación de Salida de Inventario. ID referencia: " . $id . ". Solicitante: " . $nombre_solicitante;
            $mail->Body = $body; //"Nueva de Orden de Inventario Body";

            $mail->send();
            // Puedes retornar una respuesta exitosa
            //echo json_encode(['status' => 'success', 'message' => 'Correo enviado correctamente.']);
        } catch (Exception $e) {
            // Manejo de errores
            echo json_encode(['status' => 'failed', 'err' => "Error al enviar el correo: {$mail->ErrorInfo}"]);
        }
    }

?>