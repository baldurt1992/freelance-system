export function parseLocalizedNumber(value: string): number | null {
  const trimmed = value.trim();
  if (trimmed === "") return null;

  const normalized = trimmed
    .replace(/\s+/g, "")
    .replace(/\.(?=\d{3}(\D|$))/g, "")
    .replace(/,/g, ".");

  const parsed = Number(normalized);
  return Number.isNaN(parsed) ? null : parsed;
}
