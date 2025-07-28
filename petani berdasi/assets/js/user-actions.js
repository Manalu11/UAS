// assets/js/user-actions.js

document.addEventListener("DOMContentLoaded", function () {
  console.log("user-actions.js loaded");

  // Konfirmasi sebelum menghapus alamat
  const deleteAddressButtons = document.querySelectorAll('a[href*="delete"]');
  deleteAddressButtons.forEach((button) => {
    button.addEventListener("click", function (e) {
      if (!confirm("Apakah Anda yakin ingin menghapus alamat ini?")) {
        e.preventDefault();
      }
    });
  });

  // Konfirmasi sebelum membatalkan transaksi
  const cancelTransactionButtons = document.querySelectorAll(
    'button[name="cancel_transaction"]'
  );
  cancelTransactionButtons.forEach((button) => {
    button.addEventListener("click", function (e) {
      if (!confirm("Yakin ingin membatalkan transaksi ini?")) {
        e.preventDefault();
      }
    });
  });

  // Konfirmasi sebelum menghapus item dari wishlist
  const removeWishlistButtons = document.querySelectorAll('a[href*="remove"]');
  removeWishlistButtons.forEach((button) => {
    button.addEventListener("click", function (e) {
      if (!confirm("Yakin ingin menghapus dari wishlist?")) {
        e.preventDefault();
      }
    });
  });

  // ========== CART FUNCTIONS ==========

  // Helper function untuk format number
  function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
  }

  // Helper function untuk show loading
  function showLoading() {
    let loadingOverlay = document.getElementById("loading-overlay");
    if (!loadingOverlay) {
      loadingOverlay = document.createElement("div");
      loadingOverlay.id = "loading-overlay";
      loadingOverlay.style.cssText = `
        display: block;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 9999;
      `;
      loadingOverlay.innerHTML = `
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: white;">
          <div class="spinner-border" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>
      `;
      document.body.appendChild(loadingOverlay);
    } else {
      loadingOverlay.style.display = "block";
    }
  }

  // Helper function untuk hide loading
  function hideLoading() {
    const loadingOverlay = document.getElementById("loading-overlay");
    if (loadingOverlay) {
      loadingOverlay.style.display = "none";
    }
  }

  // Function untuk update total
  function updateCartTotal() {
    const cartTable = document.getElementById("cart-table");
    if (!cartTable) return;

    let total = 0;
    const rows = cartTable.querySelectorAll(
      'tbody tr:not([style*="display: none"])'
    );

    rows.forEach((row) => {
      const price = parseInt(row.dataset.price) || 0;
      const quantityInput = row.querySelector(".quantity-input");
      const quantity = parseInt(quantityInput ? quantityInput.value : 0) || 0;
      total += price * quantity;
    });

    const totalElement = document.getElementById("total-amount");
    if (totalElement) {
      totalElement.textContent = "Rp " + formatNumber(total);
    }

    console.log("Total updated:", total);
  }

  // Handler untuk update quantity
  const updateQuantityButtons = document.querySelectorAll(".update-quantity");
  updateQuantityButtons.forEach((button) => {
    button.addEventListener("click", function (e) {
      e.preventDefault();
      console.log("Update quantity clicked");

      const cartId = this.dataset.id;
      const action = this.dataset.action;
      const row = this.closest("tr");
      const quantityInput = row.querySelector(".quantity-input");
      const currentQty = parseInt(quantityInput.value) || 0;

      console.log(
        "Cart ID:",
        cartId,
        "Action:",
        action,
        "Current Qty:",
        currentQty
      );

      // Prevent decrease below 1
      if (action === "decrease" && currentQty <= 1) {
        console.log("Cannot decrease below 1");
        return;
      }

      // Disable button during request
      this.disabled = true;
      showLoading();

      // Create FormData
      const formData = new FormData();
      formData.append("action", "update_quantity");
      formData.append("cart_id", cartId);
      formData.append("quantity_action", action);

      // Send AJAX request
      fetch(window.location.href, {
        method: "POST",
        body: formData,
      })
        .then((response) => {
          if (!response.ok) {
            throw new Error("Network response was not ok");
          }
          return response.json();
        })
        .then((data) => {
          console.log("AJAX Response:", data);
          hideLoading();
          this.disabled = false;

          if (data.success) {
            // Update quantity display
            quantityInput.value = data.new_quantity;

            // Update subtotal
            const subtotalElement = row.querySelector(".item-subtotal");
            if (subtotalElement) {
              if (data.subtotal) {
                subtotalElement.textContent =
                  "Rp " + formatNumber(data.subtotal);
              } else {
                const price = parseInt(row.dataset.price) || 0;
                const newSubtotal = price * data.new_quantity;
                subtotalElement.textContent = "Rp " + formatNumber(newSubtotal);
              }
            }

            // Update total
            updateCartTotal();
            console.log("Quantity updated successfully");
          } else {
            alert(data.message || "Terjadi kesalahan saat mengupdate quantity");
          }
        })
        .catch((error) => {
          console.error("AJAX Error:", error);
          hideLoading();
          this.disabled = false;
          alert("Terjadi kesalahan jaringan: " + error.message);
        });
    });
  });

  // Handler untuk remove item
  document.querySelectorAll(".add-to-cart").forEach((btn) => {
    btn.addEventListener("click", function (e) {
      e.preventDefault();
      const productId = this.dataset.productId;

      const formData = new FormData();
      formData.append("action", "add_to_cart");
      formData.append("product_id", productId);

      fetch("cart-handler.php", {
        method: "POST",
        body: formData,
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.success) {
            alert("Produk berhasil ditambahkan ke keranjang!");
          } else {
            alert(data.message || "Gagal menambahkan ke keranjang");
          }
        });
    });
  });

  const removeItemButtons = document.querySelectorAll(".remove-item");
  removeItemButtons.forEach((button) => {
    button.addEventListener("click", function (e) {
      e.preventDefault();
      console.log("Remove item clicked");

      if (!confirm("Yakin ingin menghapus item ini dari keranjang?")) {
        return;
      }

      const cartId = this.dataset.id;
      const row = this.closest("tr");

      console.log("Removing cart ID:", cartId);

      showLoading();

      // Create FormData
      const formData = new FormData();
      formData.append("action", "remove_item");
      formData.append("cart_id", cartId);

      // Send AJAX request
      fetch(window.location.href, {
        method: "POST",
        body: formData,
      })
        .then((response) => {
          if (!response.ok) {
            throw new Error("Network response was not ok");
          }
          return response.json();
        })
        .then((data) => {
          console.log("Remove Response:", data);
          hideLoading();

          if (data.success) {
            // Fade out and remove row
            row.style.transition = "opacity 0.3s";
            row.style.opacity = "0";

            setTimeout(() => {
              row.remove();
              updateCartTotal();

              // Check if cart is empty
              const cartTable = document.getElementById("cart-table");
              const visibleRows = cartTable
                ? cartTable.querySelectorAll(
                    'tbody tr:not([style*="display: none"])'
                  )
                : [];

              if (visibleRows.length === 0) {
                console.log("Cart is empty, reloading...");
                setTimeout(() => {
                  location.reload();
                }, 500);
              }
            }, 300);

            console.log("Item removed successfully");
          } else {
            alert(data.message || "Terjadi kesalahan saat menghapus item");
          }
        })
        .catch((error) => {
          console.error("Remove AJAX Error:", error);
          hideLoading();
          alert("Terjadi kesalahan jaringan: " + error.message);
        });
    });
  });

  // Initialize cart total on page load
  updateCartTotal();

  console.log("Cart functions initialized");
});
