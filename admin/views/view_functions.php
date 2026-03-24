<?php

#$number_string = "1234.37" -> "1,234.37"
#$number_string = "1234" -> "1,234.00"
#$number_string = "1234567.37" -> "1,234,567.37"
#$number_string = "14567" -> "14,567.00"


function special_format($number_string){
    if($number_string == ""){
        return "0.00";
    } else if($number_string == "0"){
        return "0.00";
    } else if($number_string == 0){
        return "0.00";
    } else if($number_string == null){
        return "0.00";
    }


    if(strpos($number_string, ".")){
        $position = strpos($number_string, ".");
        $pieces = explode(".", $number_string);
        $whole_number = $pieces[0];
        $decimal_number = $pieces[1];
        if(strlen($whole_number) < 4){
            return $number_string;
        } else if(strlen($whole_number) < 7){
            $thousands = substr($whole_number, 0, strlen($whole_number) - 3);
            $hundreds = substr($whole_number, strlen($whole_number) - 3);
            return $thousands.",".$hundreds.".".$decimal_number;
        } else if(strlen($whole_number) < 10){
            $millions = substr($whole_number, 0, strlen($whole_number) - 6);
            $thousands = substr($whole_number, strlen($whole_number) - 6, 3);
            $hundreds = substr($whole_number, strlen($whole_number) - 3);
            return $millions.",".$thousands.",".$hundreds.".".$decimal_number;
        }

    } else{
        $whole_number = $number_string;
        $decimal_number = "00";
        if(strlen($whole_number) < 4){
            return $number_string . "." . $decimal_number;
        } else if(strlen($whole_number) < 7){
            $thousands = substr($whole_number, 0, strlen($whole_number) - 3);
            $hundreds = substr($whole_number, strlen($whole_number) - 3);
            return $thousands.",".$hundreds.".".$decimal_number;
        } else if(strlen($whole_number) < 10){
            $millions = substr($whole_number, 0, strlen($whole_number) - 6);
            $thousands = substr($whole_number, strlen($whole_number) - 6, 3);
            $hundreds = substr($whole_number, strlen($whole_number) - 3);
            return $millions.",".$thousands.",".$hundreds.".".$decimal_number;
        }
    }
}


function describir_estado($status){
    switch($status){
        case 0:
            return "Cambió el estado de la solicitud a <b>Pendiente</b>";
            break;
        case 1:
            return "<b>Aprobó </b> la solicitud";
            break;
        case 2:
            return "<b>Rechazó</b> la solicitud";
            break;
    }
}

function puede_aprobar($user_id, $po_id, $conn){
    if($user_id == 27 || $user_id == 133){
        return true;
    }

    $query = $conn->query("SELECT codigo_departamento FROM order_items where po_id = '$po_id';");
             if(gettype($query) == "boolean"){
                 echo "";
             }
                $departamentos = array();
            while($row = $query->fetch_assoc()) {
                    $departamentos[] = $row['codigo_departamento'];
                }
 
             $codigo_departamento_list = "'" . implode("', '", $departamentos) . "'";

            $aprobador = $conn->query("SELECT * FROM aprobadores 
          WHERE user_id = '{$user_id}' 
          AND departamento IN ({$codigo_departamento_list})");
            
            if($aprobador->num_rows == 0){
                return false;
            } else{
                return true;
            }
}

function determinar_aprobadores($po_id, $conn){
    $query = $conn->query("SELECT codigo_departamento FROM order_items where po_id = $po_id;");
    if(gettype($query) == "boolean"){
        return false;
    }
    $departamentos = array();
    while($row = $query->fetch_assoc()) {
        $departamentos[] = $row['codigo_departamento'];
    }

    // Get the username of whoever created this PO
    $po_query = $conn->query("SELECT username FROM po_list WHERE id = $po_id");
    $po_row = $po_query->fetch_assoc();
    $po_username = $po_row['username'];

    // Get the user_id of the PO creator
    $creator_query = $conn->query("SELECT id FROM users WHERE username = '{$po_username}'");
    $creator_row = $creator_query->fetch_assoc();
    $creator_id = $creator_row['id'];

    // If the creator is themselves an approver, escalate to superfirma users only
    $is_approver_query = $conn->query("SELECT 1 FROM aprobadores WHERE user_id = '{$creator_id}' LIMIT 1");
    if($is_approver_query && $is_approver_query->num_rows > 0){
        $superfirma_query = $conn->query("SELECT id FROM users WHERE super_firma = 1");
        $superfirma_ids = array();
        while($row = $superfirma_query->fetch_assoc()){
            $superfirma_ids[] = $row['id'];
        }
        return $superfirma_ids;
    }

    $aprobadores = array();
    foreach($departamentos as $departamento){
        $query_2 = $conn->query("SELECT user_id FROM aprobadores where departamento = '{$departamento}' and exclusivo_inventario <> 1");
        while($row = $query_2->fetch_assoc()){
            $aprobadores[] = $row['user_id'];
        }
    }

    return array_unique($aprobadores);
}

function puede_aprobar_compras($user_id, $po_id, $conn){
    $aprobadores = determinar_aprobadores($po_id, $conn);
    if($aprobadores === false || empty($aprobadores)){
        return false;
    }
    return in_array($user_id, $aprobadores);
}

function puede_aprobar_inventario($user_id, $po_id, $conn){
    $query = $conn->query("SELECT codigo_departamento FROM inventory_items where solicitud_id = '$po_id';");
             if(gettype($query) == "boolean"){
                 echo "";
             }
                $departamentos = array();
            while($row = $query->fetch_assoc()) {
                    $departamentos[] = $row['codigo_departamento'];
                }
 
             $codigo_departamento_list = "'" . implode("', '", $departamentos) . "'";

            $aprobador = $conn->query("SELECT * FROM aprobadores 
          WHERE user_id = '{$user_id}' 
          AND departamento IN ({$codigo_departamento_list}) and exclusivo_compras <> 1");
            
            if($aprobador->num_rows == 0){
                return false;
            } else{
                return true;
            }
}




function cambiar_estado_para_aprobador($po_id, $user_id, $conn, $status){
    
   if(puede_aprobar($user_id, $po_id, $conn) == false){
       echo "";
       return;
   } else{

   $option_0 = '<option value="0">Pendiente</option>';
   $option_1 = '<option value="1">Aprobar </option>';
   $option_2 = '<option value="2">Rechazar</option>';
   switch($status){
         case 0:
              $option_0 = '<option value="0" selected>Pendiente</option>';
              break;
         case 1:
              $option_1 = '<option value="1" selected>Aprobar </option>';
              break;
         case 2:
              $option_2 = '<option value="2" selected>Rechazar</option>';
              break;
   }
   
          echo "<div class=\"col-3\">
                <p class=\"mb-2\"><b>Cambiar estado</b></p>
                <form id=\"change_po_status\" method=\"post\">
                <div class=\"d-flex gap-2\">
                <select class=\"form-select\" aria-label=\"Default select example\">
                    $option_0
                    $option_1
                    $option_2
                </select>
                </div>
                </form>
                </div>";
   }
}

function cambiar_estado_para_aprobador_compras($po_id, $user_id, $conn, $status){
    
   if(puede_aprobar_compras($user_id, $po_id, $conn) == false){
       echo "";
       return;
   } else{

   $option_0 = '<option value="0">Pendiente</option>';
   $option_1 = '<option value="1">Aprobar </option>';
   $option_2 = '<option value="2">Rechazar</option>';
   switch($status){
         case 0:
              $option_0 = '<option value="0" selected>Pendiente</option>';
              break;
         case 1:
              $option_1 = '<option value="1" selected>Aprobar </option>';
              break;
         case 2:
              $option_2 = '<option value="2" selected>Rechazar</option>';
              break;
   }
   
          echo "<div class=\"col-3\">
                <p class=\"mb-2\"><b>Cambiar estado</b></p>
                <form id=\"change_po_status\" method=\"post\">
                <div class=\"d-flex gap-2\">
                <select class=\"form-select\" aria-label=\"Default select example\">
                    $option_0
                    $option_1
                    $option_2
                </select>
                </div>
                </form>
                </div>";
   }
}


function cambiar_estado_para_aprobador_inventario($po_id, $user_id, $conn, $status){
    
   if(puede_aprobar_inventario($user_id, $po_id, $conn) == false){
       echo "";
       return;
   } else{

   $option_0 = '<option value="0">Pendiente</option>';
   $option_1 = '<option value="1">Aprobar </option>';
   $option_2 = '<option value="2">Rechazar</option>';
   switch($status){
         case 0:
              $option_0 = '<option value="0" selected>Pendiente</option>';
              break;
         case 1:
              $option_1 = '<option value="1" selected>Aprobar </option>';
              break;
         case 2:
              $option_2 = '<option value="2" selected>Rechazar</option>';
              break;
   }
   
          return "<div class=\"col-3\">
                <p class=\"mb-2\"><b>Cambiar estado</b></p>
                <form id=\"change_po_status\" method=\"post\">
                <div class=\"d-flex gap-2\">
                <select class=\"form-select\" aria-label=\"Default select example\">
                    $option_0
                    $option_1
                    $option_2
                </select>
                </div>
                </form>
                </div>";
   }
}

function cambiar_estado_para_compras($po_id, $user_id, $conn, $status){
     $query = $conn->query("SELECT type from users where id = $user_id;");
    if(gettype($query) == "boolean"){
        echo "";
        return;
    } else{
        $rows = $query->fetch_assoc();
        $user_type = $rows['type'];
        if($user_type == 2){

            $option_0 = '<option value="0">Pendiente</option>';
            $option_2 = '<option value="2">Rechazar</option>';
            $option_3 = '<option value="3">Listo para aprobar </option>';

            switch($status){
                  case 0:
                       $option_0 = '<option value="0" selected>Pendiente</option>';
                       break;
                  case 2:
                        $option_2 = '<option value="2" selected>Rechazar</option>';
                        break;
                  case 3:
                       $option_3 = '<option value="3" selected>Listo para aprobar </option>';
                       break;
            }
            
                   echo "<div class=\"col-3\">
                         <p class=\"mb-2\"><b>Cambiar estado</b></p>
                         <form id=\"change_po_status\" method=\"post\">
                         <div class=\"d-flex gap-2\">
                         <select class=\"form-select\" aria-label=\"Default select example\">
                             $option_0
                             $option_3
                             $option_2
                         </select>
                         </div>
                         </form>
                         </div>";

        }
    }

}

/*
Esta funcion retorna una tabla con las solicitudes de inventario pendientes
*/
function render_solicitud_inventario_table($conn) {
    $query = "
        SELECT po.*, CONCAT_WS(' ', u.firstname, u.lastname) AS sname 
FROM solicitud_de_inventario po 
INNER JOIN users u ON po.username = u.username
WHERE po.salida_de_mercancia = 1
  AND (po.estado_almacen NOT IN (1, 2) OR po.estado_almacen IS NULL)
ORDER BY unix_timestamp(po.date_created) DESC;
    ";
    $result = $conn->query($query);

    echo '<div id="solicitudes_pendientes" class="container-fluid">
            <div class="container-fluid">
                <table class="table table-hover table-striped">
                    <colgroup>
                        <col width="5%">
                        <col width="10%">
                        <col width="10%">
                        <col width="8%">
                        <col width="10%">
                        <col width="10%">
                        <col width="10%">
                        <col width="10%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>ID referencia</th>
                            <th>Fecha Creación</th>
                            <th># Salida de Inventario</th>
                            <th># SAP</th>
                            <th>Solicitante</th>
                            <th>Estado</th>
                            <th>Estado en Almacen</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>';

    while ($row = $result->fetch_assoc()) {
        $row['item_count'] = $conn->query("SELECT * FROM inventory_items WHERE solicitud_id = '{$row['id']}'")->num_rows;

        $id_solicitud = $row['id'];
        $is_salida = $row['salida_de_mercancia'];
        echo '<tr>';

        // ID referencia
        echo '<td class="text-center">' . ($is_salida ? "<b>$id_solicitud</b>" : $id_solicitud) . '</td>';

        // Fecha Creación
        echo '<td>' . date("M d, Y H:i", strtotime($row['date_created'])) . '</td>';

        // Número de Solicitud
        echo '<td>' . $row['numero_solicitud'] . '</td>';

        // SAP DocEntry
        echo '<td class="text-center">' . $row['SAPDocEntry'] . '</td>';

        // Solicitante
        echo '<td>' . $row['sname'] . '</td>';

        // Estado
        echo '<td>';
        switch ($row['status']) {
            case '1':
                echo '<span class="badge badge-success">Aprobado</span>';
                break;
            case '2':
                echo '<span class="badge badge-danger">Rechazado</span>';
                break;
            case '3':
                echo '<b>Listo para aprobar</b>';
                break;
            default:
                echo '<span class="badge badge-secondary">Pendiente</span>';
                break;
        }
        echo '</td>';

        // Estado en Almacen
        echo '<td>';
        switch ($row['estado_almacen']) {
            case '1':
                echo '<span class="badge badge-success">Entregado</span>';
                break;
            case '2':
                echo '<span class="badge badge-danger">Rechazado</span>';
                break;
            case '3':
                echo '<b>Listo para entregar</b>';
                break;
            case '4':
                echo '<b>Enviado a SAP</b>';
                break;
            default:
                echo '';
                break;
        }
        echo '</td>';

        // Acción dropdown
        echo '<td align="center">
                <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
                    Acción
                    <span class="sr-only">Toggle Dropdown</span>
                </button>
                <div class="dropdown-menu" role="menu">
                    <a class="dropdown-item" href="?page=inventario/view_si&id=' . $row['id'] . '">
                        <span class="fa fa-eye text-primary"></span> Ver
                    </a>
                    <div class="dropdown-divider"></div>';
        if ($_SESSION['userdata']['type'] == 1 || $_SESSION['userdata']['type'] == 3) {
            echo '<a class="dropdown-item" href="?page=inventario/manage_si&id=' . $row['id'] . '&edit=true">
                    <span class="fa fa-edit text-primary"></span> Editar
                  </a>';
        }
        echo    '</div>
              </td>';
        echo '</tr>';
    }

    echo        '</tbody>
                </table>
            </div>
          </div>';
}

function render_historial_inventario($conn) {
    $query = "
        SELECT po.*, CONCAT_WS(' ', u.firstname, u.lastname) AS sname, ct.nombre_ccosto
        FROM solicitud_de_inventario po 
        INNER JOIN users u ON po.username = u.username
        JOIN inventory_items iv on iv.solicitud_id = po.id
        join centro_costo ct on ct.codigo_ccosto = iv.codigo_departamento
        ORDER BY unix_timestamp(po.date_created) DESC
    ";
    $result = $conn->query($query);

    echo '<div id="historial_inventario" class="container-fluid">
            <div class="container-fluid">
            <h4> Todas las Salidas de Inventario</h4>
                <table class="table table-hover table-striped">
                    <colgroup>
                       <col width="8%">
                        <col width="14%">
                        <col width="15%">
                        <col width="12%">
                        <col width="15%">
                        <col width="14%">
                        <col width="14%">
                        <col width="8%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>ID referencia</th>
                            <th>Fecha Creación</th>
                            <th># Salida de Inventario</th>
                            <th># SAP</th>
                            <th>Solicitante</th>
                            <th> Departamento </th>
                            <th>Estado</th>
                            <th>Estado en Almacen</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>';

    while ($row = $result->fetch_assoc()) {
        $row['item_count'] = $conn->query("SELECT * FROM inventory_items WHERE solicitud_id = '{$row['id']}'")->num_rows;

        $id_solicitud = $row['id'];
        $is_salida = $row['salida_de_mercancia'];
        echo '<tr>';

        // ID referencia
        echo '<td class="text-center">' . ($is_salida ? "<b>$id_solicitud</b>" : $id_solicitud) . '</td>';

        // Fecha Creación
        echo '<td>' . date("M d, Y H:i", strtotime($row['date_created'])) . '</td>';

        // Número de Solicitud
        echo '<td>' . $row['numero_solicitud'] . '</td>';

        // SAP DocEntry
        echo '<td class="text-center">' . $row['SAPDocEntry'] . '</td>';

        // Solicitante
        echo '<td>' . $row['sname'] . '</td>';

        echo '<td>' . $row['nombre_ccosto'] . '</td>';

        // Estado
        echo '<td>';
        switch ($row['status']) {
            case '1':
                echo '<span class="badge badge-success">Aprobado</span>';
                break;
            case '2':
                echo '<span class="badge badge-danger">Rechazado</span>';
                break;
            case '3':
                echo '<b>Listo para aprobar</b>';
                break;
            default:
                echo '<span class="badge badge-secondary">Pendiente</span>';
                break;
        }
        echo '</td>';

        // Estado en Almacen
        echo '<td>';
        switch ($row['estado_almacen']) {
            case '1':
                echo '<span class="badge badge-success">Entregado</span>';
                break;
            case '2':
                echo '<span class="badge badge-danger">Rechazado</span>';
                break;
            case '3':
                echo '<b>Listo para entregar</b>';
                break;
            case '4':
                echo '<b>Enviado a SAP</b>';
                break;
            default:
                echo '';
                break;
        }
        echo '</td>';

        // Acción dropdown
        echo '<td align="center">
                <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
                    Acción
                    <span class="sr-only">Toggle Dropdown</span>
                </button>
                <div class="dropdown-menu" role="menu">
                    <a class="dropdown-item" href="?page=inventario/view_si&id=' . $row['id'] . '">
                        <span class="fa fa-eye text-primary"></span> Ver
                    </a>
                    <div class="dropdown-divider"></div>';
        if ($_SESSION['userdata']['type'] == 1 || $_SESSION['userdata']['type'] == 3) {
            echo '<a class="dropdown-item" href="?page=inventario/manage_si&id=' . $row['id'] . '&edit=true">
                    <span class="fa fa-edit text-primary"></span> Editar
                  </a>';
        }
        echo    '</div>
              </td>';
        echo '</tr>';
    }

    echo        '</tbody>
                </table>
            </div>
          </div>';
}


?>