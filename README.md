# API RESTful de Gestión de Tareas (Pure PHP)

Esta API permite gestionar tareas personales con autenticación de usuarios. Construida con **PHP Nativo (sin frameworks)** y SQLite.

## Requisitos
- PHP 8.1+
- Composer
- SQLite3
- Docker y Docker Compose (Opcional, para ejecución en contenedores)

## Instalación y Configuración

1. **Clonar el repositorio.**
2. **Instalar dependencias de Composer:**
   ```bash
   composer install
   ```
3. **Configurar variables de entorno:**
   Copia el archivo el archivo .env adjunto al directorio raiz del proyecto.

### Opción A: Ejecución Local (PHP Nativo)

1. **Inicializar y Sembrar la Base de Datos:**
   ```bash
   php database/migrate.php
   php database/seed.php
   ```
   *Esto creará la estructura y un usuario de prueba: `test@example.com` / `password123`.*

2. **Iniciar el servidor local:**
   ```bash
   php -S localhost:8080 -t public
   ```

### Opción B: Ejecución con Docker

Si tienes Docker instalado y funcionando:

1. **Levantar el entorno:**
   ```bash
   docker-compose up -d
   ```
2. **Ejecutar migraciones dentro del contenedor:**
   ```bash
   docker-compose exec app php database/migrate.php
   docker-compose exec app php database/seed.php
   ```
   La API estará disponible en `http://localhost:8080`.

---

## Endpoints

### Autenticación
- `POST /auth/register`: Registrar un nuevo usuario.
  - Body: `{"name": "...", "email": "...", "password": "..."}`
- `POST /auth/login`: Obtener token JWT.
  - Body: `{"email": "...", "password": "..."}`
  - Retorna: `{"token": "...", "user": {...}}`

### Perfil de Usuario (Requiere header `Authorization: Bearer <token>`)
- `PUT /auth/profile`: Actualizar perfil (nombre, email o contraseña).
  - Body: `{"name": "...", "email": "...", "password": "..."}` (Todos opcionales)
- `DELETE /auth/profile`: Eliminar cuenta de usuario permanentemente.

### Tareas (Requiere header `Authorization: Bearer <token>`)
- `GET /tasks`: Listar tareas del usuario.
  - Query params (opcionales): 
    - `status`: Filtrar por estado.
    - `due_date`: Filtrar por fecha de vencimiento (`YYYY-MM-DD`).
    - `order`: `asc` o `desc`.
    - `page`: Número de página (Paginación).
    - `limit`: Cantidad de resultados por página (Paginación).
- `POST /tasks`: Crear una tarea.
  - Body: `{"title": "...", "description": "...", "status": "...", "due_date": "Y-m-d"}`
- `GET /tasks/{id}`: Obtener detalle de una tarea.
- `PUT /tasks/{id}`: Actualizar una tarea (título, descripción, estado o fecha).
- `DELETE /tasks/{id}`: Eliminar una tarea.

---

## Características Técnicas
- **Arquitectura**: Controladores, Servicios y Repositorios (Clean Architecture).
- **Core**: Router personalizado y Contenedor de dependencias manual (Sin Frameworks).
- **Seguridad**: JWT para protección de endpoints, `password_hash` para contraseñas y protección contra SQL Injection mediante sentencias preparadas nativas.
- **Paginación**: Implementada en los endpoints de listado para manejar grandes volúmenes de datos.
- **Auditoría**: Registro automático de cambios de estado en la tabla `audit_logs` (valor anterior y nuevo).
- **Infraestructura**: Incluye `Dockerfile` y `docker-compose.yml` para portabilidad.
- **Base de Datos**: Scripts de `migrate.php` y `seed.php` para gestión de esquema y datos iniciales.

## Pruebas (Unitarias e Integración)
El proyecto cuenta con una suite completa de pruebas utilizando **PHPUnit**:

Ejecutar todos los tests:
```bash
./vendor/bin/phpunit
```

- **Unitarias**: Prueban la lógica de negocio aislada en `tests/Unit`.
- **Integración**: Prueban el flujo completo (DB + Servicios) en `tests/Integration`.
