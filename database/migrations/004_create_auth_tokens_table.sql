-- Auth tokens
-- Magic link / session tokens
-- No passwords - token sent to email, clicked, session started

CREATE TABLE IF NOT EXISTS auth_tokens(
    id              UUID            PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id         UUID            NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token           VARCHAR(64)     NOT NULL UNIQUE,
    expires_at      TIMESTAMP       NOT NULL,
    used_at         TIMESTAMP,
    created_at      TIMESTAMP       NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS auth_tokens_token_idx    ON auth_tokens(token);
CREATE INDEX IF NOT EXISTS auth_tokens_user_idx     ON auth_tokens(user_id);