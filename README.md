# MyVet API (PHP puro)

API REST sin framework para la demo de MyVet. Conserva capas de controladores, modelos y repositorios. Los repositorios usan un almacenamiento JSON inicializado con pocos datos de prueba; al migrar a MySQL sólo se reemplazan los repositorios, sin afectar controladores ni la app móvil.

## Ejecutar

Requiere PHP 8.2 o superior. Desde la raíz:

```bash
php -S localhost:8000 -t public public/index.php
```

Probá `GET http://localhost:8000/api/health`.

### XAMPP

La carpeta pública debe ser el DocumentRoot idealmente: `C:\xampp\htdocs\ApiVet\public`.
Si dejás el proyecto completo dentro de `htdocs\ApiVet`, usá estas URLs:

- `http://localhost/ApiVet/public/`
- `http://localhost/ApiVet/public/api/health`

La API también reconoce automáticamente ese prefijo para que las rutas funcionen con ambas configuraciones.

Credenciales demo:

- Propietario: `owner@myvet.test` / `owner123`
- Veterinaria: `vet@myvet.test` / `vet123`

## Flujo de prueba

1. Iniciá sesión con `POST /api/auth/login` y guardá el `token`.
2. Enviá `Authorization: Bearer <token>` en los recursos protegidos.
3. Como veterinaria, usá `POST /api/veterinarian/invitations` para generar una invitación de un solo uso.
4. Como propietario, canjeala con `POST /api/veterinarians/redeem-invitation` y `{ "code": "INV-XXXXXX" }`.
5. Consultá disponibilidad: `GET /api/veterinarians/2/availability?date=2026-10-08`.
6. Creá una consulta mediante `POST /api/appointments` con `pet_id`, `veterinarian_id`, `appointment_date`, `appointment_time` y `reason`.

Los horarios van de 09:00 a 17:45, cada 15 minutos. Las consultas `pending` y `approved` bloquean la disponibilidad.

## Endpoints

- `POST /api/auth/login`, `GET /api/auth/me`
- `GET|POST /api/pets`
- `GET /api/veterinarians`, `POST /api/veterinarians/redeem-invitation`
- `GET|POST /api/veterinarian/invitations`, `GET /api/veterinarian/patients`
- `GET|POST /api/appointments`, `POST /api/appointments/{id}/approve|complete|cancel`
- `GET /api/veterinarians/{id}/availability?date=YYYY-MM-DD`

`storage/demo-data.json` se genera en el primer arranque y está ignorado por Git. Es útil para la demo local; un despliegue con varias instancias debe migrar los repositorios a una base de datos y aplicar transacciones para canjes y reservas.
