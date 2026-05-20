import type { ClientCreateInput, ClientUpdateInput } from "@freelance/contracts";

export function useClientForm(initial?: Partial<ClientCreateInput>) {
  const name = ref(initial?.name ?? "");
  const email = ref(initial?.email ?? "");
  const phone = ref(initial?.phone ?? "");
  const taxId = ref(initial?.tax_id ?? "");
  const address = ref(initial?.address ?? "");
  const notes = ref(initial?.notes ?? "");
  const avatar = ref(initial?.avatar ?? "");

  const toCreateInput = (): ClientCreateInput => ({
    name: name.value,
    email: email.value || null,
    phone: phone.value || null,
    tax_id: taxId.value || null,
    address: address.value || null,
    notes: notes.value || null,
    avatar: avatar.value || null,
  });

  const toUpdateInput = (): ClientUpdateInput => ({
    name: name.value,
    email: email.value || null,
    phone: phone.value || null,
    tax_id: taxId.value || null,
    address: address.value || null,
    notes: notes.value || null,
    avatar: avatar.value || null,
  });

  const reset = () => {
    name.value = "";
    email.value = "";
    phone.value = "";
    taxId.value = "";
    address.value = "";
    notes.value = "";
    avatar.value = "";
  };

  return { name, email, phone, taxId, address, notes, avatar, toCreateInput, toUpdateInput, reset };
}
