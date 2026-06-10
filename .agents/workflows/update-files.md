---
description: ensures that every code change is accompanied by updates to all affected project artifacts, including documentation, environment configuration files, dependency manifests, deployment scripts, and
---

# Documentation & Configuration Synchronization Workflow

## Objective

Ensure that every implemented feature, bug fix, architectural change, infrastructure change, or dependency update automatically triggers updates to:

- README.md
- PRD.md
- Architecture documentation
- API documentation
- Setup guides
- Deployment guides
- Docker documentation
- composer.json / composer.lock
- package.json / package-lock.json
- .env.example
- .env.local.example
- docker-entrypoint.sh
- Docker scripts
- CI/CD workflows
- Migration documentation
- Changelog

No task is considered complete until all affected artifacts have been reviewed and updated.

---

# Feature Development Lifecycle

## Phase 1: Analysis

Before implementation:

### Identify Impacted Areas

For every change, determine whether it affects:

| Area                  | Examples                                |
| --------------------- | --------------------------------------- |
| Environment Variables | New API keys, feature flags             |
| Dependencies          | Composer, NPM, Python packages          |
| Database              | Migrations, schema changes              |
| Infrastructure        | Docker, queues, cache, storage          |
| API                   | New endpoints, request/response changes |
| Authentication        | Roles, permissions                      |
| Frontend              | UI flows, pages                         |
| Business Logic        | Workflows, automations                  |
| Documentation         | README, PRD, setup guides               |

Generate an Impact Report:

```text
Impacted Files:
- README.md
- PRD.md
- .env.example
- docker_entrypoint.sh

Required Updates:
- Add OPENAI_API_KEY
- Document AI workflow
- Update installation guide
- Update deployment steps
```

---

## Phase 2: Implementation

Implement the feature.

During implementation:

### Dependency Rules

If a dependency is added:

Update:

- composer.json
- composer.lock
- package.json
- package-lock.json
- requirements.txt
- poetry.lock

Document:

- Why dependency was added
- What feature uses it
- Installation requirements

---

### Environment Variable Rules

Whenever a new env variable is introduced:

Mandatory updates:

```text
.env.example
.env.local.example
README.md
Deployment Guide
```

Example:

```env
OPENAI_API_KEY=
OPENAI_MODEL=gpt-5
```

Never introduce a new environment variable without updating both example files.

---

### Infrastructure Rules

If any of the following change:

- Docker
- Queue Workers
- Redis
- PostgreSQL
- Storage
- Supabase
- Cron Jobs

Update:

```text
docker-compose.yml
Dockerfile
docker_entrypoint.sh
README.md
Infrastructure Docs
Deployment Docs
```

---

## Phase 3: Documentation Sync

Immediately after implementation:

### README Review

Verify:

- Installation instructions
- Setup steps
- Environment variables
- Feature list
- Screenshots
- Usage examples
- Troubleshooting section

Update any outdated information.

---

### PRD Review

Verify:

- New requirements
- Updated requirements
- Modified workflows
- New user stories
- Acceptance criteria

Update accordingly.

---

### Architecture Review

Verify:

- System diagrams
- Service interactions
- Queue architecture
- Storage architecture
- API flows

Update diagrams if architecture changed.

---

## Phase 4: Validation

Run Documentation Validation Checklist.

### Environment Validation

Ensure:

```text
All env variables exist in:
✓ .env.example
✓ .env.local.example
✓ Documentation
```

---

### Dependency Validation

Ensure:

```text
All packages exist in:
✓ Lock files
✓ Installation docs
✓ Deployment docs
```

---

### Script Validation

Ensure:

```text
docker_entrypoint.sh
Dockerfile
docker-compose.yml
CI workflows
```

match the current implementation.

---

### Documentation Validation

Ensure:

```text
README.md
PRD.md
Architecture Docs
API Docs
Changelog
```

reflect the latest behavior.

---

## Phase 5: Pull Request Gate

A pull request cannot be merged unless the following checklist passes.

### Documentation Checklist

- [ ] README updated
- [ ] PRD updated
- [ ] Architecture docs updated
- [ ] API docs updated

### Configuration Checklist

- [ ] .env.example updated
- [ ] .env.local.example updated
- [ ] Secrets documented

### Dependency Checklist

- [ ] Composer dependencies documented
- [ ] NPM dependencies documented
- [ ] Lock files updated

### Infrastructure Checklist

- [ ] Docker scripts updated
- [ ] CI/CD workflows updated
- [ ] Deployment documentation updated

### Validation Checklist

- [ ] Fresh clone tested
- [ ] Setup instructions verified
- [ ] Documentation verified against implementation

---

# Definition of Done

A task is considered complete only if:

✓ Code is implemented

✓ Tests pass

✓ Documentation is updated

✓ Example env files are updated

✓ Deployment scripts are updated

✓ Docker scripts are updated

✓ Dependencies are documented

✓ PRD reflects the latest behavior

✓ README reflects the latest setup process

✓ Validation checklist passes

Otherwise, the task remains In Progress.

---

# Antigravity Enforcement Rule

Before marking any task as completed, perform:

1. Artifact Impact Analysis
2. Documentation Synchronization
3. Configuration Synchronization
4. Deployment Synchronization
5. Validation Checklist

If any affected artifact has not been updated:

STOP.

Return:

"Implementation completed, but documentation/configuration synchronization is incomplete. Update required artifacts before task closure."
