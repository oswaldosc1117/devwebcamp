<?php

namespace Controllers;

use Model\Regalos;
use Model\Registros;

class APIRegalos{

    public static function index(){
        if(!is_admin()){
            echo json_encode([]);
            return; 
        }
        $regalos = Regalos::all();

        foreach($regalos as $regalo){
            $regalo->total = Registros::totalArray(['regalo_id' => $regalo->id, 'paquete_id' => "1"]);
        }

        echo json_encode($regalos);
        return;
    }
}