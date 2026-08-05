# Shared contracts

The versioned canonical form JSON Schema and valid/invalid fixtures will be added here in Milestone 1 after the schema, identifier, versioning, and validation decisions are approved.

Planned layout:

```text
contracts/
|-- form-schema.v1.json
`-- examples/
```

Laravel and FastAPI will load the same contract artifact rather than maintaining separate schema copies.
