import { CalendarDate } from "@internationalized/date";
import type { ProjectCreateInput, ProjectType } from "@freelance/contracts";
import type { ProjectFormState } from "./useProjectForm";

export function parseDateToCalendarDate(value?: string): CalendarDate | undefined {
  if (!value) return undefined;
  const parts = value.split("-").map((v) => parseInt(v, 10));
  if (parts.length !== 3 || parts.some((n) => Number.isNaN(n))) return undefined;
  const [year, month, day] = parts as [number, number, number];
  return new CalendarDate(year, month, day);
}

export function calendarDateToString(date: CalendarDate | null | undefined): string | undefined {
  if (!date) return undefined;
  const y = String(date.year).padStart(4, "0");
  const m = String(date.month).padStart(2, "0");
  const d = String(date.day).padStart(2, "0");
  return `${y}-${m}-${d}`;
}

export function agreedTotalToCents(
  agreedTotal: number | undefined,
  agreedTotalPreview: number | undefined,
): number {
  const value = agreedTotalPreview ?? agreedTotal;
  if (!value || !Number.isFinite(value) || value <= 0) return 0;
  return Math.round(value * 100);
}

export function serializeProjectCreatePayload(
  clientId: number,
  state: Pick<ProjectFormState, "name" | "notes" | "type" | "startedAt">,
  agreedTotalCents: number,
): ProjectCreateInput {
  return {
    client_id: clientId,
    name: state.name.trim(),
    notes: state.notes.trim() || null,
    type: state.type,
    agreed_total_cents: agreedTotalCents,
    started_at: state.startedAt ?? null,
  };
}

export const projectTypeOptions = [
  { label: "Freelance", value: "freelance" },
  { label: "Precio fijo", value: "fixed" },
  { label: "Retainer", value: "retainer" },
] as const satisfies ReadonlyArray<{ label: string; value: ProjectType }>;
