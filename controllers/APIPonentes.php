<?php

namespace Controllers;

use Model\Ponente;

class APIPonentes{

    public static function index(){
        $ponentes = Ponente::all();
        echo json_encode($ponentes);
    }


    public static function ponente(){
        $id = $_GET['id'];
        $id = filter_var($id, FILTER_VALIDATE_INT);

        if(!$id || $id < 1){
            echo json_encode([]);
            return;
        }

        $ponentes = Ponente::find($id);
        echo json_encode($ponentes, JSON_UNESCAPED_SLASHES);
    }
}