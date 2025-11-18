# Diagrama Relacional — Sistema de Inventario Médico

Fecha: 23/10/2025

Este diagrama refleja las tablas y relaciones actuales según las migraciones del proyecto (MySQL/XAMPP). Incluye trazabilidad por lote en movimientos (inventario_id) y áreas de salida/entrada.

```mermaid
erDiagram
    CATEGORIAS {
        BIGINT id PK
        VARCHAR nombre UNIQUE
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }
    SUBCATEGORIAS {
        BIGINT id PK
        VARCHAR nombre
        BIGINT categoria_id FK
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }
    PROVEEDORES {
        BIGINT id PK
        VARCHAR nombre
        VARCHAR contacto
        VARCHAR telefono
        VARCHAR direccion
        VARCHAR email
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }
    PRODUCTOS {
        BIGINT id PK
        VARCHAR nombre
        VARCHAR codigo UNIQUE
        TEXT descripcion
        BIGINT categoria_id FK
        BIGINT subcategoria_id FK
        VARCHAR presentacion
        VARCHAR unidad_medida
        VARCHAR categoria_inventario
        INT stock
        INT stock_minimo
        BIGINT proveedor_id
        DATE fecha_ingreso
        DATE fecha_vencimiento
        BIGINT created_by
        BIGINT updated_by
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }
    INVENTARIOS {
        BIGINT id PK
        BIGINT producto_id FK
        VARCHAR lote
        INT cantidad
        DATE fecha_vencimiento
        INT stock_minimo
        VARCHAR estado
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }
    MOVIMIENTOS {
        BIGINT id PK
        BIGINT producto_id FK
        VARCHAR tipo
        VARCHAR salida
        BIGINT inventario_id FK NULL
        VARCHAR entrada
        INT cantidad
        VARCHAR motivo
        DATE fecha
        BIGINT usuario_id FK NULL
        TEXT observaciones
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }
    USERS {
        BIGINT id PK
        VARCHAR name
        VARCHAR email UNIQUE
        TIMESTAMP email_verified_at NULL
        VARCHAR password
        VARCHAR security_color_answer
        VARCHAR security_animal_answer
        INT login_attempts
        TIMESTAMP locked_until NULL
        VARCHAR role
        VARCHAR remember_token NULL
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }
    BITACORA {
        BIGINT id PK
        BIGINT user_id FK
        VARCHAR accion
        TEXT detalles
        TIMESTAMP fecha_hora
    }
    REPORTES {
        BIGINT id PK
        VARCHAR nombre
        VARCHAR tipo
        JSON parametros NULL
        BIGINT usuario_id FK NULL
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }

    %% Relaciones
    CATEGORIAS ||--o{ SUBCATEGORIAS : contiene
    CATEGORIAS ||--o{ PRODUCTOS : clasifica
    SUBCATEGORIAS ||--o{ PRODUCTOS : agrupa

    PROVEEDORES ||--o{ PRODUCTOS : provee

    PRODUCTOS ||--o{ INVENTARIOS : tiene
    PRODUCTOS ||--o{ MOVIMIENTOS : afecta
    INVENTARIOS ||--o{ MOVIMIENTOS : registra

    USERS ||--o{ MOVIMIENTOS : realiza
    USERS ||--o{ REPORTES : genera
    USERS ||--o{ BITACORA : registra
```

Notas:
- MOVIMIENTOS.inventario_id es NULLABLE para cubrir excepciones, pero se recomienda asociarlo siempre al lote para trazabilidad.
- PROVEEDORES no tiene FK forzada en migración de PRODUCTOS (comentada), pero el campo existe y la relación es lógica en la aplicación.
- Las áreas de salida controladas son: principal, acinf, agroalimentacion, odontologia.
- Índices recomendados: producto_id, inventario_id, usuario_id, fecha, tipo, categoria_id, subcategoria_id.
