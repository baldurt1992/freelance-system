---
name: freelance-document-workflow
description: Use for quote-to-project conversion, advances and balance_due, project completion, billing documents (cuenta de cobro), PDF templates, and email jobs in Freelance System.
---

# Freelance Document Workflow

## When to use

- Quotes (cotizaciones) lifecycle
- Convert quote → project
- Payments: advance, partial, final
- `balance_due_cents`
- Billing documents on project complete
- PDF/email templates per client

## State machine (reference)

| Entity          | States                                               |
| --------------- | ---------------------------------------------------- |
| Quote           | `draft`, `sent`, `accepted`, `rejected`, `converted` |
| Project         | `active`, `on_hold`, `completed`, `cancelled`        |
| BillingDocument | `draft`, `issued`, `sent`, `paid`                    |

## Rules

1. **Conversion** is a single Application transaction: quote locked → project created → quote marked `converted`.
2. **Payments** update `balance_due_cents`; never negative without explicit write-off rule.
3. **Ledger income** requires `source_type` + `source_id` (project, billing_document, etc.).
4. **On `project.completed`:** enqueue job → build billing document → PDF → email (idempotent).
5. **Templates** are HTML/Blade (or equivalent) per tenant/client; money from `MoneyMath`, not template math.

## Anti-patterns

- Generating billing PDF in Nuxt.
- Marking project complete without billing side-effect when policy requires invoice.
- Duplicate quote→project without idempotency key.

## References

- `docs/main/ARCHITECTURE.md` §9
- `.agents/skills/freelance-monetary-consistency/SKILL.md`
