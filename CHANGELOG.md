# Changelog

Todos los cambios importantes de este proyecto se registran en este archivo.
El formato sigue Keep a Changelog y el proyecto usa versionado semántico.

## [Unreleased]

## [1.0.0] - 2026-11-09
### Added
- CRUD de contactos: listar (paginado y con búsqueda), crear, ver perfil, actualizar (PUT y PATCH) y eliminar
- Borrado lógico de contactos (soft delete)
- ContactPolicy: un usuario solo ve, edita o elimina sus propios contactos (403 si es ajeno)
- Correo y teléfono de contacto únicos, incluidos los contactos eliminados
- Errores 403, 404 y 405 en JSON y en español
- Pruebas de cada endpoint de contactos

## [0.4.0] - 2026-11-09
### Added
- Actualizar el perfil con PUT y PATCH (PUT/PATCH /api/v1/profile)
- Correo y teléfono únicos entre usuarios, también al editar el perfil
- Correo y teléfono se normalizan antes de validar (mayúsculas, espacios y guiones)

## [0.3.0] - 2026-11-09
### Added
- Registro de usuarios con token (POST /api/v1/auth/register)
- Login con token de Sanctum (POST /api/v1/auth/login)
- Cierre de sesión que revoca el token (POST /api/v1/auth/logout)
- Perfil del usuario autenticado (GET /api/v1/profile)
- 401 en JSON con el enlace al login cuando no hay sesión
- Límite de 5 intentos de login por minuto
- Esquema Bearer en la documentación
### Changed
- El endpoint de salud pasa a un controlador invocable

## [0.2.0] - 2026-11-09
### Added
- Campos first_name, last_name y phone_number (único) en usuarios
- Tabla contacts con correo y teléfono únicos y borrado lógico (deleted_at)
- Modelos User y Contact con su relación
- Factories y seeder con un usuario demo y contactos de ejemplo
- Diagrama entidad-relación en docs/er.md

## [0.1.0] - 2026-11-09
### Added
- GET /api/v1/health
- Errores en JSON para rutas /api
