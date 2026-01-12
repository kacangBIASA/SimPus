import { apiFetch } from "./api.js";

export function saveAuth(token, user) {
  localStorage.setItem("simpus_token", token);
  localStorage.setItem("simpus_user", JSON.stringify(user));
}

export function clearAuth() {
  localStorage.removeItem("simpus_token");
  localStorage.removeItem("simpus_user");
}

export function getUser() {
  const raw = localStorage.getItem("simpus_user");
  if (!raw) return null;
  try { return JSON.parse(raw); } catch { return null; }
}

export async function login(email, password) {
  const res = await apiFetch("/auth/login", {
    method: "POST",
    body: { email, password },
  });

  saveAuth(res.data.token, res.data.user);
  return res;
}

export async function register(name, email, password) {
  const res = await apiFetch("/auth/register", {
    method: "POST",
    body: { name, email, password },
  });

  saveAuth(res.data.token, res.data.user);
  return res;
}

export async function logout() {
  try {
    await apiFetch("/auth/logout", { method: "POST" });
  } finally {
    clearAuth();
  }
}

export async function me() {
  return apiFetch("/auth/me");
}
