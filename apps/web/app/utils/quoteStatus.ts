export type BadgeColor = "neutral" | "info" | "success" | "error" | "warning" | "primary" | "secondary";

export function getQuoteStatusLabel(status: string): string {
  const map: Record<string, string> = {
    draft: "Borrador",
    sent: "Enviada",
    accepted: "Aceptada",
    rejected: "Rechazada",
    converted: "Convertida",
  };
  return map[status] ?? status;
}

export function getQuoteStatusColor(status: string): BadgeColor {
  const map: Record<string, BadgeColor> = {
    draft: "neutral",
    sent: "info",
    accepted: "success",
    rejected: "error",
    converted: "warning",
  };
  return map[status] ?? "neutral";
}
