CREATE TABLE IF NOT EXISTS images (
    id              SERIAL PRIMARY KEY,
    filename        TEXT,
    original_name   TEXT,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);