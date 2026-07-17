# 1.0 is a fresh start — no automated upgrade path from 0.x

The 0.x series was never advertised and has no install base outside our own projects. We therefore ship 1.0 as a fresh baseline: no automated 0.2 → 1.0 upgrade machinery, no upgrade tests against 0.x data. A short `UPGRADING.md` documents the breaking changes (support matrix, renamed config where applicable) for our own internal migrations. If external 0.x installations surface, they stay on 0.x.
