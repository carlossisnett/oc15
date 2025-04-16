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

function puede_aprobar($user_id, $po_id, $conn){
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
          AND departamento IN ({$codigo_departamento_list})");
            
            if($aprobador->num_rows == 0){
                return false;
            } else{
                return true;
            }
}


?>