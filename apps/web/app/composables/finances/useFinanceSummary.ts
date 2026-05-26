import { defaultFinanceMonth } from "./financeMonthHelpers";

export function useFinanceSummary() {
  function getNetLabel(netCents: number): "surplus" | "shortfall" | "balanced" {
    if (netCents > 0) return "surplus";
    if (netCents < 0) return "shortfall";
    return "balanced";
  }

  function getNetLabelText(label: "surplus" | "shortfall" | "balanced"): string {
    if (label === "surplus") return "Sobrante";
    if (label === "shortfall") return "Faltante";
    return "Equilibrio";
  }

  function monthInputDefault(): string {
    return defaultFinanceMonth();
  }

  return { getNetLabel, getNetLabelText, monthInputDefault };
}
