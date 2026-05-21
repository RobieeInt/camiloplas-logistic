# ERD - Camiloplas Logistic

```mermaid
erDiagram

    %% =====================
    %% AUTH & USER MANAGEMENT
    %% =====================

    users {
        bigint id PK
        string name
        string email
        timestamp email_verified_at
        string password
        string remember_token
        timestamps timestamps
    }

    sessions {
        string id PK
        bigint user_id FK
        string ip_address
        text user_agent
        longtext payload
        integer last_activity
    }

    password_reset_tokens {
        string email PK
        string token
        timestamp created_at
    }

    %% =====================
    %% ROLE & PERMISSION (Spatie)
    %% =====================

    permissions {
        bigint id PK
        string name
        string guard_name
        timestamps timestamps
    }

    roles {
        bigint id PK
        string name
        string guard_name
        timestamps timestamps
    }

    model_has_roles {
        bigint role_id FK
        string model_type
        bigint model_id
    }

    model_has_permissions {
        bigint permission_id FK
        string model_type
        bigint model_id
    }

    role_has_permissions {
        bigint permission_id FK
        bigint role_id FK
    }

    %% =====================
    %% MENU
    %% =====================

    menus {
        bigint id PK
        bigint parent_id FK
        string name
        string route
        string icon
        string permission_name
        integer sort_order
        boolean is_active
        timestamps timestamps
    }

    %% =====================
    %% LOGISTIC CORE
    %% =====================

    items {
        bigint id PK
        string item_code
        string item_name
        string uom
        timestamps timestamps
    }

    production_orders {
        bigint id PK
        string spk_number
        date production_date
        bigint item_id FK
        integer planned_qty
        string status
        timestamps timestamps
    }

    packing_units {
        bigint id PK
        bigint production_order_id FK
        bigint item_id FK
        string box_number
        string barcode
        string print_batch_id
        integer qty
        string uom
        timestamp printed_at
        bigint printed_by FK
        string status
        timestamps timestamps
    }

    fgw_racks {
        bigint id PK
        string rack_code
        string rack_name
        boolean is_active
        timestamps timestamps
    }

    trolleys {
        bigint id PK
        string trolley_code
        string barcode
        integer capacity
        string status
        bigint fgw_rack_id FK
        timestamp received_fgw_at
        bigint received_fgw_by FK
        timestamps timestamps
    }

    trolley_items {
        bigint id PK
        bigint trolley_id FK
        bigint packing_unit_id FK
        timestamp scanned_at
        bigint scanned_by FK
        timestamps timestamps
    }

    trolley_histories {
        bigint id PK
        bigint trolley_id FK
        string status
        text notes
        bigint created_by FK
        timestamps timestamps
    }

    delivery_orders {
        bigint id PK
        string so_number
        string do_number
        string customer_name
        string truck_number
        string status
        timestamp loaded_at
        bigint loaded_by FK
        integer do_print_count
        timestamp do_first_printed_at
        integer surat_jalan_print_count
        timestamp surat_jalan_first_printed_at
        timestamps timestamps
    }

    delivery_order_items {
        bigint id PK
        bigint delivery_order_id FK
        bigint item_id FK
        integer required_boxes
        integer loaded_boxes
        timestamps timestamps
    }

    loading_items {
        bigint id PK
        bigint delivery_order_id FK
        bigint packing_unit_id FK
        bigint trolley_id FK
        timestamp loaded_at
        bigint loaded_by FK
        timestamps timestamps
    }

    %% =====================
    %% RELATIONSHIPS
    %% =====================

    %% Auth
    users ||--o{ sessions : "has"
    users ||--o{ packing_units : "printed_by"
    users ||--o{ trolleys : "received_fgw_by"
    users ||--o{ trolley_items : "scanned_by"
    users ||--o{ trolley_histories : "created_by"
    users ||--o{ delivery_orders : "loaded_by"
    users ||--o{ loading_items : "loaded_by"

    %% Roles & Permissions
    roles ||--o{ model_has_roles : "assigned via"
    permissions ||--o{ model_has_permissions : "assigned via"
    roles ||--o{ role_has_permissions : "has"
    permissions ||--o{ role_has_permissions : "belongs to"

    %% Menu (self-referential)
    menus ||--o{ menus : "parent_id"

    %% Logistic Flow
    items ||--o{ production_orders : "diproduksi"
    items ||--o{ packing_units : "dikemas"
    items ||--o{ delivery_order_items : "dikirim"

    production_orders ||--o{ packing_units : "menghasilkan"

    fgw_racks ||--o{ trolleys : "menyimpan"

    trolleys ||--o{ trolley_items : "berisi"
    trolleys ||--o{ trolley_histories : "riwayat"
    trolleys ||--o{ loading_items : "digunakan"

    packing_units ||--o{ trolley_items : "masuk trolley"
    packing_units ||--o{ loading_items : "dimuat ke DO"

    delivery_orders ||--o{ delivery_order_items : "detail item"
    delivery_orders ||--o{ loading_items : "proses loading"
```

---

## Ringkasan Tabel

| Grup | Tabel | Keterangan |
|------|-------|------------|
| **Auth** | `users`, `sessions`, `password_reset_tokens` | Manajemen user & session |
| **Permission** | `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` | Spatie Laravel Permission |
| **Menu** | `menus` | Navigasi dinamis (self-referential tree) |
| **Master** | `items`, `fgw_racks` | Data master barang & rak gudang |
| **Produksi** | `production_orders`, `packing_units` | SPK & hasil packing |
| **Trolley** | `trolleys`, `trolley_items`, `trolley_histories` | Manajemen trolley & isinya |
| **Pengiriman** | `delivery_orders`, `delivery_order_items`, `loading_items` | DO, item DO, & proses loading |

---

## Alur Utama Logistik

```
items
  └─► production_orders (SPK)
          └─► packing_units (hasil packing per box/barcode)
                    └─► trolley_items ────► trolleys ────► fgw_racks
                    └─► loading_items ────► delivery_orders
                                                └─► delivery_order_items
```
