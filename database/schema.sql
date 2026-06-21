-- RGE Hotel & Restaurant Management System — SQLite schema
-- Run via scripts/migrate.php

PRAGMA foreign_keys = ON;

-- ----------------------------------------------------------------------------
-- RBAC: roles, permissions, users
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS roles (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    slug        TEXT NOT NULL UNIQUE,
    name        TEXT NOT NULL,
    description TEXT,
    created_at  TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS permissions (
    id      INTEGER PRIMARY KEY AUTOINCREMENT,
    slug    TEXT NOT NULL UNIQUE,
    name    TEXT NOT NULL,
    grp     TEXT NOT NULL DEFAULT 'general'
);

CREATE TABLE IF NOT EXISTS role_permissions (
    role_id       INTEGER NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
    permission_id INTEGER NOT NULL REFERENCES permissions(id) ON DELETE CASCADE,
    PRIMARY KEY (role_id, permission_id)
);

CREATE TABLE IF NOT EXISTS users (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    role_id       INTEGER NOT NULL REFERENCES roles(id),
    name          TEXT NOT NULL,
    email         TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    phone         TEXT,
    position      TEXT,
    department    TEXT,
    is_active     INTEGER NOT NULL DEFAULT 1,
    last_login_at TEXT,
    created_at    TEXT DEFAULT (datetime('now')),
    updated_at    TEXT DEFAULT (datetime('now'))
);

-- ----------------------------------------------------------------------------
-- Accommodations
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS amenities (
    id        INTEGER PRIMARY KEY AUTOINCREMENT,
    slug      TEXT NOT NULL UNIQUE,
    name      TEXT NOT NULL,
    icon      TEXT,                  -- icon key (lucide sprite id)
    category  TEXT DEFAULT 'general',
    created_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS room_types (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    slug            TEXT NOT NULL UNIQUE,
    name            TEXT NOT NULL,
    summary         TEXT,
    description     TEXT,
    max_occupancy   INTEGER NOT NULL DEFAULT 2,
    adults          INTEGER NOT NULL DEFAULT 2,
    children        INTEGER NOT NULL DEFAULT 0,
    beds            TEXT,
    size_sqm        INTEGER,
    base_price      REAL NOT NULL DEFAULT 0,     -- nightly, PHP
    weekend_price   REAL,
    total_units     INTEGER NOT NULL DEFAULT 1,
    view            TEXT,
    sort_order      INTEGER NOT NULL DEFAULT 0,
    is_published    INTEGER NOT NULL DEFAULT 1,
    is_featured     INTEGER NOT NULL DEFAULT 0,
    meta_title       TEXT,
    meta_description TEXT,
    created_at      TEXT DEFAULT (datetime('now')),
    updated_at      TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS room_type_amenities (
    room_type_id INTEGER NOT NULL REFERENCES room_types(id) ON DELETE CASCADE,
    amenity_id   INTEGER NOT NULL REFERENCES amenities(id) ON DELETE CASCADE,
    PRIMARY KEY (room_type_id, amenity_id)
);

CREATE TABLE IF NOT EXISTS room_photos (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    room_type_id INTEGER NOT NULL REFERENCES room_types(id) ON DELETE CASCADE,
    filename     TEXT NOT NULL,       -- relative to /assets/img/rooms/
    alt          TEXT,
    is_cover     INTEGER NOT NULL DEFAULT 0,
    sort_order   INTEGER NOT NULL DEFAULT 0
);

-- Physical inventory units (for housekeeping / front desk).
CREATE TABLE IF NOT EXISTS rooms (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    room_type_id INTEGER NOT NULL REFERENCES room_types(id) ON DELETE CASCADE,
    code         TEXT NOT NULL UNIQUE,
    floor        TEXT,
    status       TEXT NOT NULL DEFAULT 'available',  -- available|occupied|cleaning|maintenance
    housekeeping TEXT NOT NULL DEFAULT 'clean',      -- clean|dirty|inspected
    notes        TEXT,
    created_at   TEXT DEFAULT (datetime('now'))
);

-- Seasonal / date-range rate overrides (optional; falls back to room_types.base_price).
CREATE TABLE IF NOT EXISTS room_rates (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    room_type_id INTEGER NOT NULL REFERENCES room_types(id) ON DELETE CASCADE,
    label        TEXT,
    start_date   TEXT NOT NULL,
    end_date     TEXT NOT NULL,
    price        REAL NOT NULL
);

-- ----------------------------------------------------------------------------
-- Bookings & payments
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bookings (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    reference       TEXT NOT NULL UNIQUE,
    room_type_id    INTEGER NOT NULL REFERENCES room_types(id),
    check_in        TEXT NOT NULL,
    check_out       TEXT NOT NULL,
    nights          INTEGER NOT NULL,
    rooms_count     INTEGER NOT NULL DEFAULT 1,
    adults          INTEGER NOT NULL DEFAULT 1,
    children        INTEGER NOT NULL DEFAULT 0,
    guest_name      TEXT NOT NULL,
    guest_email     TEXT NOT NULL,
    guest_phone     TEXT,
    guest_country   TEXT,
    special_requests TEXT,
    package_id      INTEGER REFERENCES packages(id),
    offer_code      TEXT,
    subtotal        REAL NOT NULL DEFAULT 0,
    discount        REAL NOT NULL DEFAULT 0,
    total           REAL NOT NULL DEFAULT 0,
    currency        TEXT NOT NULL DEFAULT 'PHP',
    status          TEXT NOT NULL DEFAULT 'pending',   -- pending|confirmed|checked_in|checked_out|cancelled
    payment_status  TEXT NOT NULL DEFAULT 'unpaid',    -- unpaid|paid|partial|refunded|failed
    source          TEXT DEFAULT 'website',
    created_at      TEXT DEFAULT (datetime('now')),
    updated_at      TEXT DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_bookings_dates ON bookings(room_type_id, check_in, check_out);
CREATE INDEX IF NOT EXISTS idx_bookings_status ON bookings(status);

CREATE TABLE IF NOT EXISTS booking_rooms (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    booking_id INTEGER NOT NULL REFERENCES bookings(id) ON DELETE CASCADE,
    room_id    INTEGER REFERENCES rooms(id)
);

CREATE TABLE IF NOT EXISTS payments (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    booking_id  INTEGER NOT NULL REFERENCES bookings(id) ON DELETE CASCADE,
    provider    TEXT NOT NULL,             -- xendit|cash|bank
    method      TEXT,                       -- card|ewallet|gcash|cash|bank|...
    amount      REAL NOT NULL,
    currency    TEXT NOT NULL DEFAULT 'PHP',
    status      TEXT NOT NULL DEFAULT 'pending',  -- pending|paid|failed|expired|refunded
    external_id TEXT,                        -- provider invoice/order id
    external_ref TEXT,                       -- checkout URL / capture id
    payload     TEXT,                        -- raw JSON from provider
    created_at  TEXT DEFAULT (datetime('now')),
    updated_at  TEXT DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_payments_external ON payments(external_id);

-- In-house folio: incidental charges posted to a booking (room service, minibar,
-- laundry, spa, amenity usage, transfers, etc.). Settled with the room balance
-- online (Xendit) or as a manual cash entry at the front desk.
CREATE TABLE IF NOT EXISTS room_charges (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    booking_id   INTEGER NOT NULL REFERENCES bookings(id) ON DELETE CASCADE,
    category     TEXT NOT NULL DEFAULT 'other',   -- room_service|food_beverage|minibar|laundry|spa|amenity|transfer|other
    description  TEXT NOT NULL,
    quantity     REAL NOT NULL DEFAULT 1,
    unit_price   REAL NOT NULL DEFAULT 0,
    amount       REAL NOT NULL DEFAULT 0,         -- quantity * unit_price
    status       TEXT NOT NULL DEFAULT 'unpaid',  -- unpaid|paid|void
    charged_at   TEXT NOT NULL DEFAULT (date('now')),
    notes        TEXT,
    recorded_by  INTEGER REFERENCES users(id),
    created_at   TEXT DEFAULT (datetime('now')),
    updated_at   TEXT DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_room_charges_booking ON room_charges(booking_id, status);

-- ----------------------------------------------------------------------------
-- Services, packages, offers
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS services (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    slug         TEXT NOT NULL UNIQUE,
    name         TEXT NOT NULL,
    category     TEXT NOT NULL DEFAULT 'tour',  -- tour|island_hopping|watersport|diving|car|transfer|spa|other
    summary      TEXT,
    description  TEXT,
    price        REAL,
    price_unit   TEXT DEFAULT 'per person',
    duration     TEXT,
    image        TEXT,
    highlights   TEXT,                           -- newline-separated
    is_published INTEGER NOT NULL DEFAULT 1,
    is_featured  INTEGER NOT NULL DEFAULT 0,
    sort_order   INTEGER NOT NULL DEFAULT 0,
    meta_title       TEXT,
    meta_description TEXT,
    created_at   TEXT DEFAULT (datetime('now')),
    updated_at   TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS packages (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    slug           TEXT NOT NULL UNIQUE,
    name           TEXT NOT NULL,
    summary        TEXT,
    description    TEXT,
    price          REAL,
    original_price REAL,
    price_unit     TEXT DEFAULT 'per package',
    inclusions     TEXT,                          -- newline-separated
    image          TEXT,
    nights         INTEGER,
    pax            INTEGER,
    is_published   INTEGER NOT NULL DEFAULT 1,
    is_featured    INTEGER NOT NULL DEFAULT 0,
    sort_order     INTEGER NOT NULL DEFAULT 0,
    valid_from     TEXT,
    valid_to       TEXT,
    meta_title       TEXT,
    meta_description TEXT,
    created_at     TEXT DEFAULT (datetime('now')),
    updated_at     TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS package_room_types (
    package_id   INTEGER NOT NULL REFERENCES packages(id) ON DELETE CASCADE,
    room_type_id INTEGER NOT NULL REFERENCES room_types(id) ON DELETE CASCADE,
    PRIMARY KEY (package_id, room_type_id)
);

CREATE TABLE IF NOT EXISTS package_services (
    package_id INTEGER NOT NULL REFERENCES packages(id) ON DELETE CASCADE,
    service_id INTEGER NOT NULL REFERENCES services(id) ON DELETE CASCADE,
    PRIMARY KEY (package_id, service_id)
);

CREATE TABLE IF NOT EXISTS offers (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    slug          TEXT NOT NULL UNIQUE,
    title         TEXT NOT NULL,
    subtitle      TEXT,
    description   TEXT,
    discount_type TEXT DEFAULT 'percent',   -- percent|fixed
    discount_value REAL DEFAULT 0,
    code          TEXT,
    image         TEXT,
    starts_at     TEXT,
    ends_at       TEXT,
    is_published  INTEGER NOT NULL DEFAULT 1,
    is_featured   INTEGER NOT NULL DEFAULT 0,
    sort_order    INTEGER NOT NULL DEFAULT 0,
    created_at    TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS offer_room_types (
    offer_id     INTEGER NOT NULL REFERENCES offers(id) ON DELETE CASCADE,
    room_type_id INTEGER NOT NULL REFERENCES room_types(id) ON DELETE CASCADE,
    PRIMARY KEY (offer_id, room_type_id)
);

-- ----------------------------------------------------------------------------
-- Reviews (polymorphic: hotel or room_type)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS reviews (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    subject_type    TEXT NOT NULL DEFAULT 'hotel',  -- hotel|room_type
    subject_id      INTEGER,
    author_name     TEXT NOT NULL,
    author_country  TEXT,
    rating          INTEGER NOT NULL,               -- 1..5
    title           TEXT,
    body            TEXT,
    stay_date       TEXT,
    is_approved     INTEGER NOT NULL DEFAULT 0,
    created_at      TEXT DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_reviews_subject ON reviews(subject_type, subject_id, is_approved);

-- ----------------------------------------------------------------------------
-- Restaurant (kept unpublished on live)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS menu_categories (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    slug         TEXT NOT NULL UNIQUE,
    name         TEXT NOT NULL,
    description  TEXT,
    sort_order   INTEGER NOT NULL DEFAULT 0,
    is_published INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS menu_items (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id  INTEGER NOT NULL REFERENCES menu_categories(id) ON DELETE CASCADE,
    name         TEXT NOT NULL,
    description  TEXT,
    price        REAL,
    image        TEXT,
    tags         TEXT,
    is_available INTEGER NOT NULL DEFAULT 1,
    is_featured  INTEGER NOT NULL DEFAULT 0,
    sort_order   INTEGER NOT NULL DEFAULT 0
);

-- ----------------------------------------------------------------------------
-- CMS / settings / contact / subscribers
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pages (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    slug         TEXT NOT NULL UNIQUE,
    title        TEXT NOT NULL,
    body         TEXT,
    meta_title       TEXT,
    meta_description TEXT,
    is_published INTEGER NOT NULL DEFAULT 1,
    updated_at   TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS settings (
    key        TEXT PRIMARY KEY,
    value      TEXT,
    type       TEXT DEFAULT 'string',
    grp        TEXT DEFAULT 'general'
);

CREATE TABLE IF NOT EXISTS contact_messages (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    name       TEXT NOT NULL,
    email      TEXT NOT NULL,
    phone      TEXT,
    subject    TEXT,
    message    TEXT NOT NULL,
    is_read    INTEGER NOT NULL DEFAULT 0,
    created_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS subscribers (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    email      TEXT NOT NULL UNIQUE,
    source     TEXT DEFAULT 'website',
    created_at TEXT DEFAULT (datetime('now'))
);

-- ----------------------------------------------------------------------------
-- Accounting: expenses, other income, refunds
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS expense_categories (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    slug       TEXT NOT NULL UNIQUE,
    name       TEXT NOT NULL,
    created_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS expenses (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id    INTEGER REFERENCES expense_categories(id),
    description    TEXT NOT NULL,
    vendor         TEXT,
    amount         REAL NOT NULL DEFAULT 0,
    expense_date   TEXT NOT NULL,
    payment_method TEXT,                       -- cash|bank|gcash|card|other
    reference      TEXT,
    notes          TEXT,
    recorded_by    INTEGER REFERENCES users(id),
    created_at     TEXT DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_expenses_date ON expenses(expense_date);

CREATE TABLE IF NOT EXISTS other_income (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    source       TEXT NOT NULL,                 -- restaurant|service|other
    description  TEXT,
    amount       REAL NOT NULL DEFAULT 0,
    income_date  TEXT NOT NULL,
    method       TEXT,
    notes        TEXT,
    recorded_by  INTEGER REFERENCES users(id),
    created_at   TEXT DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_other_income_date ON other_income(income_date);

CREATE TABLE IF NOT EXISTS refunds (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    booking_id   INTEGER REFERENCES bookings(id),
    payment_id   INTEGER REFERENCES payments(id),
    amount       REAL NOT NULL DEFAULT 0,
    reason       TEXT,
    method       TEXT,
    refunded_by  INTEGER REFERENCES users(id),
    created_at   TEXT DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_refunds_date ON refunds(created_at);
