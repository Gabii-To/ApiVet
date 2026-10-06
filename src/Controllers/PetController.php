<?php
declare(strict_types=1);
namespace MyVet\Controllers;
use MyVet\Http\Request;
use MyVet\Http\Response;
use MyVet\Repositories\PetRepository;
use MyVet\Services\AuthService;
final class PetController {
    public function __construct(private AuthService $auth, private PetRepository $pets) {}
    public function index(Request $request): never { $user=$this->auth->requireRole($request,'owner'); Response::json(['data'=>$this->pets->forOwner($user->id)]); }
    public function create(Request $request): never { $user=$this->auth->requireRole($request,'owner'); $body=$request->body(); foreach(['name','species','birth_date'] as $field) if(empty($body[$field])) Response::error('validation_error',"$field es obligatorio.",422); Response::json(['data'=>$this->pets->create($user->id,$body)],201); }
}
