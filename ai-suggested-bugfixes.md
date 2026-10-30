# PHP `sentience/database` v1: verified bug report

Repo: `/Users/koen/Documents/Github/Sentience-Framework/database`, branch `v1` (HEAD 9cc4167, checked with `branch --show-current`). Paths are relative to that repo root. No tests or README exist on v1.

| # | Title | File:line | Verdict |
|---|-------|-----------|---------|
| A1 | PostgreSQL `escapeString` uses backslash escapes | src/Dialects/SQLDialect.php:1296-1306, PgSQLDialect.php:18-28 | Confirmed |
---
