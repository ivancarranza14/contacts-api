# Contacts API

[![tests](https://github.com/ivancarranza14/contacts-api/actions/workflows/tests.yml/badge.svg)](https://github.com/ivancarranza14/contacts-api/actions/workflows/tests.yml)
![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4)
![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20)
![Licencia](https://img.shields.io/badge/licencia-MIT-blue)

API REST para gestionar una agenda de contactos personal: cada usuario inicia sesión
con un token y administra solo sus propios contactos (listar, crear, ver su perfil,
actualizar y eliminar con borrado lógico).
Proyecto del programa ADSO (SENA), ficha 3174404. Entrega: lunes 23 de noviembre de 2026.

- Versión actual: v1.0.0

## Contenido

- [Tecnologías](#tecnologías)
- [Requisitos](#requisitos)
- [Instalación](#instalación)
- [Modelo de datos](#modelo-de-datos)
- [Datos de prueba](#datos-de-prueba)
- [Variables de entorno](#variables-de-entorno)
- [Pruebas](#pruebas)
- [Endpoints](#endpoints)
- [Autenticación](#autenticación)
- [Restricciones](#restricciones)
- [Respuestas de error](#respuestas-de-error)
- [Flujo de trabajo](#flujo-de-trabajo)
- [Licencia](#licencia)

## Tecnologías

PHP 8.3+, Laravel 13, MySQL 8, Laravel Sanctum (tokens Bearer), Pest, Pint y Scramble.

## Requisitos

PHP 8.3 o superior (con pdo_mysql, mbstring, openssl, fileinfo y curl), Composer 2, MySQL 8 y Git.

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
php artisan migrate --seed
php artisan serve
```

## Modelo de datos

Diagrama entidad-relación: [docs/er.md](docs/er.md)

| Tabla    | Campos                                                                                         |
|----------|------------------------------------------------------------------------------------------------|
| users    | id, first_name, last_name?, email (único), password (hash), phone_number? (único), timestamps  |
| contacts | id, user_id (FK), first_name, last_name?, phone_number (único), email (único), address, avatar_url?, timestamps, deleted_at |

`?` = opcional.

## Datos de prueba

```bash
php artisan migrate:fresh --seed
```

Crea el usuario demo (demo@contacts.test / password) con 10 contactos y
2 usuarios más con 5 contactos cada uno. Solo para desarrollo local.

## Variables de entorno

| Variable    | Ejemplo         | Descripción                    |
|-------------|-----------------|--------------------------------|
| APP_NAME    | "Contacts API"  | Nombre que responde /health    |
| DB_DATABASE | db_contacts_api | Base de datos MySQL            |
| DB_USERNAME | root            | Usuario de MySQL               |
| DB_PASSWORD | (vacío)         | Contraseña de MySQL            |
| DB_PREFIX   | a07_            | Prefijo de las tablas          |

## Pruebas

```bash
php artisan test
vendor/bin/pint --test
```

Cada endpoint tiene sus pruebas con Pest en `tests/Feature`. Las pruebas usan la base
`db_contacts_api_test` (ver `phpunit.xml`), así nunca tocan los datos de desarrollo.
GitHub Actions las ejecuta en cada push y en cada Pull Request.

| Archivo                     | Qué prueba                                             |
|-----------------------------|--------------------------------------------------------|
| HealthTest                  | GET /health y 404 en JSON                              |
| DataModelTest               | Hash, únicos, relación, borrado lógico y seeder        |
| AuthTest                    | Registro, login, logout, 401 y límite de intentos      |
| ProfileTest                 | Ver y actualizar el perfil (PUT y PATCH) y sus únicos  |
| Contacts/ContactIndexTest   | Listar, paginar, buscar y no ver ajenos ni eliminados  |
| Contacts/ContactStoreTest   | Crear, validar, únicos y nombre/dirección repetibles   |
| Contacts/ContactShowTest    | Perfil del contacto, 403 ajeno, 404 y 405              |
| Contacts/ContactUpdateTest  | PUT, PATCH, únicos, 403 ajeno y 404                    |
| Contacts/ContactDestroyTest | Soft delete, 403 ajeno y 404                           |

## Documentación de la API

Con el servidor local: http://127.0.0.1:8000/docs/api (Scramble, OpenAPI).

La colección de Insomnia está en `docs/insomnia/contacts-api.json`
(entorno `local`; el login guarda el token automáticamente).

## Endpoints

Todas las rutas van bajo `/api/v1`. Las marcadas con **Bearer** necesitan
`Authorization: Bearer {token}`.

| Método | Ruta                  | Descripción                                    | Auth   | Éxito |
|--------|-----------------------|------------------------------------------------|--------|-------|
| GET    | /api/v1/health        | Estado del servicio                            | No     | 200   |
| POST   | /api/v1/auth/register | Crea una cuenta y entrega un token             | No     | 201   |
| POST   | /api/v1/auth/login    | Inicia sesión y entrega un token               | No     | 200   |
| POST   | /api/v1/auth/logout   | Cierra sesión y revoca el token                | Bearer | 204   |
| GET    | /api/v1/profile       | Perfil del usuario autenticado                 | Bearer | 200   |
| PUT    | /api/v1/profile       | Reemplaza el perfil                            | Bearer | 200   |
| PATCH  | /api/v1/profile       | Modifica solo los campos enviados              | Bearer | 200   |
| GET    | /api/v1/contacts      | Lista tus contactos (`?per_page=`, `?search=`) | Bearer | 200   |
| POST   | /api/v1/contacts      | Crea un contacto                               | Bearer | 201   |
| GET    | /api/v1/contacts/{id} | Perfil completo de un contacto                 | Bearer | 200   |
| PUT    | /api/v1/contacts/{id} | Reemplaza un contacto                          | Bearer | 200   |
| PATCH  | /api/v1/contacts/{id} | Modifica solo los campos enviados              | Bearer | 200   |
| DELETE | /api/v1/contacts/{id} | Elimina con borrado lógico (soft delete)       | Bearer | 204   |

### Ejemplo: crear un contacto

```http
POST /api/v1/contacts
Authorization: Bearer 1|Vb3k...
Accept: application/json
Content-Type: application/json

{
  "first_name": "Ana",
  "last_name": "Pérez",
  "phone_number": "3101234567",
  "email": "ana.perez@correo.com",
  "address": "Calle 10 # 5-20, Bogotá",
  "avatar_url": "https://i.pravatar.cc/300?img=5"
}
```

Responde `201`:

```json
{
  "data": {
    "id": 21,
    "first_name": "Ana",
    "last_name": "Pérez",
    "full_name": "Ana Pérez",
    "phone_number": "3101234567",
    "email": "ana.perez@correo.com",
    "address": "Calle 10 # 5-20, Bogotá",
    "avatar_url": "https://i.pravatar.cc/300?img=5",
    "created_at": "2026-11-09T10:00:00+00:00",
    "updated_at": "2026-11-09T10:00:00+00:00"
  },
  "message": "Contacto creado correctamente."
}
```

## Autenticación

1. POST /api/v1/auth/login (o /auth/register) con email, password y device_name.
2. Guardar el token de la respuesta.
3. Enviar en cada petición protegida:
   `Authorization: Bearer {token}` y `Accept: application/json`.

Sin token (o con un token revocado) la API responde 401 con el enlace al login:

```json
{"message": "No has iniciado sesión. Inicia sesión para continuar.", "login_url": "http://127.0.0.1:8000/api/v1/auth/login"}
```

El login admite 5 intentos por minuto por correo e IP (al sexto: 429).

## Restricciones

| Requisito                                                   | Cómo se cumple                                                        |
|-------------------------------------------------------------|-----------------------------------------------------------------------|
| Errores de validación en JSON, también si un link no existe | `shouldRenderJsonWhen` y respuestas 404, 405 y 422 en JSON            |
| Sin sesión no se accede a los contactos (devolver al login) | Grupo `auth:sanctum`: 401 con `login_url`                             |
| Si el contacto es de otro usuario, no deja pasar            | `ContactPolicy`: 403 "Este contacto no te pertenece."                 |
| No repetir el correo de un contacto o usuario               | `unique` en la base y en la validación (también contactos eliminados) |
| No repetir el teléfono de un contacto o usuario             | `unique` en la base y en la validación; se ignoran espacios y guiones |
| Nombre y dirección sí se pueden repetir                     | Sin regla `unique` en esos campos                                     |
| Eliminar con soft delete                                    | `SoftDeletes` y columna `deleted_at`                                  |
| Cada endpoint con su test                                   | Pest en `tests/Feature` (ver tabla de pruebas)                        |

## Respuestas de error

| Código | Cuándo                                             | Ejemplo de `message`                                         |
|--------|----------------------------------------------------|--------------------------------------------------------------|
| 401    | Sin token o con un token revocado                  | "No has iniciado sesión. Inicia sesión para continuar."      |
| 403    | El contacto existe pero es de otro usuario         | "Este contacto no te pertenece."                             |
| 404    | La ruta o el contacto no existen (o fue eliminado) | "El contacto solicitado no existe."                          |
| 405    | La ruta existe, pero no con ese método             | "El método POST no está permitido en esta ruta."             |
| 422    | Datos inválidos o repetidos (con `errors` por campo) | "Ese correo ya está registrado en otro contacto."          |
| 429    | Sexto intento de login en un minuto                | "Demasiados intentos de inicio de sesión. Espera un minuto." |

## Flujo de trabajo

Git Flow (main, develop, feature/\*, release/\*, hotfix/\*),
Conventional Commits y versionado semántico.
Los cambios se registran en CHANGELOG.md.

| Versión | Contenido                               |
|---------|-----------------------------------------|
| v0.1.0  | Proyecto, /health, errores JSON y CI    |
| v0.2.0  | Modelo de datos: usuarios y contactos   |
| v0.3.0  | Autenticación con Sanctum               |
| v0.4.0  | Perfil de usuario                       |
| v1.0.0  | CRUD de contactos (entrega)             |

## Autor

Iván Carranza · aprendiz ADSO · github.com/ivancarranza14

## Licencia

Distribuido bajo la licencia MIT. Ver LICENSE.md.
