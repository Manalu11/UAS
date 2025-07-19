/**
 * Main JavaScript File untuk Petani Berdasi
 * File ini dapat digunakan di berbagai halaman
 */

// ============================================
// UTILITY FUNCTIONS
// ============================================

/**
 * Fungsi untuk validasi nomor WhatsApp
 */
function validateWhatsAppNumber(number) {
  const cleanNumber = number.replace(/\D/g, "");
  if (cleanNumber.length < 10 || cleanNumber.length > 15) return false;
  return /^(62|08)\d+$/.test(cleanNumber);
}

/**
 * Fungsi untuk menampilkan notifikasi
 */
function showNotification(message, type = "success") {
  const notification = document.createElement("div");
  notification.className = `alert ${type}`;
  notification.innerHTML = `
        ${message}
        <span class="alert-close" onclick="this.parentElement.remove()">&times;</span>
    `;
  document.body.appendChild(notification);
  setTimeout(() => {
    if (notification.parentNode) {
      notification.remove();
    }
  }, 5000);
}

/**
 * Fungsi untuk mendapatkan CSRF token
 */
function getCsrfToken() {
  const metaToken = document.querySelector('meta[name="csrf-token"]');
  return metaToken ? metaToken.content : "";
}

/**
 * Fungsi untuk format rupiah
 */
function formatRupiah(amount) {
  return new Intl.NumberFormat("id-ID").format(amount);
}

// ============================================
// ALERT FUNCTIONS
// ============================================

/**
 * Tutup alert message
 */
function closeAlert() {
  const alertMessage = document.getElementById("alertMessage");
  if (alertMessage) {
    alertMessage.style.display = "none";
  }
}

// ============================================
// MODAL FUNCTIONS
// ============================================

/**
 * Buka modal keranjang
 */
function openModal() {
  const cartModal = document.getElementById("cartModal");
  if (cartModal) {
    cartModal.style.display = "block";
    loadCartItems();
  }
}

/**
 * Tutup modal keranjang
 */
function closeModal() {
  const cartModal = document.getElementById("cartModal");
  if (cartModal) {
    cartModal.style.display = "none";
  }
}

// ============================================
// DELETE CONFIRMATION
// ============================================

let productToDelete = null;

/**
 * Konfirmasi hapus produk
 */
function confirmDelete(productId) {
  productToDelete = productId;
  const confirmModal = document.getElementById("confirmModal");
  if (confirmModal) {
    confirmModal.style.display = "block";
  }
}

/**
 * Tutup modal konfirmasi hapus
 */
function closeConfirmModal() {
  productToDelete = null;
  const confirmModal = document.getElementById("confirmModal");
  if (confirmModal) {
    confirmModal.style.display = "none";
  }
}

/**
 * Proses hapus produk
 */
function processDelete() {
  if (productToDelete) {
    const deleteBtn = document.getElementById("confirmDeleteBtn");
    if (deleteBtn) {
      deleteBtn.disabled = true;
      deleteBtn.innerHTML =
        '<i class="fas fa-spinner fa-spin"></i> Menghapus...';
    }
    window.location.href = "hapus_produk.php?id=" + productToDelete;
  }
}

// ============================================
// CART FUNCTIONS
// ============================================

let cart = [];

/**
 * Muat item keranjang
 */
function loadCartItems() {
  fetch("get_keranjang.php")
    .then((response) => response.json())
    .then((data) => {
      let cartItemsHtml = "";
      let total = 0;

      if (data.items && data.items.length > 0) {
        data.items.forEach((item) => {
          cartItemsHtml += `
                    <div class="cart-item">
                        <img src="/${item.gambar}" alt="${item.judul}">
                        <div class="cart-item-info">
                            <div class="cart-item-title">${item.judul}</div>
                            <div class="cart-item-price">Rp ${formatRupiah(
                              item.harga
                            )} x ${item.quantity}</div>
                        </div>
                        <button class="btn-remove-cart" data-product-id="${
                          item.id
                        }">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>`;
          total += item.harga * item.quantity;
        });
      } else {
        cartItemsHtml =
          '<div class="empty-cart"><p>Keranjang belanja kosong</p></div>';
      }

      const cartItemsElement = document.getElementById("cartItems");
      const cartTotalElement = document.getElementById("cartTotal");

      if (cartItemsElement) {
        cartItemsElement.innerHTML = cartItemsHtml;
      }
      if (cartTotalElement) {
        cartTotalElement.textContent = formatRupiah(total);
      }
    })
    .catch((error) => {
      console.error("Error loading cart:", error);
      showNotification("Gagal memuat keranjang", "error");
    });
}

/**
 * Update jumlah item di keranjang
 */
function updateCartCount(count) {
  const cartCount = document.getElementById("cartCount");
  if (cartCount) {
    cartCount.textContent = count;
    cartCount.style.display = count > 0 ? "inline-block" : "none";
  }
}

/**
 * Tambah produk ke keranjang
 */
function addToCart(productId) {
  const button = document.querySelector(
    `.btn-keranjang[onclick="addToCart(${productId})"]`
  );
  if (!button) return;

  const originalHTML = button.innerHTML;
  button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
  button.disabled = true;

  const formData = new FormData();
  formData.append("product_id", productId);
  formData.append("csrf_token", getCsrfToken());

  fetch("tambah_keranjang.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status === "success") {
        updateCartCount(data.cartCount);
        showNotification("Produk berhasil ditambahkan ke keranjang", "success");
      } else {
        showNotification(
          data.message || "Gagal menambahkan ke keranjang",
          "error"
        );
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      showNotification("Terjadi kesalahan", "error");
    })
    .finally(() => {
      button.innerHTML = originalHTML;
      button.disabled = false;
    });
}

/**
 * Hapus produk dari keranjang
 */
function removeFromCart(productId) {
  const formData = new FormData();
  formData.append("product_id", productId);
  formData.append("csrf_token", getCsrfToken());

  fetch("hapus_keranjang.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status === "success") {
        loadCartItems();
        updateCartCount(data.cartCount);
        showNotification("Item dihapus dari keranjang", "success");
      } else {
        showNotification(data.message || "Gagal menghapus item", "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      showNotification("Terjadi kesalahan", "error");
    });
}

// ============================================
// CHECKOUT FUNCTIONS
// ============================================

/**
 * Checkout dengan produk tunggal via WhatsApp
 */
function checkoutProduct(productId, title, price, sellerName, sellerWhatsApp) {
  const cleanWa = sellerWhatsApp.replace(/\D/g, "");
  const message = `Halo ${sellerName}, saya tertarik dengan produk ${title} (Rp ${formatRupiah(
    price
  )}) yang Anda jual. Apakah masih tersedia?`;
  window.open(
    `https://wa.me/${cleanWa}?text=${encodeURIComponent(message)}`,
    "_blank"
  );
}

/**
 * Checkout keranjang via WhatsApp
 */
async function checkout() {
  try {
    // Ambil data keranjang dari server
    const response = await fetch("get_keranjang.php");
    const data = await response.json();

    if (!data.items || data.items.length === 0) {
      showNotification("Keranjang belanja kosong!", "error");
      return;
    }

    // Buat pesan WhatsApp
    let message = "Halo, saya ingin memesan produk berikut:%0A%0A";
    let total = 0;

    data.items.forEach((item) => {
      message += `- ${item.judul} (Rp ${formatRupiah(item.harga)}) x${
        item.quantity
      }%0A`;
      total += item.harga * item.quantity;
    });

    message += `%0ATotal: Rp ${formatRupiah(total)}%0A%0ATerima kasih!`;

    // Ambil nomor WA penjual (asumsi semua item dari penjual yang sama)
    const waNumber = data.items[0].nomor_wa.replace(/\D/g, "");

    // Buka WhatsApp
    window.open(`https://wa.me/${waNumber}?text=${message}`, "_blank");
    closeModal();
  } catch (error) {
    console.error("Checkout error:", error);
    showNotification("Gagal melakukan checkout", "error");
  }
}

// ============================================
// SEARCH FUNCTIONS
// ============================================

/**
 * Cari produk
 */
function searchProducts() {
  const searchInput = document.getElementById("searchInput");
  if (searchInput) {
    const query = searchInput.value.trim();
    if (query) {
      window.location.href = "beranda.php?search=" + encodeURIComponent(query);
    }
  }
}

/**
 * Handle enter key di search input
 */
function handleSearchEnter(event) {
  if (event.key === "Enter") {
    searchProducts();
  }
}

// ============================================
// COMMENT FUNCTIONS - DIPERBAIKI
// ============================================

/**
 * Toggle tampilan komentar
 */
function toggleComments(postId) {
  const commentsContainer = document.getElementById(`comments-${postId}`);
  if (!commentsContainer) {
    console.error(`Comments container not found for post ${postId}`);
    return;
  }

  if (
    commentsContainer.style.display === "none" ||
    !commentsContainer.style.display
  ) {
    commentsContainer.style.display = "block";
    loadComments(postId);
  } else {
    commentsContainer.style.display = "none";
  }
}

/**
 * Muat komentar dari server
 */
async function loadComments(postId) {
  try {
    const response = await fetch(`get_komentar.php?post_id=${postId}`);

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const result = await response.json();
    const commentsList = document.getElementById(`comments-list-${postId}`);

    if (!commentsList) {
      console.error(`Comments list container not found for post ${postId}`);
      return;
    }

    if (
      result.status === "success" &&
      result.comments &&
      result.comments.length > 0
    ) {
      let html = "";

      // Filter komentar utama (bukan balasan)
      const mainComments = result.comments.filter(
        (comment) => !comment.parent_id || comment.parent_id == 0
      );

      mainComments.forEach((comment) => {
        // Filter balasan untuk komentar ini
        const replies = result.comments.filter(
          (reply) => reply.parent_id == comment.id
        );

        html += generateCommentHtml(comment, replies);
      });

      commentsList.innerHTML = html;
    } else {
      commentsList.innerHTML = `
        <div class="no-comments">
          <p>Belum ada komentar. Jadilah yang pertama berkomentar!</p>
        </div>
      `;
    }
  } catch (error) {
    console.error("Error loading comments:", error);
    showNotification("Gagal memuat komentar", "error");

    const commentsList = document.getElementById(`comments-list-${postId}`);
    if (commentsList) {
      commentsList.innerHTML = `
        <div class="no-comments error">
          <p>Gagal memuat komentar. Silakan coba lagi.</p>
        </div>
      `;
    }
  }
}

/**
 * Generate HTML untuk komentar dan balasannya
 */
function generateCommentHtml(comment, replies = []) {
  const avatar = comment.foto_profil
    ? `/${comment.foto_profil}`
    : "/assets/images/default-avatar.png";
  const commentDate = new Date(comment.created_at).toLocaleDateString("id-ID", {
    day: "2-digit",
    month: "short",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });

  // Pastikan content komentar tidak kosong
  const commentContent =
    comment.isi ||
    comment.content ||
    comment.komentar ||
    "[Konten tidak tersedia]";

  let html = `
    <div class="comment-item" data-comment-id="${comment.id}">
      <div class="comment-header">
        <img src="${avatar}" alt="${
    comment.username
  }" class="comment-avatar" onerror="this.src='/assets/images/default-avatar.png'">
        <div class="comment-meta">
          <strong class="comment-author">${comment.username}</strong>
          <span class="comment-date">${commentDate}</span>
        </div>
      </div>
      <div class="comment-content">
        ${commentContent.replace(/\n/g, "<br>")}
      </div>
      <button class="btn-reply" onclick="showReplyForm(${comment.id}, ${
    comment.post_id || comment.produk_id
  })">
        <i class="fas fa-reply"></i> Balas
      </button>
  `;

  // Tambahkan balasan jika ada
  if (replies && replies.length > 0) {
    html += `<div class="replies-container" id="replies-${comment.id}">`;

    replies.forEach((reply) => {
      const replyAvatar = reply.foto_profil
        ? `/${reply.foto_profil}`
        : "/assets/images/default-avatar.png";
      const replyDate = new Date(reply.created_at).toLocaleDateString("id-ID", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
      });
      const replyContent =
        reply.isi ||
        reply.content ||
        reply.komentar ||
        "[Konten tidak tersedia]";

      html += `
        <div class="reply-item" data-comment-id="${reply.id}">
          <div class="comment-header">
            <img src="${replyAvatar}" alt="${
        reply.username
      }" class="comment-avatar" onerror="this.src='/assets/images/default-avatar.png'">
            <div class="comment-meta">
              <strong class="comment-author">${reply.username}</strong>
              <span class="comment-date">${replyDate}</span>
            </div>
          </div>
          <div class="comment-content">
            ${replyContent.replace(/\n/g, "<br>")}
          </div>
        </div>
      `;
    });

    html += `</div>`;
  }

  html += `</div>`;

  return html;
}

/**
 * Tambah komentar baru
 */
async function addComment(event, postId) {
  event.preventDefault();

  const form = event.target;
  const textarea = form.querySelector('textarea[name="comment"]');
  const submitBtn = form.querySelector(".submit-comment-btn");
  const comment = textarea.value.trim();

  if (!comment) {
    showNotification("Komentar tidak boleh kosong", "error");
    return false;
  }

  // Disable form saat proses pengiriman
  const originalText = submitBtn.innerHTML;
  submitBtn.disabled = true;
  submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim...';

  try {
    const formData = new FormData();
    formData.append("post_id", postId);
    formData.append("content", comment);
    formData.append("csrf_token", getCsrfToken());

    const response = await fetch("tambah_komentar.php", {
      method: "POST",
      body: formData,
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const result = await response.json();

    if (result.status === "success") {
      textarea.value = "";
      showNotification("Komentar berhasil ditambahkan", "success");

      // Update comment count
      const commentCount = document.getElementById(`comment-count-${postId}`);
      if (commentCount) {
        const currentCount = parseInt(commentCount.textContent) || 0;
        commentCount.textContent = currentCount + 1;
      }

      // Reload comments untuk menampilkan komentar baru
      await loadComments(postId);
    } else {
      showNotification(result.message || "Gagal menambahkan komentar", "error");
    }
  } catch (error) {
    console.error("Error adding comment:", error);
    showNotification("Terjadi kesalahan saat mengirim komentar", "error");
  } finally {
    submitBtn.disabled = false;
    submitBtn.innerHTML = originalText;
  }

  return false;
}

/**
 * Tampilkan form balasan
 */
function showReplyForm(commentId, postId) {
  // Sembunyikan semua form balasan yang mungkin terbuka
  document.querySelectorAll(".reply-form-container").forEach((form) => {
    form.style.display = "none";
  });

  // Tampilkan form balasan yang dipilih
  const replyFormContainer = document.getElementById(
    `reply-form-container-${postId}`
  );
  if (replyFormContainer) {
    replyFormContainer.style.display = "block";

    // Set parent_id
    const parentIdInput = document.getElementById(`parent-id-${postId}`);
    if (parentIdInput) {
      parentIdInput.value = commentId;
    }

    // Focus pada textarea
    const textarea = replyFormContainer.querySelector(
      'textarea[name="reply_content"]'
    );
    if (textarea) {
      textarea.focus();
    }

    // Scroll ke form
    replyFormContainer.scrollIntoView({ behavior: "smooth", block: "nearest" });
  } else {
    console.error(`Reply form container not found for post ${postId}`);
  }
}

/**
 * Sembunyikan form balasan
 */
function hideReplyForm(postId) {
  const replyFormContainer = document.getElementById(
    `reply-form-container-${postId}`
  );
  if (replyFormContainer) {
    replyFormContainer.style.display = "none";

    // Reset form
    const form = replyFormContainer.querySelector("form");
    if (form) {
      form.reset();
    }
  }
}

/**
 * Tambah balasan komentar
 */
async function addReply(event, postId) {
  event.preventDefault();

  const form = event.target;
  const textarea = form.querySelector('textarea[name="reply_content"]');
  const submitBtn = form.querySelector(".btn-submit-reply");
  const parentIdInput = form.querySelector('input[name="parent_id"]');
  const replyContent = textarea.value.trim();
  const parentId = parentIdInput.value;

  if (!replyContent) {
    showNotification("Balasan tidak boleh kosong", "error");
    return false;
  }

  if (!parentId) {
    showNotification("Parent comment ID tidak ditemukan", "error");
    return false;
  }

  // Disable form saat proses pengiriman
  const originalText = submitBtn.innerHTML;
  submitBtn.disabled = true;
  submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim...';

  try {
    const formData = new FormData();
    formData.append("post_id", postId);
    formData.append("parent_id", parentId);
    formData.append("content", replyContent);
    formData.append("csrf_token", getCsrfToken());

    const response = await fetch("tambah_komentar.php", {
      method: "POST",
      body: formData,
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const result = await response.json();

    if (result.status === "success") {
      textarea.value = "";
      hideReplyForm(postId);
      showNotification("Balasan berhasil dikirim", "success");

      // Update comment count
      const commentCount = document.getElementById(`comment-count-${postId}`);
      if (commentCount) {
        const currentCount = parseInt(commentCount.textContent) || 0;
        commentCount.textContent = currentCount + 1;
      }

      // Reload comments untuk menampilkan balasan baru
      await loadComments(postId);
    } else {
      showNotification(result.message || "Gagal mengirim balasan", "error");
    }
  } catch (error) {
    console.error("Error adding reply:", error);
    showNotification("Terjadi kesalahan saat mengirim balasan", "error");
  } finally {
    submitBtn.disabled = false;
    submitBtn.innerHTML = originalText;
  }

  return false;
}

/**
 * Hapus komentar
 */
async function deleteComment(commentId) {
  if (!confirm("Apakah Anda yakin ingin menghapus komentar ini?")) return;

  try {
    const formData = new FormData();
    formData.append("comment_id", commentId);
    formData.append("csrf_token", getCsrfToken());

    const response = await fetch("hapus_komentar.php", {
      method: "POST",
      body: formData,
    });

    // Periksa status HTTP
    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(
        errorData.message || `HTTP error! status: ${response.status}`
      );
    }

    const result = await response.json();

    if (result.status === "success") {
      showNotification("Komentar berhasil dihapus", "success");

      // Cari elemen komentar dan hapus dari DOM
      const commentElement = document.querySelector(
        `[data-comment-id="${commentId}"]`
      );
      if (commentElement) {
        commentElement.remove();

        // Update jumlah komentar
        const commentsContainer = commentElement.closest(".comments-container");
        if (commentsContainer) {
          const commentCount =
            commentsContainer.querySelector(".comment-count");
          if (commentCount) {
            const currentCount = parseInt(commentCount.textContent) || 0;
            commentCount.textContent = currentCount - 1;
          }
        }
      }
    } else {
      throw new Error(result.message || "Gagal menghapus komentar");
    }
  } catch (error) {
    console.error("Error:", error);
    showNotification(
      error.message || "Terjadi kesalahan saat menghapus komentar",
      "error"
    );
  }
}

// ============================================
// FORM FUNCTIONS
// ============================================

/**
 * Validasi form produk
 */
function validateProductForm(form) {
  const requiredFields = ["judul", "deskripsi", "harga", "kategori"];
  let isValid = true;

  requiredFields.forEach((fieldName) => {
    const field = form.querySelector(`[name="${fieldName}"]`);
    if (field && !field.value.trim()) {
      showNotification(`Field ${fieldName} harus diisi`, "error");
      isValid = false;
      return false;
    }
  });

  // Validasi harga
  const harga = form.querySelector('[name="harga"]');
  if (harga && (isNaN(harga.value) || parseInt(harga.value) < 0)) {
    showNotification("Harga harus berupa angka positif", "error");
    isValid = false;
  }

  return isValid;
}

/**
 * Preview gambar sebelum upload
 */
function previewImage(input) {
  const preview = document.getElementById("imagePreview");
  if (input.files && input.files[0] && preview) {
    const reader = new FileReader();
    reader.onload = function (e) {
      preview.src = e.target.result;
      preview.style.display = "block";
    };
    reader.readAsDataURL(input.files[0]);
  }
}

// ============================================
// EVENT LISTENERS & INITIALIZATION
// ============================================

/**
 * Inisialisasi saat DOM ready
 */
document.addEventListener("DOMContentLoaded", function () {
  // Initialize cart count
  fetch("get_keranjang.php")
    .then((response) => response.json())
    .then((data) => updateCartCount(data.cartCount || 0))
    .catch(() => {}); // Silent fail untuk halaman tanpa keranjang

  // Cart button event
  const cartButton = document.getElementById("cartButton");
  if (cartButton) {
    cartButton.addEventListener("click", function (e) {
      e.preventDefault();
      openModal();
    });
  }

  // Confirm delete button
  const confirmDeleteBtn = document.getElementById("confirmDeleteBtn");
  if (confirmDeleteBtn) {
    confirmDeleteBtn.addEventListener("click", processDelete);
  }

  // Search input enter key
  const searchInput = document.getElementById("searchInput");
  if (searchInput) {
    searchInput.addEventListener("keypress", handleSearchEnter);
  }

  // Auto-hide alert messages
  const alertMessage = document.getElementById("alertMessage");
  if (alertMessage) {
    setTimeout(() => {
      alertMessage.style.display = "none";
    }, 5000);
  }

  // Pastikan komentar visible dengan styling yang tepat
  const comments = document.querySelectorAll(".comment-content");
  comments.forEach(function (comment) {
    comment.style.color = "#333";
    comment.style.fontSize = "14px";
    comment.style.lineHeight = "1.5";
    comment.style.marginBottom = "8px";

    if (!comment.textContent.trim()) {
      comment.textContent = "[Komentar kosong]";
      comment.style.color = "#999";
      comment.style.fontStyle = "italic";
    }
  });

  // Initialize comment forms
  initializeCommentForms();
});

/**
 * Inisialisasi form komentar
 */
function initializeCommentForms() {
  // Enable/disable submit button based on textarea content
  document.querySelectorAll(".form-komentar textarea").forEach((textarea) => {
    const form = textarea.closest(".form-komentar");
    const submitBtn = form ? form.querySelector(".submit-comment-btn") : null;

    if (submitBtn) {
      // Initial state
      submitBtn.disabled = textarea.value.trim() === "";

      // Listen for input changes
      textarea.addEventListener("input", function () {
        submitBtn.disabled = this.value.trim() === "";
      });
    }
  });

  // Initialize reply form textareas
  document.querySelectorAll(".reply-form textarea").forEach((textarea) => {
    const form = textarea.closest(".reply-form");
    const submitBtn = form ? form.querySelector(".btn-submit-reply") : null;

    if (submitBtn) {
      // Initial state
      submitBtn.disabled = textarea.value.trim() === "";

      // Listen for input changes
      textarea.addEventListener("input", function () {
        submitBtn.disabled = this.value.trim() === "";
      });
    }
  });
}

/**
 * Event listeners untuk modal
 */
window.onclick = function (event) {
  const confirmModal = document.getElementById("confirmModal");
  const cartModal = document.getElementById("cartModal");

  if (event.target === confirmModal) {
    closeConfirmModal();
  }
  if (event.target === cartModal) {
    closeModal();
  }
};

/**
 * Event listener untuk ESC key
 */
document.addEventListener("keydown", function (event) {
  if (event.key === "Escape") {
    closeConfirmModal();
    closeModal();

    // Close all reply forms
    document.querySelectorAll(".reply-form-container").forEach((form) => {
      form.style.display = "none";
    });
  }
});

// ============================================
// EXPORT FUNCTIONS (untuk debugging)
// ============================================

// Buat fungsi global tersedia di window untuk debugging
if (typeof window !== "undefined") {
  window.PetaniBerdasi = {
    validateWhatsAppNumber,
    showNotification,
    getCsrfToken,
    formatRupiah,
    addToCart,
    removeFromCart,
    checkout,
    checkoutProduct,
    searchProducts,
    toggleComments,
    addComment,
    loadComments,
    addReply,
    showReplyForm,
    hideReplyForm,
    deleteComment,
    validateProductForm,
    previewImage,
    generateCommentHtml,
    initializeCommentForms,
  };
}

function clearCart() {
  const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

  // Tampilkan loading state
  const clearBtn = document.querySelector(".btn-clear");
  const originalText = clearBtn.innerHTML;
  clearBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengosongkan...';
  clearBtn.disabled = true;

  fetch("kosongkan_keranjang.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: `csrf_token=${encodeURIComponent(csrfToken)}`,
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error("Network response was not ok");
      }
      return response.json();
    })
    .then((data) => {
      if (data.status === "success") {
        // Update UI
        updateCartCount(0);
        document.getElementById("cartItems").innerHTML =
          '<div class="empty-cart"><p>Keranjang belanja kosong</p></div>';
        document.getElementById("cartTotal").textContent = "0";

        // Tampilkan notifikasi
        showNotification("Keranjang berhasil dikosongkan", "success");
      } else {
        throw new Error(data.message || "Gagal mengosongkan keranjang");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      showNotification(
        error.message || "Terjadi kesalahan saat mengosongkan keranjang",
        "error"
      );
    })
    .finally(() => {
      // Reset button state
      clearBtn.innerHTML = originalText;
      clearBtn.disabled = false;
    });
}
