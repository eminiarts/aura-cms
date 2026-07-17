# The Resource Editor ships in 1.0 as a local-only development tool

The Resource Editor rewrites PHP resource classes on disk (regex-based codegen, `unlink()` on delete). Instead of fully hardening it for production use (AST-based rewriting, atomic writes, backups — roughly 1–2 weeks) or cutting the feature, we position it like scaffolding tools such as Blueprint: a development tool that only runs in the `local` environment. The real risk is not the file writing but accidental production exposure, so V1 hardens exactly that: the environment gate is enforced server-side (route/mount, not just navigation visibility) and covered by negative tests asserting it is unreachable in production.

Do not "fix" the editor by adding production-grade file-writing safety before there is a decision to make it a production feature.
