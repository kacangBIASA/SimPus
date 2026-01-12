import { API_URL } from "./config.js";

function getToken() {
  return localStorage.getItem("simpus_token");
}

export async function apiFetch(path, { method = "GET", body = null, headers = {} } = {}) {
  const token = getToken();

  const finalHeaders = {
    "Accept": "application/json",
    ...headers,
  };

  if (body !== null) finalHeaders["Content-Type"] = "application/json";
  if (token) finalHeaders["Authorization"] = `Bearer ${token}`;

  const res = await fetch(`${API_URL}${path}`, {
    method,
    headers: finalHeaders,
    body: body !== null ? JSON.stringify(body) : null,
  });

  const text = await res.text();
  let data;
  try { data = JSON.parse(text); } catch { data = { raw: text }; }

  if (!res.ok) {
    const message = data?.error?.message || data?.message || `HTTP ${res.status}`;
    const err = new Error(message);
    err.status = res.status;
    err.details = data?.error?.details || null;
    err.payload = data;
    throw err;
  }

  return data;
}
