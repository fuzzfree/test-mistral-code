# 001. Route requests with simple Slim routes

Date: 2026-11-21

## Status

Accepted

## Context

The app must expose two HTTP endpoints (`GET /` for service info and
`GET /weather` for the current Open-Meteo weather of Volos). Routing is the
core of the app, and the README explicitly requires the latest Slim version.

At the other end of the spectrum, a full framework (Laravel, Symfony full
stack) or a Slim setup with a DI container, auto-wiring, and configuration
files would give us infrastructure we do not need for two routes and a single
domain action.

## Decision

We use plain Slim 4 routing: routes are registered as closures directly in
`public/index.php`, receive their dependencies through `use (...)` bindings,
and the application is created with `AppFactory::create()` without a DI
container, without route configuration files, and without controllers.

## Consequences

- The entire request/response flow is visible in one file; no indirection
  through container definitions or route annotations.
- The dependency set stays minimal (`slim/slim` + `slim/psr7`).
- If the number of routes or collaborators grows, we will need to extract
  actions into classes and introduce a container — the closure style does not
  scale well beyond a handful of routes. This ADR should be superseded at that
  point.
