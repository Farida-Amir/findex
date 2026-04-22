let currentTab = "all";

const input = document.getElementById("searchInput");
const resultsBox = document.getElementById("results");

input.addEventListener("input", () => {
  const q = input.value;

  if (!q) {
    resultsBox.innerHTML = "";
    return;
  }

  resultsBox.innerHTML = "<p class='loading'>Searching...</p>";

  fetch("/Findex/api/search.php?q=" + encodeURIComponent(q))
    .then(res => res.json())
    .then(data => {
      renderResults(data, q);
    });
});

function setTab(tab) {
  currentTab = tab;

  document.querySelectorAll(".tab").forEach(t => t.classList.remove("active"));
  event.target.classList.add("active");

  input.dispatchEvent(new Event("input"));
}

function highlight(text, q) {
  const regex = new RegExp(`(${q})`, "gi");
  return text.replace(regex, "<mark>$1</mark>");
}

function renderResults(data, q) {
  let html = "";

  if (currentTab === "all" || currentTab === "users") {
    html += "<div class='card'><h3>Users</h3>";

    if (data.users.length) {
      data.users.forEach(u => {
        html += `
          <div class="result-item user" onclick="openChat(${u.id})">
            <div class="avatar">${u.name.charAt(0).toUpperCase()}</div>
            <div>
              <strong>${highlight(u.name, q)}</strong>
              <p>Click to chat</p>
            </div>
          </div>
        `;
      });
    } else {
      html += "<p class='empty'>No users found</p>";
    }

    html += "</div>";
  }

  if (currentTab === "all" || currentTab === "reports") {
    html += "<div class='card'><h3>Reports</h3>";

    if (data.reports.length) {
      data.reports.forEach(r => {
        html += `
          <div class="result-item report">
            <div>
              <strong>${highlight(r.title, q)}</strong>
              <p>${highlight(r.description, q)}</p>
            </div>
            <span class="report-type ${r.type}">${r.type}</span>
          </div>
        `;
      });
    } else {
      html += "<p class='empty'>No reports found</p>";
    }

    html += "</div>";
  }

  resultsBox.innerHTML = html;
}

function openChat(id) {
  window.location.href = "/Findex/chat.php?user=" + id;
}