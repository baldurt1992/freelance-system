import type { BadgeColor } from "~/utils/quoteStatus";
import type { BillingDocumentStatus } from "@freelance/contracts";

const STATUS_LABELS: Record<BillingDocumentStatus, string> = {
  draft: "Borrador",
  issued: "Emitida",
  sent: "Enviada",
  paid: "Pagada",
};

const STATUS_COLORS: Record<BillingDocumentStatus, BadgeColor> = {
  draft: "neutral",
  issued: "info",
  sent: "success",
  paid: "success",
};

export function getBillingStatusLabel(status: BillingDocumentStatus | string): string {
  return STATUS_LABELS[status as BillingDocumentStatus] ?? status;
}

export function getBillingStatusColor(status: BillingDocumentStatus | string): BadgeColor {
  return STATUS_COLORS[status as BillingDocumentStatus] ?? "neutral";
}
