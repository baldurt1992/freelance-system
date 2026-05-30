import { useApi } from "~/composables/api/useApi";
import { parseApiResponse } from "~/composables/api/parseApiResponse";
import {
  DashboardResponseSchema,
  type DashboardResponse,
} from "@freelance/contracts";

export function useDashboardApi() {
  const { api } = useApi();

  async function getDashboard(month: string): Promise<DashboardResponse> {
    const data = await api(`/dashboard?month=${month}`);
    return parseApiResponse(DashboardResponseSchema, data, "dashboard.getDashboard");
  }

  return { getDashboard };
}
