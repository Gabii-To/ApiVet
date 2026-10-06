<?php
declare(strict_types=1);
namespace MyVet\Controllers;
use MyVet\Http\Request;
use MyVet\Http\Response;
use MyVet\Services\AuthService;
final class AuthController {
    public function __construct(private AuthService $auth) {}
    public function login(Request $request): never { $body=$request->body(); $result=$this->auth->login((string)($body['email']??''),(string)($body['password']??'')); if(!$result) Response::error('invalid_credentials','Email o contraseña incorrectos.',401); Response::json($result); }
    public function me(Request $request): never { Response::json(['user'=>$this->auth->user($request)->toArray()]); }
}
