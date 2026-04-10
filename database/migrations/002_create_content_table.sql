-- Content Table
-- Generic Content Model
-- type column distinguishes posts, pages, etc.

CREATE TABLE IF NOT EXISTS content (
    id          UUID         PRIMARY KEY DEFAULT gen_random_uuid(),
    type        VARCHAR(50)  NOT NULL DEFAULT 'post',
    title       VARCHAR(255) NOT NULL,
    slug        VARCHAR(255) NOT NULL UNIQUE,
    body        TEXT,
    status      VARCHAR(20)  NOT NULL DEFAULT 'draft',
    created_at  TIMESTAMP    NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMP    NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS content_type_idx    ON content (type);
CREATE INDEX IF NOT EXISTS content_slug_idx    ON content (slug);
CREATE INDEX IF NOT EXISTS content_status_idx  ON content (status);