import { requireAuth } from "./guard.js";
import { logout, getUser } from "./auth.js";
import { apiFetch } from "./api.js";

await requireAuth();

document.getElementById("btnLogout").addEventListener("click", async () => {
  await logout();
  window.location.href = "./login.html";
});

const user = getUser();
const tbody = document.querySelector("#tableLoans tbody");
const errorBox = document.getElementById("error");

function fmt(dt) {
  if (!dt) return "-";
  return new Date(dt).toLocaleString();
}

async function loadLoans() {
  errorBox.textContent = "";
  tbody.innerHTML = `<tr><td colspan="7">Loading...</td></tr>`;

  try {
    const res = await apiFetch("/loans");
    const paged = res.data; // paginate object

    tbody.innerHTML = "";
    if (!paged.data.length) {
      tbody.innerHTML = `<tr><td colspan="7">Tidak ada data</td></tr>`;
      return;
    }

    for (const l of paged.data) {
      const bookTitle = l.book?.title ?? "-";
      const canReturn = l.returned_at === null;

      const actions = [];
      if (canReturn) {
        // member boleh return loan milik sendiri; admin juga boleh
        actions.push(`<a href="#" data-ret="${l.id}">Return</a>`);
      }

      tbody.insertAdjacentHTML("beforeend", `
        <tr>
          <td>${l.id}</td>
          <td>${bookTitle}</td>
          <td>${l.status}</td>
          <td>${fmt(l.borrowed_at)}</td>
          <td>${fmt(l.due_at)}</td>
          <td>${fmt(l.returned_at)}</td>
          <td>${actions.join(" ") || "-"}</td>
        </tr>
      `);
    }

    tbody.querySelectorAll("[data-ret]").forEach(a => {
      a.addEventListener("click", async (e) => {
        e.preventDefault();
        const loanId = Number(a.getAttribute("data-ret"));
        if (!confirm(`Return loan #${loanId}?`)) return;

        try {
          await apiFetch("/loans/return", { method:"POST", body:{ loan_id: loanId } });
          await loadLoans();
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

await loadLoans();
