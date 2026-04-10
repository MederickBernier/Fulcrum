-- Migration tracking trable
-- Keeps record of which migrations have run
-- Simple, no dependencies

CREATE TABLE IF NOT EXISTS migrations(
    id          SERIAL PRIMARY KEY,
    filename    VARCHAR(255) NOT NULL UNIQUE,
    ran_at      TIMESTAMP NOT NULL DEFAULT NOW()
);