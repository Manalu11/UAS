// Fungsi utilitas untuk dashboard
const utils = {
  formatRupiah: (angka) => "Rp " + angka.toLocaleString("id-ID"),

  formatTanggal: (dateString) => {
    const bulan = [
      "Jan",
      "Feb",
      "Mar",
      "Apr",
      "Mei",
      "Jun",
      "Jul",
      "Ags",
      "Sep",
      "Okt",
      "Nov",
      "Des",
    ];
    const tgl = new Date(dateString);
    return `${tgl.getDate()} ${bulan[tgl.getMonth()]} ${tgl.getFullYear()}`;
  },

  escapeHTML: (str) => {
    const div = document.createElement("div");
    div.textContent = str;
    return div.innerHTML;
  },
};
function initAdminDashboard() {
  // Inisialisasi chart pendapatan jika elemen ada
  if (document.getElementById("revenueChart")) {
    initRevenueChart();
  }

  // Inisialisasi statistik dashboard
  if (document.getElementById("stats-container")) {
    renderDashboardStats();
  }

  // Inisialisasi tabel produk terbaru
  if (document.getElementById("recent-products-table")) {
    renderRecentProducts();
  }

  // Inisialisasi daftar pengguna terbaru
  if (document.getElementById("recent-users-container")) {
    renderRecentUsers();
  }
}

/**
 * Inisialisasi chart pendapatan menggunakan Chart.js
 */
function initRevenueChart() {
  const revenueCtx = document.getElementById("revenueChart");
  if (!revenueCtx) return;

  new Chart(revenueCtx, {
    type: "bar",
    data: {
      labels: window.appData?.chartData?.labels || [],
      datasets: [
        {
          label: "Pendapatan (Rp)",
          data: window.appData?.chartData?.data || [],
          backgroundColor: "rgba(54, 162, 235, 0.7)",
          borderColor: "rgba(54, 162, 235, 1)",
          borderWidth: 1,
        },
      ],
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: function (context) {
              return "Rp " + context.raw.toLocaleString("id-ID");
            },
          },
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: function (value) {
              return "Rp " + value.toLocaleString("id-ID");
            },
          },
        },
      },
    },
  });
}

/**
 * Render statistik dashboard
 */
function renderDashboardStats() {
  const statsContainer = document.getElementById("stats-container");
  if (!window.appData?.stats) return;

  const stats = window.appData.stats;
  const currentMonthRevenue = window.appData.stats.current_month_revenue || 0;

  statsContainer.innerHTML = `
    <div class="col-md-3">
      <div class="card bg-primary text-white mb-4">
        <div class="card-body">
          <h5 class="card-title">Total Pengguna</h5>
          <h2 class="card-text">${stats.total_users}</h2>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-success text-white mb-4">
        <div class="card-body">
          <h5 class="card-title">Total Produk</h5>
          <h2 class="card-text">${stats.total_products}</h2>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-warning text-dark mb-4">
        <div class="card-body">
          <h5 class="card-title">Total Transaksi</h5>
          <h2 class="card-text">${stats.total_transactions}</h2>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-info text-white mb-4">
        <div class="card-body">
          <h5 class="card-title">Pendapatan Bulan Ini</h5>
          <h2 class="card-text">Rp ${currentMonthRevenue.toLocaleString(
            "id-ID"
          )}</h2>
        </div>
      </div>
    </div>
  `;
}

/**
 * Render daftar pengguna terbaru
 */
function renderRecentUsers() {
  const container = document.getElementById("recent-users-container");
  if (!window.appData?.stats?.recent_users) return;

  const users = window.appData.stats.recent_users;
  let html = '<div class="list-group">';

  users.forEach((user) => {
    html += `
      <div class="list-group-item d-flex align-items-center">
        <img src="${
          user.foto_profil || "../assets/images/default-profile.png"
        }" 
             class="rounded-circle me-3" width="40" height="40" alt="${
               user.username
             }">
        <div>
          <h6 class="mb-0">${user.username}</h6>
          <small class="text-muted">${user.email}</small>
        </div>
      </div>
    `;
  });

  html += "</div>";
  container.innerHTML = html;
}

/**
 * Render tabel produk terbaru
 */
/**
 * Render tabel produk terbaru dengan tombol aksi
 */
function renderRecentProducts() {
  const tableBody = document.querySelector("#recent-products-table tbody");
  if (!window.appData?.stats?.recent_products) return;

  // Format angka ke Rupiah
  const formatRupiah = (angka) => {
    return "Rp " + angka.toLocaleString("id-ID");
  };

  // Format tanggal Indonesia
  const formatTanggal = (dateString) => {
    const options = { day: "numeric", month: "short", year: "numeric" };
    return new Date(dateString).toLocaleDateString("id-ID", options);
  };

  // Bersihkan konten HTML berbahaya
  const escapeHTML = (str) => {
    return str.replace(
      /[&<>'"]/g,
      (tag) =>
        ({
          "&": "&amp;",
          "<": "&lt;",
          ">": "&gt;",
          "'": "&#39;",
          '"': "&quot;",
        }[tag])
    );
  };

  let barisProduk = "";

  window.appData.stats.recent_products.forEach((produk) => {
    barisProduk += `
      <tr>
        <td>${escapeHTML(produk.name)}</td>
        <td>${formatRupiah(produk.price)}</td>
        <td>${escapeHTML(produk.username)}</td>
        <td>${formatTanggal(produk.created_at)}</td>
        <td>
        "<a href="../produk/detail.php?id=${produk.id}"
             class="btn btn-sm btn-primary"
             title="Lihat detail produk">
            <i class="fas fa-eye me-1"></i> Detail
          </a>
        </td>
      </tr>
    `;
  });

  tableBody.innerHTML = barisProduk;
}

/**
 * Fungsi untuk inisialisasi fitur autentikasi
 */
function initAuthFunctions() {
  // Toggle show/hide password
  document.querySelectorAll(".toggle-password").forEach(function (button) {
    button.addEventListener("click", function () {
      const icon = this.querySelector("i");
      const passwordInput = document.getElementById("password");

      if (passwordInput.type === "password") {
        passwordInput.type = "text";
        icon.classList.replace("fa-eye", "fa-eye-slash");
      } else {
        passwordInput.type = "password";
        icon.classList.replace("fa-eye-slash", "fa-eye");
      }
    });
  });

  // Fungsi "Ingat Saya"
  const rememberCheckbox = document.getElementById("remember");
  if (rememberCheckbox) {
    const usernameInput = document.getElementById("username");

    if (localStorage.getItem("rememberUsername") === "true") {
      rememberCheckbox.checked = true;
      usernameInput.value = localStorage.getItem("savedUsername") || "";
    }

    rememberCheckbox.addEventListener("change", function () {
      if (this.checked) {
        localStorage.setItem("rememberUsername", "true");
        localStorage.setItem("savedUsername", usernameInput.value);
      } else {
        localStorage.removeItem("rememberUsername");
        localStorage.removeItem("savedUsername");
      }
    });
  }
}
/**
 * Admin Dashboard Script
 * Mengelola tampilan data dashboard admin
 */

document.addEventListener("DOMContentLoaded", function () {
  // Format tanggal ke format Indonesia
  function formatDate(dateString) {
    const options = { day: "2-digit", month: "short", year: "numeric" };
    return new Date(dateString).toLocaleDateString("id-ID", options);
  }

  // Escape HTML untuk mencegah XSS
  function escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  }

  // Format mata uang Rupiah
  function formatCurrency(amount) {
    return "Rp " + parseInt(amount).toLocaleString("id-ID");
  }

  // Render tabel produk terbaru
  function renderRecentProducts(products) {
    const tableBody = document.querySelector("#recent-products-table tbody");
    tableBody.innerHTML = "";

    products.forEach((product) => {
      const row = document.createElement("tr");
      row.innerHTML = `
                <td>${escapeHtml(product.name)}</td>
                <td>${formatCurrency(product.price)}</td>
                <td>${escapeHtml(product.username)}</td>
                <td>${formatDate(product.created_at)}</td>
              <td>
    <a href="/produk/detail.php?id=<?= htmlspecialchars($product['id']) ?>" 
       class="btn btn-sm btn-info" 
       title="Lihat detail produk"
       data-bs-toggle="tooltip" 
       data-bs-placement="top">
        <i class="fas fa-eye"></i> Detail
    </a>
</td>
            `;
      tableBody.appendChild(row);
    });
  }

  // Inisialisasi dashboard
  function initDashboard() {
    // Pastikan data tersedia di window.appData
    if (window.appData && window.appData.stats) {
      renderRecentProducts(window.appData.stats.recent_products);
    } else {
      console.error("Data dashboard tidak tersedia");
    }
  }

  // Jalankan inisialisasi
  initDashboard();
});
/**
 * File: admin-dashboard.js
 * Deskripsi: Menangani semua fungsi JavaScript untuk dashboard admin
 */

// Fungsi untuk menginisialisasi chart pendapatan
function initRevenueChart() {
  const revenueCtx = document.getElementById("revenueChart");
  if (!revenueCtx || !window.dashboardData?.chartData) return;

  new Chart(revenueCtx, {
    type: "bar",
    data: {
      labels: window.dashboardData.chartData.labels,
      datasets: [
        {
          label: "Pendapatan (Rp)",
          data: window.dashboardData.chartData.data,
          backgroundColor: "rgba(54, 162, 235, 0.7)",
          borderColor: "rgba(54, 162, 235, 1)",
          borderWidth: 1,
        },
      ],
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: function (context) {
              return "Rp " + context.raw.toLocaleString("id-ID");
            },
          },
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: function (value) {
              return "Rp " + value.toLocaleString("id-ID");
            },
          },
        },
      },
    },
  });
}

// Fungsi untuk animasi card statistik
function initCardAnimations() {
  const cards = document.querySelectorAll(".card-stat");
  cards.forEach((card) => {
    card.addEventListener("mouseenter", function () {
      this.style.transform = "translateY(-5px)";
      this.style.boxShadow = "0 10px 20px rgba(0,0,0,0.1)";
    });
    card.addEventListener("mouseleave", function () {
      this.style.transform = "";
      this.style.boxShadow = "";
    });
  });
}

// Fungsi untuk inisialisasi tooltip
function initTooltips() {
  const tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
  );
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });
}

// Fungsi utama yang dijalankan saat DOM siap
document.addEventListener("DOMContentLoaded", function () {
  initRevenueChart();
  initCardAnimations();
  initTooltips();

  // Tambahkan event listener untuk tombol detail
  document.querySelectorAll(".btn-detail").forEach((btn) => {
    btn.addEventListener("click", function (e) {
      // Anda bisa menambahkan logika tambahan di sini
      console.log("Melihat detail produk:", this.getAttribute("data-id"));
    });
  });
});
