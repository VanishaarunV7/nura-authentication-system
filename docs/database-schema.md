# Database Schema

```mermaid
erDiagram
    USERS {
        INT id PK
        VARCHAR username UK
        VARCHAR email UK
        VARCHAR password_hash
        TIMESTAMP created_at
    }

    MONGODB_PROFILES {
        INT user_id
        STRING fullName
        INT age
        STRING bio
        STRING interests
        DATETIME updated_at
    }

    USERS ||--o| MONGODB_PROFILES : "user_id"
```

## MySQL

The `users` table stores authentication data:
- `username`
- `email`
- `password_hash`
- timestamps

## MongoDB

The `profiles` collection stores additional profile information:
- `user_id`
- `fullName`
- `age`
- `bio`
- `interests`
- `updated_at`

Redis stores the login session temporarily using a SHA-256-derived session key.
