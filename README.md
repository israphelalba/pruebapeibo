# API RESTful de Gestión de Tareas (Pure PHP)

Esta API permite gestionar tareas personales con autenticación de usuarios. Construida con **PHP Nativo (sin frameworks)** y SQLite.

## Requisitos
- PHP 8.1+
- Composer
- SQLite3

## Instalación

1. Clonar el repositorio.
2. Instalar dependencias:
   ```bash
   composer install
   ```
3. Inicializar la base de datos (si no existe `database/database.sqlite`):
   ```bash
   sqlite3 database/database.sqlite < database/init.sql
   ```
4. Configurar el archivo `.env` con una clave secreta para JWT.
5. Iniciar el servidor local:
   ```bash
   php -S localhost:8080 -t public
   ```

## Endpoints

### Autenticación
- `POST /auth/register`: Registrar un nuevo usuario.
  - Body: `{"name": "...", "email": "...", "password": "..."}`
- `POST /auth/login`: Obtener token JWT.
  - Body: `{"email": "...", "password": "..."}`
  - Retorna: `{"token": "...", "user": {...}}`

### Tareas (Eperan header `Authorization: Bearer <token>`)
- `GET /tasks`: Listar tareas del usuario.
  - Query params (opcionales): `status`, `due_date`, `order` (asc/desc).
- `POST /tasks`: Crear una tarea.
  - Body: `{"title": "...", "description": "...", "status": "...", "due_date": "Y-m-d"}`
- `GET /tasks/{id}`: Obtener detalle de una tarea.
- `PUT /tasks/{id}`: Actualizar una tarea.
- `DELETE /tasks/{id}`: Eliminar una tarea.

## Características Técnicas
- **Arquitectura**: Controladores, Servicios y Repositorios.
- **Inyección de Dependencias**: Uso de PHP-DI para desacoplamiento.
- **Seguridad**: JWT para protección de endpoints y `password_hash` para contraseñas.
- **Validación**: Validaciones de campos obligatorios y formatos en la capa de servicio.
- **Auditoría**: Registro automático de cambios de estado en la tabla `audit_logs`.
- **Clean Code**: Seguimiento de principios SOLID y nombres claros en español.

## Pruebas
Ejecutar el set de pruebas unitarias:
```bash
./vendor/bin/phpunit
```
