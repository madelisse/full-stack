<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\User;

class UserController extends Controller {

    public function pruebas(Request $request) {
        return "Accion de pruebas de USER-CONTROLLER";
    }

    public function register(Request $request) {

        // recoger los datos del usuario por post
        $json = $request->input('json', null);
        $params = json_decode($json); //objeto
        $params_array = json_decode($json, true); //array

        if (!empty($params) && !empty($params_array)) {
            //limpiar datos
            $params_array = array_map('trim', $params_array);

            //validar datos
            $validate = \Validator::make($params_array, [
                        'name' => 'required|alpha',
                        'surname' => 'required|alpha',
                        'email' => 'required|email|unique:users', //comprobar si el usuario existe ya (duplicado)
                        'password' => 'required'
            ]);

            if ($validate->fails()) {
                //validacion ha fallado
                $data = array(
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'El usuario no se ha creado correctamente',
                    'errors' => $validate->errors()
                );
            } else {
                //validacion pasada correctamente
                //cifrar contrasenia
                $pwd = hash('sha256', $params->password);

                //crear el usuario
                $user = new User();
                $user->name = $params_array['name'];
                $user->surname = $params_array['surname'];
                $user->email = $params_array['email'];
                $user->password = $pwd;
                $user->role = 'ROLE_USER';

                //guardar el usuario
                $user->save();

                //mensaje de confirmacion por haberse guardado correctamente ele usuario
                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'El usuario se ha creado correctamente',
                    'user' => $user
                );
            }
        } else {
            $data = array(
                'status' => 'error',
                'code' => 404,
                'message' => 'Los datos enviados no son correctos'
            );
        }

        return response()->json($data, $data['code']);
    }

    public function login(Request $request) {

        $jwtAuth = new \JwtAuth();

        //recibir datos por post
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);

        //validar los adtos
        $validate = \Validator::make($params_array, [
                    'email' => 'required|email',
                    'password' => 'required'
        ]);

        if ($validate->fails()) {
            //validacion ha fallado
            $signup = array(
                'status' => 'error',
                'code' => 404,
                'message' => 'El usuario no se ha podido indentificar',
                'errors' => $validate->errors()
            );
        } else {

            //cifrar contrase;a
            $pwd = hash('sha256', $params->password);

            //devolver token o datos
            $signup = $jwtAuth->signup($params->email, $pwd);

            if (!empty($params->gettoken)) {
                $signup = $jwtAuth->signup($params->email, $pwd, true);
            }
        }

        return response()->json($signup, 200);

//        $email = 'juan@juan.com';
//        $password = 'juan';
//        $pwd = hash('sha256', $password);
        //var_dump($pwd); die();
        //return $jwtAuth->signup($email, $pwd);//devuelve elt oken
        //return response()->json($jwtAuth->signup($email, $pwd, true), 200); //devuelve los datos del usuario
    }

    public function update(Request $request) {

        //comprobar si el usuario esta identificado
        $token = $request->header('Authorization');
        $jwtAuth = new \JwtAuth();
        $checkToken = $jwtAuth->checkToken($token);

        // recoger los datos del post
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);


        if ($checkToken && !empty($params_array)) {

            //sacar usuario identificado
            $user = $jwtAuth->checkToken($token, true);


            //validar datos
            $validate = \Validator::make($params_array, [
                        'name' => 'required|alpha',
                        'surname' => 'required|alpha',
                        'email' => 'required|email|unique:users,' . $user->sub  //comprobar si el usuario existe ya (duplicado)
            ]);

            // quitar los campos que no quiero actualizar
            unset($params_array['id']);
            unset($params_array['role']);
            unset($params_array['password']);
            unset($params_array['created_at']);
            unset($params_array['remember_token']);

            //actualizar usuario en base de datos
            $user_update = User::where('id', $user->sub)->update($params_array);


            //devolver array con resultado
            $data = array(
                'code' => 200,
                'status' => 'success',
                'user' => $user,
                'changes' => $params_array
            );
        } else {
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'el usuario no esta identificado.'
            );
        }

        return response()->json($data, $data['code']);
    }

    public function upload(Request $request) {

        //recoger los datos de la peticion
        $image = $request->file('file0');

        //validacion de imagen
        $validate = \Validator::make($request->all(), [
                    'file0' => 'required|image|mimes:jpg,jpeg,png,gif'
        ]);

        //subir y guardar la imagen
        if (!$image || $validate->fails()) {
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'Error al subir imagen'
            );
        } else {
            $image_name = time() . $image->getClientOriginalName(); //nombre de la imagen que nunca se repite -ojo el punto es concatenar
            \Storage::disk('users')->put($image_name, \File::get($image)); //guarda archivos en el servidor

            $data = array(
                'code' => 200,
                'status' => 'success',
                'image' => $image_name
            );
        }

        return response()->json($data, $data['code']);
    }

    public function getImage($filename) {
        $isset = \Storage::disk('users')->exists($filename);
        if ($isset) {
            $file = \Storage::disk('users')->get($filename);
            return new Response($file, 200);
        } else {
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'Error, no existe la imagen'
            );
            return response()->json($data, $data['code']);
        }
    }

    public function detail($id) {
        $user = User::find($id);

        if (is_object($user)) {
            $data = array(
                'code' => 200,
                'status' => 'success',
                'user' => $user
            );
        } else {
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'Error, no existe el usuario'
            );
        }
        return response()->json($data, $data['code']);
    }

}
