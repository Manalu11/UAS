document.addEventListener("DOMContentLoaded", function () {
  // Like button functionality
  document.querySelectorAll(".btn-like").forEach((button) => {
    button.addEventListener("click", function () {
      const productId = this.getAttribute("data-product-id");
      const likeCount = this.querySelector(".like-count");
      const icon = this.querySelector("i");
      const isLiked = this.classList.contains("text-danger");

      fetch("includes/like_product.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: `product_id=${productId}&action=${isLiked ? "unlike" : "like"}`,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            this.classList.toggle("text-danger");
            this.classList.toggle("text-secondary");
            likeCount.textContent = data.like_count;
          } else {
            showToast("danger", data.message || "Gagal memproses like");
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          showToast("danger", "Terjadi kesalahan");
        });
    });
  });

  // Add to cart functionality
  document.querySelectorAll(".btn-add-to-cart").forEach((button) => {
    button.addEventListener("click", function () {
      const productId = this.getAttribute("data-product-id");

      fetch("produk/keranjang.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: `product_id=${productId}`,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            showToast("success", "Produk ditambahkan ke keranjang");
            // Update cart count if element exists
            const cartCount = document.getElementById("cartCount");
            if (cartCount) {
              cartCount.textContent = data.cart_count;
            }
          } else {
            showToast(
              "danger",
              data.message || "Gagal menambahkan ke keranjang"
            );
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          showToast("danger", "Terjadi kesalahan");
        });
    });
  });

  // Comment functionality
  document.querySelectorAll(".comment-form").forEach((form) => {
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      const productId = this.getAttribute("data-product-id");
      const commentInput = this.querySelector('input[name="comment"]');
      const comment = commentInput.value.trim();
      const commentsContainer = document.getElementById(
        `comments-container-${productId}`
      );

      if (!comment) return;

      fetch("includes/add_comment.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: `product_id=${productId}&comment=${encodeURIComponent(comment)}`,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            // Add new comment to the top
            const commentHtml = `
                        <div class="comment mb-2">
                            <div class="d-flex align-items-center">
                                <img src="${data.user_photo}" class="rounded-circle me-2" width="30" height="30" alt="User">
                                <strong>${data.username}</strong>
                                <small class="text-muted ms-2">${data.time_ago}</small>
                            </div>
                            <p class="mb-0 ms-4 ps-3">${data.comment}</p>
                        </div>
                    `;
            commentsContainer.insertAdjacentHTML("afterbegin", commentHtml);
            commentInput.value = "";
          } else {
            showToast("danger", data.message || "Gagal menambahkan komentar");
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          showToast("danger", "Terjadi kesalahan");
        });
    });
  });

  // Load comments when comment section is shown
  document.querySelectorAll('[data-bs-toggle="collapse"]').forEach((button) => {
    button.addEventListener("click", function () {
      const target = this.getAttribute("data-bs-target");
      const productId = target.split("-")[1];
      const commentsContainer = document.getElementById(
        `comments-container-${productId}`
      );

      // Only load comments if not already loaded
      if (commentsContainer.children.length === 0) {
        fetch(`includes/get_comments.php?product_id=${productId}`)
          .then((response) => response.json())
          .then((data) => {
            if (data.success) {
              let commentsHtml = "";
              data.comments.forEach((comment) => {
                commentsHtml += `
                                    <div class="comment mb-2">
                                        <div class="d-flex align-items-center">
                                            <img src="${comment.foto_profil}" class="rounded-circle me-2" width="30" height="30" alt="User">
                                            <strong>${comment.username}</strong>
                                            <small class="text-muted ms-2">${comment.time_ago}</small>
                                        </div>
                                        <p class="mb-0 ms-4 ps-3">${comment.comment}</p>
                                    </div>
                                `;
              });
              commentsContainer.innerHTML = commentsHtml;
            } else {
              commentsContainer.innerHTML =
                '<p class="text-muted">Gagal memuat komentar</p>';
            }
          })
          .catch((error) => {
            console.error("Error:", error);
            commentsContainer.innerHTML =
              '<p class="text-muted">Terjadi kesalahan</p>';
          });
      }
    });
  });
});
