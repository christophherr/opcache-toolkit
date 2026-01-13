# Architecture Decision Records (ADRs)

This directory contains Architecture Decision Records for the OPcache Toolkit plugin.

## What is an ADR?

An Architecture Decision Record captures an important architectural decision made along with its context and consequences. ADRs help us understand:

- **Why** decisions were made
- **What alternatives** were considered
- **What trade-offs** we accepted

## ADR Index

### [ADR-001: PSR-4 Conversion Strategy](./001-psr4-conversion-strategy.md)
**Decision:** Adopt a hybrid approach - convert code to classes only where it provides measurable benefits in testing, maintainability, or performance.

**Key Points:**
- Convert ~9 files to classes (Services, Commands, REST endpoints)
- Keep ~8 files procedural (simple hooks, templates, enqueues)
- Conversion criteria: testing needs, complex logic, caching requirements, file size

**Status:** Proposed

---

### [ADR-002: Service Instantiation Strategy](./002-service-instantiation-strategy.md)
**Decision:** Use static accessor methods on a `Plugin` class to provide shared service instances.

**Key Points:**
- Full IDE autocomplete via typed return values
- Shared instances for 3-5 core services
- Can still inject mocks for testing
- Rejected: Direct instantiation (too repetitive), Service Container (overkill)

**Status:** Proposed

---

### [ADR-003: Hook Registration Pattern](./003-hook-registration-pattern.md)
**Decision:** Keep hooks inline near their context; do NOT centralize in Hook Subscriber classes.

**Key Points:**
- Asset enqueues stay with page registration (screen ID context)
- Only plugin-wide hooks centralized in `Plugin::boot()`
- Rejected: Hook Subscriber pattern (disconnects context, adds boilerplate)

**Status:** Proposed

---

### [ADR-004: Design Patterns to Implement](./004-design-patterns-to-implement.md)
**Decision:** Implement only 4 patterns that solve specific problems: Service Wrapper, Repository, Command, REST Split.

**Key Points:**
- **Service Wrapper** - Enables testing via mocking
- **Repository** - Adds caching for 95% performance improvement
- **Command** - Reusable operations with consistent results
- **REST Split** - Improves maintainability of large files
- **Rejected:** Hook Subscriber, Service Container, Strategy, Facade (no benefits)

**Status:** Proposed

---

### [ADR-005: Strict Type Safety for PSR-4 Classes](./005-strict-type-safety.md)
**Decision:** All new PSR-4 classes MUST use `declare(strict_types=1);` and full type hints.

**Key Points:**
- `declare(strict_types=1);` at top of every class file
- Parameter types on all method arguments
- Return types on all methods (including `void`)
- Property types on all class properties
- Procedural files remain untyped (backward compatibility)
- Benefits: Runtime type checking, IDE autocomplete, self-documenting code

**Status:** Proposed

---

### [ADR-006: Observability Strategy](./006-observability-strategy.md)
**Decision:** Implement structured logging, debug mode toggle, and system diagnostics to improve troubleshooting and reduce support burden.

**Key Points:**
- **Structured Logging:** JSON-formatted logs with context via `Logger` service
- **Debug Mode Toggle:** Enable verbose logging independent of `WP_DEBUG` (tentative)
- **WP-CLI Doctor Command:** Automated health checks for CI/CD and self-diagnosis
- **System Report Page:** Copy-paste environment, config, and plugin details for support
- Logging disabled by default (zero overhead)

**Status:** Accepted

---

### [ADR-007: Multi-Layered Testing Strategy](./007-multi-layered-testing-strategy.md)
**Decision:** Implement a combination of Unit and Integration tests using Mockery, Brain\Monkey, and php-mock to ensure 100% testability of PSR-4 logic.

**Key Points:**
- **Unit Tests**: Isolated logic testing using mocks for external dependencies.
- **Integration Tests**: WordPress-dependent testing for DB and API interactions.
- **php-mock**: Mocking global PHP functions (`opcache_*`) via namespace interception.
- **Service Wrappers**: Isolate environment-dependent code for clean testing.

**Status:** Accepted

---

## Decision Status

- **Proposed** - Decision documented, not yet implemented
- **Accepted** - Decision approved and implementation started
- **Deprecated** - Decision superseded by a later ADR
- **Superseded** - Use this status with a link to the new ADR

## Creating a New ADR

When documenting a new architectural decision:

1. Copy the template below
2. Number it sequentially (005, 006, etc.)
3. Fill in all sections
4. Update this README index
5. Commit to version control

### ADR Template

```markdown
# ADR XXX: [Title]

**Status:** [Proposed | Accepted | Deprecated | Superseded]
**Date:** YYYY-MM-DD
**Deciders:** [List of people involved]

## Context

What is the issue we're facing? What factors are at play?

## Decision

What is the change we're proposing/implementing?

## Consequences

### Positive
- What becomes easier?

### Negative
- What becomes harder?

### Neutral
- What is unchanged but worth noting?

## Alternatives Considered

### Alternative 1
Why was this rejected?

### Alternative 2
Why was this rejected?

## Implementation Notes

Specific details about implementing the decision.

## References

Links to relevant resources.
```

## Philosophy

Our ADRs follow these principles:

### 1. Pragmatism Over Purity
We reject patterns that exist for their own sake. Every architectural decision must improve:
- **Performance** - Measurable speed/memory improvements
- **Maintainability** - Reduced cognitive load, better organization
- **Testing** - Enable unit tests, allow mocking, isolate logic

### 2. WordPress-First
We embrace WordPress conventions where they work:
- Procedural code is fine for simple tasks
- Hooks stay near their context
- Global functions acceptable for utilities

### 3. Evidence-Based
Decisions cite specific problems:
- ✅ "466-line file is hard to navigate" (measurable)
- ✅ "Cannot mock opcache_get_status()" (concrete blocker)
- ❌ "We should use dependency injection" (ideology)

### 4. No Frameworks
We don't import Laravel/Symfony patterns wholesale. Each pattern must justify itself in a WordPress context.

## Reading Order

For new team members, read in this order:

1. **ADR-001** (Conversion Strategy) - Understand what we're converting and why
2. **ADR-004** (Design Patterns) - Understand which patterns we use and which we reject
3. **ADR-002** (Service Instantiation) - Understand how services are accessed
4. **ADR-003** (Hook Registration) - Understand how WordPress hooks are organized

## Questions?

If an ADR doesn't adequately explain a decision:
1. Ask questions in pull request reviews
2. Update the ADR with clarifications
3. Create a new ADR if the decision has changed

ADRs are living documents - they should evolve as our understanding improves.
