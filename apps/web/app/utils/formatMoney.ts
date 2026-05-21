export function formatMoney(cents: number, currency: string): string {
  const value = (cents / 100).toLocaleString("es-CO", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
  return `${value} ${currency}`;
}
