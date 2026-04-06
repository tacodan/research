# Tracker API Documentation Prototype

This folder is a small proof of concept for the recommended setup:

- `OpenAPI 3.1` as the source of truth
- `Redocly CLI` for linting and HTML generation
- `Redoc` as the single-page documentation view

## Files

- `openapi/openapi.yaml`: the API contract
- `redocly.yaml`: optional config entry for linting/building
- `index.html`: the built docs page to open in a browser

## How It Works

You edit `openapi/openapi.yaml`.

That file defines:

- API metadata
- auth
- endpoints
- parameters
- request bodies
- response bodies
- examples

Then a renderer reads the spec and builds a docs page from it.

## What The Docs Page Looks Like

With `Redoc`, the page is a single long API reference with:

- a left sidebar for navigation
- endpoint details in the main column
- schemas and examples on the right

Each endpoint appears with its method, path, summary, auth, parameters, example payloads, and responses.

## Typical Commands

```bash
redocly lint openapi/openapi.yaml
redocly build-docs openapi/openapi.yaml -o docs/api/index.html
```

That second command creates a static HTML page that you can open in a browser or publish anywhere.

## Open The POC

Open `index.html` directly in a browser.

That file is the generated documentation page for this sample API.

If you rebuild the docs later, refresh `index.html` from the latest generated output.

## Prototype Scope

This sample includes:

- bearer auth
- list trackers
- get tracker by id
- list telemetry for a tracker
- send a ping command to a tracker
- shared error responses

The point is not to fully model the app yet. The point is to show what the workflow feels like with a realistic starter spec.
