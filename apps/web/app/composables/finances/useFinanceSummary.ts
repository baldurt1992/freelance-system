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
    const now = new Date();
    const yyyy = now.getFullYear();
    const mm = String(now.getMonth() + 1).padStart(2, "0");
    return `${yyyy}-${mm}`;
  }

  return { getNetLabel, getNetLabelText, monthInputDefault };
}
