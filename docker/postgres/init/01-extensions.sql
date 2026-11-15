-- Выполняется только при инициализации пустого volume postgres_data.
-- Для уже существующего volume: docker compose down -v (данные будут потеряны)
-- или CREATE EXTENSION вручную.

-- Расширение в основной базе (POSTGRES_DB).
CREATE EXTENSION IF NOT EXISTS vector;

-- Базы, созданные позже (например app_test для Symfony test env),
-- получат расширение автоматически из template1.
\connect template1
CREATE EXTENSION IF NOT EXISTS vector;