<?php
declare(strict_types=1);
namespace MyVet\Services;
use MyVet\Http\Request;
use MyVet\Http\Response;
use MyVet\Models\User;
use MyVet\Repositories\UserRepository;
final class AuthService {
    public function __construct(private UserRepository $users) {}
    public function login(string $email, string $password): ?array { $record=$this->users->byEmail($email); if (!$record || !password_verify($password,$record['password_hash'])) return null; return ['token'=>$this->users->createToken((int)$record['id']), 'user'=>User::fromArray($record)->toArray()]; }
    public function user(Request $request): User { $token=$request->bearerToken(); $user=$token?$this->users->userFromToken($token):null; if(!$user) Response::error('unauthorized','Credenciales inválidas o faltantes.',401); return $user; }
    public function requireRole(Request $request, string $role): User { $user=$this->user($request); if($user->role!==$role) Response::error('forbidden','No tenés permisos para esta operación.',403); return $user; }
}
