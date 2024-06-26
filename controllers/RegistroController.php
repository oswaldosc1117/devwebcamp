<?php

namespace Controllers;

use Model\Dias;
use MVC\Router;
use Model\Horas;
use Model\Eventos;
use Model\Ponente;
use Model\Usuario;
use Model\Paquetes;
use Model\Registros;
use Model\Categorias;
use Model\EventosRegistros;
use Model\Regalos;

class RegistroController{

    public static function crear(Router $router){

        if(!is_auth()){
            header('Location: /');
            return;
        }

        // Verificar si el usuario ya está registrado.
        $registro = Registros::where('usuario_id', $_SESSION['id']);

        if(isset($registro) && $registro->paquete_id === '3' || $registro->paquete_id === '2'){
            header('Location: /boleto?id=' . urlencode($registro->token));
            return;
        }

        if(isset($registro) && $registro->paquete_id === "1"){
            header('Location: /finalizar-registro/conferencias');
            return;
        }

        $router->render('registro/crear', ['titulo' => 'Finalizar Registro']);
    }


    public static function gratis(Router $router){

        if($_SERVER['REQUEST_METHOD'] === 'POST'){
            if(!is_auth()){
                header('Location: /login');
                return;
            }

            // Verificar si el usuario ya está registrado.
            $registro = Registros::where('usuario_id', $_SESSION['id']);

            if(isset($registro) && $registro->paquete_id === '3'){
                header('Location: /boleto?id=' . urlencode($registro->token));
                return;
            }

            $token = substr(md5(uniqid(rand(), true)), 0, 8);

            // Crear registro
            $datos = [
                'paquete_id' => 3,
                'pago_id' => '',
                'token' => $token,
                'usuario_id' => $_SESSION['id']
            ];

            $registro = new Registros($datos);
            $resultado = $registro->guardar();

            if($resultado){
                header('Location: /boleto?id=' . urlencode($registro->token));
                return;
            }
        }
    }


    public static function boleto(Router $router){

        // Validar la URL
        $id = $_GET['id'];

        if(!$id || !strlen($id) === 8){
            header('Location: /');
            return;
        }

        // Buscar en la BD
        $registro = Registros::where('token', $id);
        
        if(!$registro){
            header('Location: /');
            return;
        }

        // Llenar la tabla de referencia
        $registro->usuario = Usuario::find($registro->usuario_id);
        $registro->paquete = Paquetes::find($registro->paquete_id);

        $router->render('registro/boleto', ['titulo' => 'Bienvenido a DevWebCamp', 'registro' => $registro]);
    }


    public static function pagar(Router $router){

        if($_SERVER['REQUEST_METHOD'] === 'POST'){
            if(!is_auth()){
                header('Location: /login');
                return;
            }

            // Validar que POST no venga vacio
            if(empty($_POST)){
                echo json_encode([]);
                return;
            }

            // Crear el registro
            $datos = $_POST;
            $datos['token'] = substr(md5(uniqid(rand(), true)), 0, 8);
            $datos['usuario_id'] = $_SESSION['id'];

            try {
                $registro = new Registros($datos);
                $resultado = $registro->guardar();

                echo json_encode($resultado);
            } catch (\Throwable $th) {
                echo json_encode(['resultado' => 'error']);
            }
        }
    }


    public static function conferencias(Router $router){

        if(!is_auth()){
            header('Location: /login');
            return;
        }

        // Validar que el usuario tenga el plan presencial
        $usuario_id = $_SESSION['id'];
        $registro = Registros::where('usuario_id', $usuario_id);

        if(isset($registro) && $registro->paquete_id === "2"){
            header('Location: /boleto?id=' . urlencode($registro->token));
            return;
        }

        if($registro->paquete_id !== '1'){
            header('Location: /');
            return;
        }

        // Redireccionar a boleto virtual en caso de que el usuario haya finalizado el registro
        if(isset($registro->regalo_id) && $registro->paquete_id === "1"){
            header('Location: /boleto?id=' . urlencode($registro->token));
            return;
        }

        $eventos = Eventos::ordenar('hora_id', 'ASC');
        $eventos_formateados = [];

        foreach($eventos as $evento){
            
            $evento->categoria = Categorias::find($evento->categoria_id);
            $evento->dia = Dias::find($evento->dia_id);
            $evento->hora = Horas::find($evento->hora_id);
            $evento->ponente = Ponente::find($evento->ponente_id);

            if($evento->dia_id === '1' && $evento->categoria_id === '1'){
                $eventos_formateados['conferencias_viernes'][] = $evento;
            }
            elseif($evento->dia_id === '2' && $evento->categoria_id === '1'){
                $eventos_formateados['conferencias_sabado'][] = $evento;
            }
            elseif($evento->dia_id === '1' && $evento->categoria_id === '2'){
                $eventos_formateados['workshops_viernes'][] = $evento;
            }
            elseif($evento->dia_id === '2' && $evento->categoria_id === '2'){
                $eventos_formateados['workshops_sabado'][] = $evento;
            }
        }

        $regalos = Regalos::all('ASC');

        // Manejando el registro mediante POST
        if($_SERVER['REQUEST_METHOD'] === 'POST'){

            // Revisar que el usuario este autenticado
            if(!is_auth()){
                header('Location: /login');
                return;
            }

            $eventos = explode(',', $_POST['eventos']);
            if(empty($eventos)){
                echo json_encode(['resultado' => false]);
                return;
            }

            // Obtener el registro de usuario
            $registro = Registros::where('usuario_id', $_SESSION['id']);
            if(!isset($registro) || $registro->paquete_id !== "1"){
                echo json_encode(['resultado' => false]);
                return;
            }

            $eventos_array = [];

            // Validar la disponibilidad de los eventos seleccionados
            foreach($eventos as $evento_id){
                $evento = Eventos::find($evento_id);

                // Comprobar que el evento exista
                if(!isset($evento) || $evento->disponibles === '0'){
                    echo json_encode(['resultado' => false]);
                    return;
                }

                $eventos_array[] = $evento;
            }

            foreach($eventos_array as $evento){
                $evento->disponibles -= 1;
                $evento->guardar();

                // Almacenar el registro
                $datos = [
                    'evento_id' => (int) $evento->id,
                    'registro_id' => (int) $registro->id
                ];

                $registro_usuario = new EventosRegistros($datos);
                $registro_usuario->guardar();
            }

            // Almacenar el regalo
            $registro->sincronizar(['regalo_id' => $_POST['regalo_id']]);
            $resultado = $registro->guardar();

            if($resultado){
                echo json_encode([
                    'resultado' => $resultado,
                    'token' =>$registro->token
                ]);
            } else{
                echo json_encode(['resultado' => false]);
            }
            
            return;
        }

        $router->render('registro/conferencias', [
            'titulo' => 'Elige Workshops y Conferencias',
            'eventos' => $eventos_formateados,
            'regalos' => $regalos
        ]);
    }
}