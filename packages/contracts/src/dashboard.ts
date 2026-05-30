import { z } from "zod";
import { IsoDateTimeStringSchema } from "./common/datetime.js";
import { FinanceSummaryLabelSchema } from "./finances.js";

export const DashboardRecentActivityKindSchema = z.enum([
  "finance_entry",
  "quote",
  "project",
]);

export type DashboardRecentActivityKind = z.infer<
  typeof DashboardRecentActivityKindSchema
>;

export const DashboardKpisSchema = z.object({
  receivables_cents: z.number().int().nonnegative(),
  income_cents: z.number().int().nonnegative(),
  expense_cents: z.number().int().nonnegative(),
  pending_quotes_count: z.number().int().nonnegative(),
  active_projects_count: z.number().int().nonnegative(),
});

export type DashboardKpis = z.infer<typeof DashboardKpisSchema>;

export const DashboardPendingSchema = z.object({
  projects_with_balance_count: z.number().int().nonnegative(),
  sent_quotes_count: z.number().int().nonnegative(),
  draft_quotes_count: z.number().int().nonnegative(),
});

export type DashboardPending = z.infer<typeof DashboardPendingSchema>;

export const DashboardFinancialSummarySchema = z.object({
  income_cents: z.number().int().nonnegative(),
  expense_cents: z.number().int().nonnegative(),
  net_cents: z.number().int(),
  label: FinanceSummaryLabelSchema,
});

export type DashboardFinancialSummary = z.infer<
  typeof DashboardFinancialSummarySchema
>;

export const DashboardRecentActivityItemSchema = z.object({
  id: z.string().min(1),
  kind: DashboardRecentActivityKindSchema,
  title: z.string().min(1),
  description: z.string().min(1),
  occurred_at: IsoDateTimeStringSchema,
  to: z.string().min(1),
});

export type DashboardRecentActivityItem = z.infer<
  typeof DashboardRecentActivityItemSchema
>;

export const DashboardResponseSchema = z.object({
  month: z.string().regex(/^\d{4}-\d{2}$/),
  kpis: DashboardKpisSchema,
  pending: DashboardPendingSchema,
  financial_summary: DashboardFinancialSummarySchema,
  recent_activity: z.array(DashboardRecentActivityItemSchema),
});

export type DashboardResponse = z.infer<typeof DashboardResponseSchema>;
