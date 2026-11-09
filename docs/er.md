# Modelo de datos de Contacts API

```mermaid
erDiagram
    USERS ||--o{ CONTACTS : "tiene (user_id)"
    USERS ||--o{ PERSONAL_ACCESS_TOKENS : "inicia sesión (tokenable)"

    USERS {
        bigint id PK
        string first_name "obligatorio"
        string last_name "opcional"
        string email UK "obligatorio"
        string password "hash bcrypt"
        string phone_number UK "opcional"
        timestamp created_at
        timestamp updated_at
    }

    CONTACTS {
        bigint id PK
        bigint user_id FK "dueño del contacto"
        string first_name "obligatorio"
        string last_name "opcional"
        string phone_number UK "obligatorio"
        string email UK "obligatorio"
        string address "obligatorio"
        string avatar_url "opcional"
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at "borrado lógico"
    }

    PERSONAL_ACCESS_TOKENS {
        bigint id PK
        string tokenable_type
        bigint tokenable_id
        text name "dispositivo"
        string token UK "hash SHA-256"
        timestamp last_used_at
    }
```

- Un usuario tiene muchos contactos; cada contacto pertenece a un solo usuario.
- `contacts.email` y `contacts.phone_number` son únicos en toda la tabla,
  incluidos los contactos eliminados (el borrado es lógico y se pueden restaurar).
- El nombre y la dirección sí se pueden repetir.
- Si se elimina un usuario (de verdad), sus contactos se van con él (`ON DELETE CASCADE`).
