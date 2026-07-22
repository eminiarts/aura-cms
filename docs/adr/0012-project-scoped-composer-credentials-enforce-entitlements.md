# Project-scoped Composer credentials enforce entitlements

A Customer Account assigns a purchase to a Licensed Project, and Aura Store issues that project a revocable Composer Credential covering its entitled packages across production, local, CI, preview, and staging environments. Aura Store enforces active entitlements only when Composer requests metadata or distributions; production URLs are descriptive rather than remotely verified, and installed plugins never validate licenses at runtime.
