# Tracker API Documentation Research

## Assignment

Research the best way to document the tracker app API so it is:

- simple
- easy to follow
- industry standard
- possible to keep on a single page
- easy to modify, add to, and maintain

## Recommendation

Use:

- `OpenAPI 3.1` as the source of truth
- one maintained `openapi.yaml` file to start
- `Redoc Community Edition` for the published single-page docs
- `Redocly CLI` to lint and build the static HTML
- optional `Swagger UI` later if the team wants interactive "try it out" docs

## Why This Is The Best Fit

This approach is the best match for the tracker app because it gives you:

- an industry-standard API contract
- a single-page output that is easy to host anywhere
- one file to edit for most changes
- a clean reading experience for developers
- a path to add tooling later without rewriting the docs

The key decision is to keep the source of truth in `OpenAPI`, not in handwritten prose and not only in framework annotations. That keeps the docs portable, machine-readable, and easier to validate.

## Short Answer

If the goal is "simple, standard, single-page, and easy to maintain", the best default setup is:

`OpenAPI 3.1 YAML` + `Redoc CE` + `Redocly CLI`

If the goal later shifts toward interactive testing inside the docs, keep the same `OpenAPI` file and add `Swagger UI` without changing the documentation model.

## Option Comparison

| Option | Pros | Cons | Verdict |
| --- | --- | --- | --- |
| Handwritten single-page docs | Fastest possible start, very flexible | Drifts from real API, no validation, no schema reuse, no standard tooling | Not recommended as the source of truth |
| OpenAPI + Swagger UI | Industry-standard, interactive, easy to host, familiar to many teams | Busier interface, less polished as a reading-first single page | Strong option if interactivity matters most |
| OpenAPI + Redoc CE | Clean single-page layout, static HTML output, strong readability, still based on OpenAPI | Open source edition is less interactive than Swagger UI | Best overall fit for this project |
| Framework-generated docs | Can be convenient if the stack already supports it well | Often couples docs to framework internals and can become harder to maintain cleanly | Use only if your stack already does this exceptionally well |

## Final Recommendation

Use a small docs-as-code workflow:

1. Maintain `openapi/openapi.yaml`
2. Lint it with `Redocly CLI`
3. Build one static HTML page with `Redoc`
4. Publish that page anywhere the team can access it

This keeps the process light while still following the standard approach used across modern APIs.

## Why Not Handwritten Docs

Handwritten docs look simple at first, but they usually fail over time because:

- endpoint changes are easy to forget
- request and response schemas become inconsistent
- errors and auth rules get documented differently across pages
- there is no automated validation
- you cannot easily reuse the spec for SDK generation, mocks, or testing later

For a tracker app, where endpoints, auth, payloads, and device events need consistency, that tradeoff is usually not worth it.

## Why Redoc Over Swagger UI For The Main Page

`Swagger UI` is excellent when the docs should double as a testing console.

`Redoc` is better when the top priority is:

- one clean page
- easy scanning
- strong navigation
- simple static output

For this assignment, those priorities matter more than built-in interactivity, so `Redoc` is the better primary renderer.

## Recommended File Layout

Start small:

```text
tracker-app/
  openapi/
    openapi.yaml
  redocly.yaml
  docs/
    api/
      index.html
```

Do not split the spec into many files on day one. Start with one `openapi.yaml`. Split it later only if the API becomes large enough that one file becomes hard to manage.

## Recommended Update Workflow

For each API change:

1. Update `openapi/openapi.yaml`
2. Add or update request and response examples
3. Run linting
4. Build the docs page
5. Review the rendered page before merging

Example commands:

```bash
npm install -D @redocly/cli
redocly lint openapi/openapi.yaml
redocly build-docs openapi/openapi.yaml -o docs/api/index.html
```

If you want the easiest possible maintenance path, add package scripts later:

```json
{
  "scripts": {
    "docs:lint": "redocly lint openapi/openapi.yaml",
    "docs:build": "redocly build-docs openapi/openapi.yaml -o docs/api/index.html"
  }
}
```

## Documentation Rules To Keep It Maintainable

Every endpoint should include:

- `summary`
- `description` when needed
- `tags`
- auth requirements
- parameters
- request body schema if applicable
- success response schema
- error response schema
- at least one realistic example

Keep the writing style consistent:

- short summaries
- plain language
- same order for each endpoint
- same error format across the API

## Best Practices For A Tracker App

For this kind of API, the docs should make these easy to find:

- authentication
- device identifiers
- tracker status endpoints
- telemetry/event payloads
- pagination rules
- timestamps and timezone handling
- common error responses

If those areas are inconsistent, the docs will feel harder to use than the API itself.

## Version Choice Note

As of `April 5, 2026`, the OpenAPI Initiative lists `3.2.0` as the latest specification release. However, the renderer and tooling sources reviewed here explicitly call out `OpenAPI 3.1` support today, and Redocly's `build-docs` documentation says `3.2` support is still coming soon.

Because of that, the safest practical choice right now is:

- write the docs in `OpenAPI 3.1`
- use tooling that already supports it well
- revisit `3.2` later when the renderer/tooling path is fully settled

This is an implementation recommendation based on current tool compatibility, not a claim that `3.2` is unimportant.

## Practical Conclusion

If you want the simplest setup that still feels professional and standard, do this:

- keep one `OpenAPI 3.1` YAML file in the repo
- build one `Redoc` HTML page from it
- lint it on every change
- add `Swagger UI` only if the team later wants interactive requests in the docs

That gives you a single-page documentation system that is easy to read, easy to maintain, and aligned with common industry practice.

## Prototype

A small working prototype lives in this folder:

- `POC/openapi/openapi.yaml`
- `POC/redocly.yaml`
- `POC/README.md`

That prototype is intentionally small. It is there to show what the source file actually looks like and how a tracker API would be structured in practice.

## Sources

- OpenAPI Initiative overview and specification: <https://www.openapis.org/> and <https://spec.openapis.org/oas/latest.html>
- OpenAPI "What is OpenAPI?": <https://learn.openapis.org/specification/what.html>
- Swagger UI installation and static hosting docs: <https://swagger.io/docs/open-source-tools/swagger-ui/usage/installation/>
- Swagger UI configuration docs: <https://swagger.io/docs/open-source-tools/swagger-ui/usage/configuration/>
- Swagger and OpenAPI 3.1 support announcement: <https://swagger.io/blog/swagger-supports-openapi-3-1/>
- Redoc open source README and usage: <https://github.com/Redocly/redoc>
- Redocly CLI `build-docs`: <https://redocly.com/docs/cli/commands/build-docs>
- Redocly CLI `lint`: <https://redocly.com/docs/cli/commands/lint>
