# Shared form schema contract

`form-schema.v1.json` is the authoritative persisted-form contract shared by Laravel, React, and FastAPI. It uses JSON Schema Draft 2020-12 and rejects unknown properties at every object boundary.

The `examples/` directory contains reviewer-facing forms. Automated contract fixtures are split into `fixtures/valid/` and `fixtures/invalid/`.

JSON Schema validates document shape. Laravel and FastAPI semantic validators additionally enforce identifier and key uniqueness, reference integrity, field-type compatibility, regex safety, and configured nesting/count limits.
