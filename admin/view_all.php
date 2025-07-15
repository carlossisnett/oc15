<?php

function cambiar_estado_para_aprobador($si_id, $user_id, $conn, $status){
    $query = $conn->query("SELECT codigo_departamento FROM inventory_items where solicitud_id = $si_id;");
    if(gettype($query) == "boolean"){
        echo "";
        return;
    }
   while($row = $query->fetch_assoc()) {
           $departamentos[] = $row['codigo_departamento'];
       }
    //echo $rows;
    //$codigo_departamento = $rows['codigo_departamento'];
    $codigo_departamento_list = "'" . implode("', '", $departamentos) . "'";
   

   //echo $codigo_departamento_list;
  
   $user = $conn->query("SELECT * FROM users where id ='".$user_id."'");
   
   /*
   foreach($user->fetch_array() as $k =>$v){
       $meta[$k] = $v;
   }
   */

   //echo "  " . $meta['id'];

   $aprobador = $conn->query("SELECT * FROM aprobadores 
 WHERE user_id = '{$user_id}' 
 AND departamento IN ({$codigo_departamento_list})");
   
   if($aprobador->num_rows == 0){
       echo "";
       return;
   } else if($aprobador->num_rows > 0){
   //$aprobador = $aprobador->fetch_array();

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

function cambiar_estado_para_almacen($user_id, $conn, $status){
    $query = $conn->query("SELECT type from users where id = $user_id;");
    if(gettype($query) == "boolean"){
        echo "";
        return;
    } else{
        $rows = $query->fetch_assoc();
        $user_type = $rows['type'];
        if($user_type == 3){

            $option_0 = '<option value="0">Pendiente</option>';
            $option_1 = "<option value='1'>Entregado </option>";
            $option_2 = '<option value="2">Rechazado</option>';
            $option_3 = '<option value="3">Listo para Entregar </option>';

            switch($status){
                  case 0:
                       $option_0 = '<option value="0" selected>Pendiente</option>';
                       break;
                  case 1:
                       $option_1 = "<option value='1' selected>Entregado </option>";
                       break;
                  case 2:
                       $option_2 = '<option value="2" selected>Rechazado</option>';
                       break;
                
                  case 3:
                       $option_3 = '<option value="3" selected>Listo para Entregar </option>';
                       break;
            }
            
                   return "<div class=\"col-3\">
                         <p class=\"mb-2\"><b>Cambiar estado de Almacen</b></p>
                         <form id=\"change_po_status\" method=\"post\">
                         <div class=\"d-flex gap-2\">
                         <select class=\"form-select\" aria-label=\"Default select example\">
                             $option_0
                             $option_1
                             $option_2
                             $option_3
                         </select>
                         </div>
                         </form>
                         </div>";

        }
    }

}


?>