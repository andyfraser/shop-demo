<?php

return new class {
    public function up(string $driver): string {
        if ($driver === 'mysql') {
            return "
                CREATE INDEX idx_remember_tokens_expires_at ON remember_tokens(expires_at);
            ";
        }
        return "
            CREATE INDEX IF NOT EXISTS idx_remember_tokens_expires_at ON remember_tokens(expires_at);
        ";
    }

    public function down(string $driver): string {
        if ($driver === 'mysql') {
            return "
                DROP INDEX idx_remember_tokens_expires_at ON remember_tokens;
            ";
        }
        return "
            DROP INDEX IF EXISTS idx_remember_tokens_expires_at;
        ";
    }
};
