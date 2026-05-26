import type { ProjectType } from "@freelance/contracts";

export interface ProjectFormState {
  clientId?: number;
  name: string;
  notes: string;
  type: ProjectType;
  agreedTotal?: number;
  agreedTotalPreview?: number;
  startedAt?: string;
}

/**
 * Estado reactivo del formulario de creación de proyecto.
 */
export function useProjectForm(initial?: Partial<ProjectFormState>) {
  const clientId = ref<number | undefined>(initial?.clientId);
  const name = ref(initial?.name ?? "");
  const notes = ref(initial?.notes ?? "");
  const type = ref<ProjectType>(initial?.type ?? "freelance");
  const agreedTotal = ref<number | undefined>(initial?.agreedTotal);
  const agreedTotalPreview = ref<number | undefined>(initial?.agreedTotalPreview);
  const startedAt = ref<string | undefined>(initial?.startedAt);

  function reset() {
    clientId.value = undefined;
    name.value = "";
    notes.value = "";
    type.value = "freelance";
    agreedTotal.value = undefined;
    agreedTotalPreview.value = undefined;
    startedAt.value = undefined;
  }

  return {
    clientId,
    name,
    notes,
    type,
    agreedTotal,
    agreedTotalPreview,
    startedAt,
    reset,
  };
}
