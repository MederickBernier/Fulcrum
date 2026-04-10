-- Posts Table

CREATE TABLE IF NOT EXISTS posts(
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    title       VARCHAR(255) NOT NULL,
    slug        VARCHAR(255) NOT NULL UNIQUE,
    body        TEXT,
    status      VARCHAR(20) NOT NULL DEFAULT 'draft',
    created_at  TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS posts_slug_idx   ON posts(slug);
CREATE INDEX IF NOT EXISTS posts_status_idx ON posts(status);