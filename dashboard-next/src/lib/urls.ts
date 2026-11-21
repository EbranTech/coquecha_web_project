function withoutTrailingSlash(value: string): string {
  return value.replace(/\/$/, "");
}

export const appUrl = withoutTrailingSlash(process.env.PUBLIC_APP_URL ?? "http://localhost:8080");
export const operationsUrl = withoutTrailingSlash(process.env.PUBLIC_OPERATIONS_URL ?? "http://localhost:5175");
export const panelUrl = withoutTrailingSlash(process.env.PUBLIC_PANEL_URL ?? "http://localhost:3000");

export const loginUrl = `${appUrl}/login`;
export const adminUrl = `${appUrl}/admin`;
