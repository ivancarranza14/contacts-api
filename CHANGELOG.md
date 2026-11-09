# Changelog

Todos los cambios importantes de este proyecto se registran en este archivo.
El formato sigue Keep a Changelog y el proyecto usa versionado semántico.

## [Unreleased]

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
