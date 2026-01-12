import { requireAuth } from "./guard.js";
import { getUser, logout } from "./auth.js";
import { apiFetch } from "./api.js";

await requireAuth();

document.getElementById("btnLogout").addEventListener("click", async () => {
  await logout();
  window.location.href = "./login.html";
});

const user = getUser();
const errorBox = document.getElementById("error");

if (user?.role !== "admin") {
  errorBox.textContent = "Forbidden: admin only";
  throw new Error("admin only");
}

const params = new URLSearchParams(window.location.search);
const id = params.get("id"); // kalau ada -> edit

const formTitle = document.getElementById("formTitle");
const form = document.getElementById("bookForm");

const categorySelect = document.getElementById("category_id");
const titleInput = document.getElementById("title");
const authorInput = document.getElementById("author");
const isbnInput = document.getElementById("isbn");
const totalInput = document.getElementById("stock_total");
const availInput = document.getElementById("stock_available");

async function loadCategories() {
  const res = await apiFetch("/categories");
  categorySelect.innerHTML = "";
  for (const c of res.data) {
    const opt = document.createElement("option");
    opt.value = c.id;
    opt.textContent = c.name;
    categorySelect.appendChild(opt);
  }
}

async function loadBookIfEdit() {
  if (!id) return;
  formTitle.textContent = "Edit Buku";

  const res = await apiFetch(`/books/${id}`);
  const b = res.data;

  categorySelect.value = b.category_id ?? b.category?.id;
  titleInput.value = b.title;
  authorInput.value = b.author;
  isbnInput.value = b.isbn ?? "";
  totalInput.value = b.stock_total;
  availInput.value = b.stock_available;
}

form.addEventListener("submit", async (e) => {
  e.preventDefault();
  errorBox.textContent = "";

  const payload = {
    category_id: Number(categorySelect.value),
    title: titleInput.value.trim(),
    author: authorInput.value.trim(),
    isbn: isbnInput.value.trim() || null,
    stock_total: Number(totalInput.value),
    stock_available: Number(availInput.value),
  };

  // validasi ringan FE
  if (payload.stock_available > payload.stock_total) {
    errorBox.textContent = "stock_available tidak boleh lebih besar dari stock_total";
    return;
  }

  try {
    if (!id) {
      await apiFetch("/books", { method: "POST", body: payload });
    } else {
      await apiFetch(`/books/${id}`, { method: "PUT", body: payload });
    }
    window.location.href = "./books.html";
  } catch (err) {
    errorBox.textContent = err.message;
    if (err.details) console.log("Validation details:", err.details);
  }
});

await loadCategories();
await loadBookIfEdit();
