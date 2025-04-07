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

function cambiar_estado_para_aprobador($po_id, $user_id, $conn, $status){
    $query = $conn->query("SELECT codigo_departamento FROM order_items where po_id = $po_id;");
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
            $option_3 = '<option value="3">Listo para aprobar </option>';

            switch($status){
                  case 0:
                       $option_0 = '<option value="0" selected>Pendiente</option>';
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
                         </select>
                         </div>
                         </form>
                         </div>";

        }
    }

}


?>