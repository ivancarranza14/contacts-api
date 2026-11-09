# Contacts API

[![tests](https://github.com/ivancarranza14/contacts-api/actions/workflows/tests.yml/badge.svg)](https://github.com/ivancarranza14/contacts-api/actions/workflows/tests.yml)
![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4)
![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20)
![Licencia](https://img.shields.io/badge/licencia-MIT-blue)

API REST para gestionar una agenda de contactos personal: cada usuario inicia sesión
con un token y administra solo sus propios contactos.
Proyecto del programa ADSO (SENA), ficha 3174404.

- Versión actual: v0.1.0

## Contenido

- [Tecnologías](#tecnologías)
- [Requisitos](#requisitos)
- [Instalación](#instalación)
- [Variables de entorno](#variables-de-entorno)
- [Pruebas](#pruebas)
- [Endpoints](#endpoints)
- [Flujo de trabajo](#flujo-de-trabajo)
- [Licencia](#licencia)

## Tecnologías

PHP 8.3+, Laravel 13, MySQL 8, Laravel Sanctum, Pest, Pint y Scramble.

## Requisitos

PHP 8.3 o superior, Composer 2, MySQL 8 y Git.

## Instalación

Crear las bases de datos en MySQL:

```sql
CREATE DATABASE db_contacts_api CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE db_contacts_api_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Luego:

```bash
git clone https://github.com/ivancarranza14/contacts-api.git
cd contacts-api
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

## Variables de entorno

| Variable    | Ejemplo         | Descripción                    |
|-------------|-----------------|--------------------------------|
| APP_NAME    | "Contacts API"  | Nombre que responde /health    |
| DB_DATABASE | db_contacts_api | Base de datos MySQL            |
| DB_PREFIX   | a07_            | Prefijo de las tablas          |

## Pruebas

```bash
php artisan test
vendor/bin/pint --test
```

Las pruebas usan la base `db_contacts_api_test` (ver `phpunit.xml`), así nunca
tocan los datos de desarrollo.

## Documentación de la API

Con el servidor local: http://127.0.0.1:8000/docs/api

La colección de Insomnia está en `docs/insomnia/contacts-api.json`.

## Endpoints

| Método | Ruta           | Descripción         | Auth |
|--------|----------------|---------------------|------|
| GET    | /api/v1/health | Estado del servicio | No   |

## Flujo de trabajo

Git Flow (main, develop, feature/\*, release/\*, hotfix/\*),
Conventional Commits y versionado semántico.
Los cambios se registran en CHANGELOG.md.

## Autor

Iván Carranza · aprendiz ADSO · github.com/ivancarranza14

## Licencia

Distribuido bajo la licencia MIT. Ver LICENSE.md.
