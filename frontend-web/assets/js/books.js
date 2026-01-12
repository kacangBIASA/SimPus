import { requireAuth } from "./guard.js";
import { getUser, logout } from "./auth.js";
import { apiFetch } from "./api.js";

await requireAuth();

const user = getUser();
const btnAdd = document.getElementById("btnAdd");
const btnLogout = document.getElementById("btnLogout");

btnLogout.addEventListener("click", async () => {
  await logout();
  window.location.href = "./login.html";
});

if (user?.role === "admin") {
  btnAdd.style.display = "inline-block";
  btnAdd.addEventListener("click", () => {
    window.location.href = "./book-form.html";
  });
}

const tbody = document.querySelector("#tableBooks tbody");
const errorBox = document.getElementById("error");
const searchInput = document.getElementById("search");
const btnSearch = document.getElementById("btnSearch");
const prevBtn = document.getElementById("prev");
const nextBtn = document.getElementById("next");
const pageInfo = document.getElementById("pageInfo");

let page = 1;
const perPage = 10;
let lastPage = 1;

function escapeHtml(s) {
  return String(s ?? "").replace(/[&<>"']/g, m => ({
    "&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#039;"
  }[m]));
}

async function loadBooks() {
  errorBox.textContent = "";
  tbody.innerHTML = `<tr><td colspan="6">Loading...</td></tr>`;

  const search = searchInput.value.trim();
  const qs = new URLSearchParams({ page, per_page: perPage });
  if (search) qs.set("search", search);

  try {
    const res = await apiFetch(`/books?${qs.toString()}`);
    const paged = res.data; // Laravel paginate object
    lastPage = paged.last_page;

    pageInfo.textContent = `Page ${paged.current_page} / ${paged.last_page}`;

    tbody.innerHTML = "";
    if (!paged.data.length) {
      tbody.innerHTML = `<tr><td colspan="6">Tidak ada data</td></tr>`;
      return;
    }

    for (const b of paged.data) {
      const cat = b.category?.name ?? "-";
      const stok = `${b.stock_available}/${b.stock_total}`;

      const actions = [
        `<a href="./book-detail.html?id=${b.id}">Detail</a>`
      ];

      if (user?.role === "admin") {
        actions.push(` | <a href="./book-form.html?id=${b.id}">Edit</a>`);
        actions.push(` | <a href="#" data-del="${b.id}">Delete</a>`);
      }

      tbody.insertAdjacentHTML("beforeend", `
        <tr>
          <td>${b.id}</td>
          <td>${escapeHtml(b.title)}</td>
          <td>${escapeHtml(b.author)}</td>
          <td>${escapeHtml(cat)}</td>
          <td>${stok}</td>
          <td>${actions.join("")}</td>
        </tr>
      `);
    }

    // bind delete
    tbody.querySelectorAll("[data-del]").forEach(a => {
      a.addEventListener("click", async (e) => {
        e.preventDefault();
        const id = a.getAttribute("data-del");
        if (!confirm(`Hapus buku ID ${id}?`)) return;

        try {
          await apiFetch(`/books/${id}`, { method: "DELETE" });
          await loadBooks();
        } catch (err) {
          errorBox.textContent = err.message;
        }
      });
    });

  } catch (err) {
    tbody.innerHTML = "";
    errorBox.textContent = err.message;
  }
}

btnSearch.addEventListener("click", () => { page = 1; loadBooks(); });
prevBtn.addEventListener("click", () => { if (page > 1) { page--; loadBooks(); } });
nextBtn.addEventListener("click", () => { if (page < lastPage) { page++; loadBooks(); } });

await loadBooks();
