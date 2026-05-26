export interface ApiUser {
  id: number;
  name: string;
  email: string;
  email_verified_at: string | null;
  created_at: string | null;
}

export interface ApiTenant {
  id: string;
  name: string;
  tax_enabled: boolean;
  currency: string;
  tax_rate?: number;
}

export interface LoginResponse {
  token: string;
  token_type: string;
  user: ApiUser;
}

export interface MeResponse {
  user: ApiUser;
  tenant: ApiTenant;
}
