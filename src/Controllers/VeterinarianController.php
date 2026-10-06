<?php
declare(strict_types=1);
namespace MyVet\Controllers;
use MyVet\Http\Request;
use MyVet\Http\Response;
use MyVet\Repositories\PetRepository;
use MyVet\Repositories\VeterinarianRepository;
use MyVet\Services\AuthService;
final class VeterinarianController {
    public function __construct(private AuthService $auth, private VeterinarianRepository $vets, private PetRepository $pets) {}
    public function linked(Request $request): never { $user=$this->auth->requireRole($request,'owner'); Response::json(['data'=>$this->vets->linkedTo($user->id)]); }
    public function createInvitation(Request $request): never { $user=$this->auth->requireRole($request,'veterinarian'); Response::json(['data'=>$this->vets->createInvitation($user->id)],201); }
    public function invitations(Request $request): never { $user=$this->auth->requireRole($request,'veterinarian'); Response::json(['data'=>$this->vets->invitationsFor($user->id)]); }
    public function redeemInvitation(Request $request): never { $user=$this->auth->requireRole($request,'owner'); $code=(string)($request->body()['code']??''); if($code==='') Response::error('validation_error','El código es obligatorio.',422); $invitation=$this->vets->redeem($user->id,$code); if(!$invitation) Response::error('invalid_invitation','El código no existe, ya fue usado o el veterinario ya está vinculado.',422); Response::json(['data'=>$invitation]); }
    public function patients(Request $request): never { $user=$this->auth->requireRole($request,'veterinarian'); Response::json(['data'=>$this->pets->patientsForVeterinarian($user->id)]); }
}
