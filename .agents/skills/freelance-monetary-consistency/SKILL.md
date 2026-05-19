---
name: freelance-monetary-consistency
description: Use when implementing or reviewing money, cents, optional VAT (tax_enabled), quote/project totals, ledger amounts, or PDF monetary output in Freelance System.
---

# Freelance Monetary Consistency

## When to use

- Campos `*_cents` en migraciones o API
- Totales de cotización, proyecto, cuenta de cobro
- `MoneyMath` o equivalente
- Activar/desactivar IVA (`tax_enabled`)

## Rules

1. **Storage:** integers only (`*_cents`).
2. **UI:** `formatMoney()` / presentación; no cálculo fiscal definitivo.
3. **No float** arithmetic for money in PHP or TS business logic.
4. **No hardcoded VAT** (`* 1.19`, `/ 1.19`) outside `MoneyMath`.
5. **`tax_enabled === false`:** force `tax_rate = 0`; backend recalculates line totals; ignore client-derived tax.
6. **`tax_enabled === true`:** respect `price_mode` (`net_first` | `gross_first`) end-to-end.

## Anti-patterns

- Duplicating total formulas in Vue and PHP.
- Trusting frontend totals on persist.
- Storing display strings instead of cents.

## Definition of done

- Single engine (`api/app/Support/Money/MoneyMath.php`).
- Round-trip tests for lines with and without tax.
- Resources expose cents; UI formats.

## References

- `docs/main/ARCHITECTURE.md` §8
- `docs/main/ENGINEERING_GUARDRAILS.md` §4
