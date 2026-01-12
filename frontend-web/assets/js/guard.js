import { me, clearAuth } from "./auth.js";

export async function requireAuth() {
  const token = localStorage.getItem("simpus_token");
  if (!token) {
    window.location.href = "./login.html";
    return;
  }

  // validasi token ke backend
  try {
    const res = await me();
    const user = res?.data;
    localStorage.setItem("simpus_user", JSON.stringify(user));
  } catch (e) {
    // token invalid/expired
    clearAuth();
    window.location.href = "./login.html";
  }
}
