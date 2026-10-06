<?php
declare(strict_types=1);

use MyVet\Controllers\AppointmentController;
use MyVet\Controllers\AuthController;
use MyVet\Controllers\PetController;
use MyVet\Controllers\VeterinarianController;
use MyVet\Http\Request;
use MyVet\Http\Response;
use MyVet\Repositories\AppointmentRepository;
use MyVet\Repositories\DemoStore;
use MyVet\Repositories\PetRepository;
use MyVet\Repositories\UserRepository;
use MyVet\Repositories\VeterinarianRepository;
use MyVet\Services\AuthService;

spl_autoload_register(function (string $class): void {
    $prefix = 'MyVet\\';
    if (str_starts_with($class, $prefix)) {
        $file = __DIR__ . '/../src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) require $file;
    }
});

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

$store = new DemoStore(__DIR__ . '/../storage/demo-data.json');
$users = new UserRepository($store);
$auth = new AuthService($users);
$pets = new PetRepository($store);
$veterinarians = new VeterinarianRepository($store);
$appointments = new AppointmentRepository($store);
$request = new Request();
$method = $request->method();
$path = rtrim($request->path(), '/') ?: '/';
$scriptDirectory = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
if ($scriptDirectory !== '' && $scriptDirectory !== '.') {
    if ($path === $scriptDirectory) {
        $path = '/';
    } elseif (str_starts_with($path, $scriptDirectory . '/')) {
        $path = substr($path, strlen($scriptDirectory));
    }
}

try {
    if ($method === 'GET' && $path === '/') Response::json(['name' => 'MyVet API', 'health' => '/api/health']);
    if ($method === 'GET' && $path === '/api/health') Response::json(['status' => 'ok', 'storage' => 'demo-json']);
    $authController = new AuthController($auth);
    $petController = new PetController($auth, $pets);
    $vetController = new VeterinarianController($auth, $veterinarians, $pets);
    $appointmentController = new AppointmentController($auth, $appointments, $pets, $veterinarians);
    if ($method === 'POST' && $path === '/api/auth/login') $authController->login($request);
    if ($method === 'GET' && $path === '/api/auth/me') $authController->me($request);
    if ($method === 'GET' && $path === '/api/pets') $petController->index($request);
    if ($method === 'POST' && $path === '/api/pets') $petController->create($request);
    if ($method === 'GET' && $path === '/api/veterinarians') $vetController->linked($request);
    if ($method === 'POST' && $path === '/api/veterinarians/redeem-invitation') $vetController->redeemInvitation($request);
    if ($method === 'POST' && $path === '/api/veterinarian/invitations') $vetController->createInvitation($request);
    if ($method === 'GET' && $path === '/api/veterinarian/invitations') $vetController->invitations($request);
    if ($method === 'GET' && $path === '/api/veterinarian/patients') $vetController->patients($request);
    if ($method === 'GET' && $path === '/api/appointments') $appointmentController->index($request);
    if ($method === 'POST' && $path === '/api/appointments') $appointmentController->create($request);
    if (preg_match('#^/api/appointments/(\d+)$#', $path, $matches) && $method === 'PUT') $appointmentController->reschedule($request, (int)$matches[1]);
    if (preg_match('#^/api/appointments/(\d+)/(approve|complete|cancel)$#', $path, $matches) && $method === 'POST') $appointmentController->changeStatus($request, (int)$matches[1], $matches[2]);
    if (preg_match('#^/api/appointments/(\d+)/extend$#', $path, $matches) && $method === 'POST') $appointmentController->extend($request, (int)$matches[1]);
    if (preg_match('#^/api/veterinarians/(\d+)/availability$#', $path, $matches) && $method === 'GET') $appointmentController->availability($request, (int)$matches[1]);
    Response::error('not_found', 'Endpoint no encontrado.', 404);
} catch (Throwable $exception) {
    Response::error('server_error', 'Ocurrió un error inesperado.', 500, ['exception' => $exception->getMessage()]);
}
