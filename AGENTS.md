# AGENTS.md — Kainat Travels Bus Mobile Development Rules

## Purpose

This file defines strict repository, branch, and folder boundaries for the Kainat Travels bus mobile application.

All coding agents, Codex sessions, developers, and automated tools must follow these rules before reading, editing, generating, committing, merging, or pushing code.

---

## Repositories and Local Paths

### Main bus system repository

- GitHub repository: `hhhashhhim/KTBUSCLONE`
- Local path: `/Users/malik/Desktop/Codes/KTBUSGIT`

This repository contains the existing bus system, the mobile backend/API branch, and the Flutter mobile application branch.

### Lovable UI reference repository

- GitHub repository: `hhhashhhim/bus-ui-lovable`
- Local path: `/Users/malik/Desktop/Codes/KTC WEBVIEW Lovable`

This is a separate repository containing the Lovable-generated frontend.

The Lovable repository is a design and UI reference only. It must not be treated as the production Flutter application or Laravel backend.

Do not copy the Lovable repository's Git history, `.git` folder, package configuration, or entire source tree into `KTBUSGIT`.

---

## Mandatory Checks Before Any Change

Before editing any file, run and review:

```bash
pwd
git remote -v
git branch --show-current
git status --short
```

The agent must state:

1. Which repository is open.
2. Which branch is active.
3. Which application layer is being changed.
4. Which files or folders are expected to change.

If the repository, branch, or requested work does not match the rules below, stop and ask for explicit approval before making changes.

Do not silently switch branches.

---

## Branch Ownership

### `main`

Purpose:

- Stable baseline of the existing bus system.
- Source branch for reviewed production changes.

Rules:

- Do not develop the mobile frontend directly on `main`.
- Do not develop mobile-specific APIs directly on `main`.
- Do not merge, rebase, cherry-pick, or push to `main` without explicit user approval.
- Treat `main` as protected.

### `mobile-backend`

Purpose:

- Laravel/PHP backend work required specifically by the Android and iOS mobile application.
- Mobile authentication, passenger APIs, booking APIs, wallet APIs where supported, update checks, notifications, and other mobile-facing endpoints.

Allowed changes may include:

- `app/`
- `routes/`
- `database/`
- `config/`
- backend tests
- API documentation
- other Laravel files required for mobile API functionality

Rules:

- Do not create or edit Flutter application code on this branch.
- Do not copy Lovable React/Vite code into this branch.
- Do not make unrelated ERP, web dashboard, or production changes unless explicitly approved.
- Existing APIs must be reused where suitable instead of duplicated.
- New APIs must not break the existing website, counters, agents, partners, or ERP workflows.
- API changes must preserve authentication, authorization, branch access, reporting, and audit controls.

### `mobile-frontend`

Purpose:

- Flutter application for Android and iOS.

Primary application folder:

- `/kt_mobile_app/`

Rules:

- All Flutter code must remain inside `kt_mobile_app/`, unless a root-level file is specifically required for Flutter project management or documentation.
- Do not edit Laravel backend files on this branch.
- Do not add new Laravel routes, controllers, migrations, models, or backend business logic on this branch.
- Backend requirements discovered during frontend work must be documented and implemented separately on `mobile-backend`.
- The Flutter app must call mobile APIs through configurable environment/base URLs.
- Never hard-code production credentials, secrets, tokens, database credentials, or private keys.
- The same Flutter codebase should support Android and iOS.
- Lovable code may be studied for layout, typography, colors, spacing, assets, screens, and user flows.
- Do not directly paste web-only React, TypeScript, DOM, CSS, Tailwind, or Vite code into Flutter.
- Recreate the Lovable design using Flutter widgets and Flutter-compatible packages.

### `api`

Purpose:

- Existing general API branch or legacy API work.

Rules:

- Treat as reference unless the user explicitly assigns work to this branch.
- Do not assume this branch is the mobile backend.
- Do not merge it into `mobile-backend` or `mobile-frontend` without reviewing differences and obtaining approval.

### `mix`

Purpose:

- Existing mixed or legacy development branch.

Rules:

- Treat as read-only reference unless explicitly instructed otherwise.
- Do not use it as the base for new mobile development.
- Do not merge it into a mobile branch without explicit approval.

---

## Lovable Repository Rules

When working inside:

`/Users/malik/Desktop/Codes/KTC WEBVIEW Lovable`

the expected GitHub repository must be:

`hhhashhhim/bus-ui-lovable`

The Lovable repository should normally use its `main` branch for two-way Lovable sync.

Rules:

- Keep Lovable-generated changes in the Lovable repository.
- Do not add Laravel backend code.
- Do not add Flutter code.
- Do not connect this repository as a remote of `KTBUSGIT`.
- Do not convert this repository into a branch of `KTBUSCLONE`.
- Do not rename, delete, transfer, or disconnect the synced GitHub repository without explicit approval, because doing so may interrupt Lovable synchronization.
- Use it as the approved visual reference when recreating screens in Flutter.

---

## Work Routing Rules

Route every task before editing:

- Flutter screen, navigation, widget, mobile UI, Android, or iOS work:
  - Repository: `KTBUSGIT`
  - Branch: `mobile-frontend`
  - Folder: `kt_mobile_app/`

- Mobile API, authentication, database integration, mobile booking logic, passenger data, update API, or server-side mobile feature:
  - Repository: `KTBUSGIT`
  - Branch: `mobile-backend`

- Lovable visual, web prototype, or design-reference work:
  - Repository: `bus-ui-lovable`
  - Branch: Lovable's currently synced branch, normally `main`

- Existing stable system or deployment work:
  - Repository: `KTBUSGIT`
  - Branch: only the explicitly approved branch

If one task requires both frontend and backend work, handle them as two separate work units on their respective branches. Never perform both sets of edits on one branch.

---

## Branch Switching Safety

Before switching branches:

```bash
git status --short
```

If there are uncommitted changes:

- Do not switch branches.
- Do not stash, discard, reset, or commit them automatically.
- Explain what is pending and ask for approval.

After switching:

```bash
git branch --show-current
git status --short
```

Confirm the correct branch before editing.

---

## Prohibited Git Actions Without Explicit Approval

Do not run any of the following without the user's explicit permission:

```bash
git reset --hard
git clean -fd
git clean -fdx
git push --force
git push --force-with-lease
git rebase
git merge
git cherry-pick
git branch -D
git restore .
git checkout -- .
```

Also prohibited without approval:

- Deleting branches.
- Renaming branches.
- Changing Git remotes.
- Moving code between repositories.
- Rewriting commit history.
- Committing generated secrets or environment files.
- Opening or merging pull requests.
- Pushing directly to `main`.

---

## Commit and Push Rules

Do not commit or push automatically unless the user explicitly asks.

Before a commit, show:

```bash
git status --short
git diff --stat
```

A commit must contain changes for only one layer:

- Flutter frontend, or
- mobile backend/API, or
- Lovable UI reference

Do not mix unrelated frontend and backend changes in one commit.

Use clear commit messages, for example:

```text
feat(mobile): add passenger login screen
feat(api): add mobile passenger profile endpoint
fix(mobile): correct seat selection state
docs: document mobile booking API
```

---

## Mobile Architecture Boundaries

The Flutter frontend may:

- Display data returned by APIs.
- Validate basic user input.
- Manage presentation state.
- Store only appropriate non-sensitive local preferences.
- Handle navigation and user interaction.

The Flutter frontend must not:

- Connect directly to the production database.
- Contain database credentials.
- Reimplement server-authoritative booking, payment, refund, rescheduling, fare, wallet, seat-locking, or permission rules.
- Assume an API exists without checking the backend.
- silently invent API fields or responses.

The mobile backend must remain authoritative for:

- Authentication and authorization.
- Passenger records.
- Schedules, routes, cities, terminals, and fares.
- Seat availability and seat locking.
- Bookings and ticket issuance.
- Payment verification.
- Wallet rules where the main system supports them.
- Refund and rescheduling workflows handled by head office or call center.
- Force-update and minimum-supported-version decisions.
- Audit trails and access control.

---

## API Contract Rules

Before integrating a Flutter screen with an endpoint:

1. Confirm the endpoint exists.
2. Confirm request method, URL, headers, and authentication.
3. Confirm request and response fields.
4. Confirm validation and error formats.
5. Confirm whether data comes from the existing system or requires backend work.

If the endpoint does not exist:

- Do not fabricate production behavior.
- Create a clearly documented backend requirement.
- Implement it on `mobile-backend`.
- Integrate it on `mobile-frontend` only after the contract is confirmed.

Keep API documentation synchronized with backend changes.

---

## Design Translation Rules

The Lovable project is the visual source of reference, not a code source for direct reuse.

When translating a Lovable screen into Flutter:

1. Identify the screen's purpose and user flow.
2. Record colors, typography, spacing, icons, images, and component states.
3. Recreate the screen using reusable Flutter widgets.
4. Support loading, empty, success, validation, offline, and error states.
5. Preserve accessibility and responsive behavior.
6. Connect real APIs only after the API contract is verified.
7. Do not alter backend logic merely to imitate placeholder Lovable data.

---

## Required Completion Report

After making changes, report:

- Repository used.
- Branch used.
- Files changed.
- What was implemented.
- Tests or checks run.
- Any backend/frontend dependency still pending.
- Whether anything was committed or pushed.

Never claim a test passed unless it was actually run.
