$(document).ready(function () {
  // Inisialisasi token CSRF
  const csrfToken =
    $('meta[name="csrf-token"]').attr("content") ||
    $('[name="csrf_token"]').val();

  // Fungsi untuk menampilkan loading
  function tampilkanLoading(elemen) {
    elemen.prop("disabled", true);
    const htmlAsli = elemen.html();
    elemen.data("html-asli", htmlAsli);
    elemen.html(
      '<span class="spinner-border spinner-border-sm" role="status"></span> Memproses...'
    );
  }

  // Fungsi untuk menyembunyikan loading
  function sembunyikanLoading(elemen) {
    elemen.prop("disabled", false);
    const htmlAsli = elemen.data("html-asli");
    if (htmlAsli) {
      elemen.html(htmlAsli);
    }
  }

  // Fungsi untuk menampilkan notifikasi
  function tampilkanNotifikasi(tipe, pesan) {
    const notifikasi = `<div class="alert alert-${tipe} alert-dismissible fade show" role="alert">
      ${pesan}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>`;

    $("#notifikasi-container").append(notifikasi);

    // Hilangkan notifikasi setelah 5 detik
    setTimeout(() => {
      $(".alert").alert("close");
    }, 5000);
  }

  // Like/Unlike produk
  $(".btn-like").click(function () {
    const tombol = $(this);
    const productId = tombol.data("product-id");
    const isLiked = tombol.hasClass("text-danger");

    tampilkanLoading(tombol);

    $.ajax({
      url: "api/like_product.php",
      method: "POST",
      data: {
        product_id: productId,
        action: isLiked ? "unlike" : "like",
        csrf_token: csrfToken,
      },
      success: function (response) {
        if (response.success) {
          const likeCount = parseInt(tombol.find(".like-count").text());
          tombol
            .find(".like-count")
            .text(isLiked ? likeCount - 1 : likeCount + 1);
          tombol.toggleClass("text-danger text-secondary");
        } else {
          tampilkanNotifikasi(
            "danger",
            response.message || "Gagal memproses like"
          );
        }
      },
      error: function (xhr, status, error) {
        tampilkanNotifikasi("danger", "Terjadi kesalahan saat memproses like");
        console.error(error);
      },
      complete: function () {
        sembunyikanLoading(tombol);
      },
    });
  });

  // Load komentar saat section dibuka
  $(".btn-comment").click(function () {
    const tombol = $(this);
    const productId = tombol.data("product-id");
    const commentsContainer = $("#comments-container-" + productId);

    if (commentsContainer.children().length === 0) {
      tampilkanLoading(tombol);

      $.ajax({
        url: "api/get_comments.php?product_id=" + productId,
        method: "GET",
        success: function (data) {
          if (data.success) {
            commentsContainer.html(data.html);
          } else {
            commentsContainer.html(
              '<div class="text-muted">Gagal memuat komentar</div>'
            );
            if (data.message) {
              tampilkanNotifikasi("warning", data.message);
            }
          }
        },
        error: function () {
          commentsContainer.html(
            '<div class="text-muted">Gagal memuat komentar</div>'
          );
          tampilkanNotifikasi(
            "danger",
            "Terjadi kesalahan saat memuat komentar"
          );
        },
        complete: function () {
          sembunyikanLoading(tombol);
        },
      });
    }
  });

  // Submit komentar
  $(".comment-form").submit(function (e) {
    e.preventDefault();
    const form = $(this);
    const productId = form.data("product-id");
    const commentText = form.find('[name="comment"]').val().trim();
    const tombolSubmit = form.find('[type="submit"]');

    if (commentText === "") {
      tampilkanNotifikasi("warning", "Komentar tidak boleh kosong");
      return;
    }

    tampilkanLoading(tombolSubmit);

    $.ajax({
      url: "api/post_comment.php",
      method: "POST",
      data: {
        product_id: productId,
        comment: commentText,
        csrf_token: form.find('[name="csrf_token"]').val(),
      },
      success: function (response) {
        if (response.success) {
          form.find('[name="comment"]').val("");
          $("#comments-container-" + productId).prepend(
            `<div class="comment mb-2">
              <div class="d-flex align-items-center">
                <img src="${
                  response.foto_profil || "assets/images/default-profile.png"
                }" 
                     class="rounded-circle me-2" width="30" height="30" alt="Profil">
                <strong>${response.username}</strong>
                <small class="text-muted ms-2">Baru saja</small>
              </div>
              <p class="mb-0 ms-4 ps-3">${response.comment}</p>
            </div>`
          );
        } else {
          tampilkanNotifikasi(
            "danger",
            response.message || "Gagal mengirim komentar"
          );
        }
      },
      error: function () {
        tampilkanNotifikasi(
          "danger",
          "Terjadi kesalahan saat mengirim komentar"
        );
      },
      complete: function () {
        sembunyikanLoading(tombolSubmit);
      },
    });
  });

  // Tambah ke keranjang
  $(".btn-add-to-cart").click(function () {
    const tombol = $(this);
    const productId = tombol.data("product-id");

    console.log("Mencoba menambahkan product ID:", productId); // Debug
    tampilkanLoading(tombol);

    $.ajax({
      url: "produk/tambah.php",
      method: "POST",
      data: {
        product_id: productId,
        csrf_token: csrfToken,
      },
      success: function (response) {
        console.log("Response:", response); // Debug
        if (response.success) {
          tampilkanNotifikasi(
            "success",
            "Produk berhasil ditambahkan ke keranjang"
          );
          const cartCount = $("#cartCount");
          if (cartCount.length) {
            cartCount.text(response.cart_count).removeClass("d-none");
          }
        } else {
          tampilkanNotifikasi(
            "danger",
            response.message || "Gagal menambahkan ke keranjang"
          );
        }
      },
      error: function (xhr, status, error) {
        console.error("Error:", error); // Debug
        tampilkanNotifikasi(
          "danger",
          "Terjadi kesalahan saat menambahkan ke keranjang"
        );
      },
      complete: function () {
        sembunyikanLoading(tombol);
      },
    });
  });

  // Konfirmasi hapus produk
  let productToDelete = null;
  $(".delete-product").click(function () {
    productToDelete = $(this).data("id");
    $("#deleteModal").modal("show");
  });

  $("#confirmDelete").click(function () {
    if (!productToDelete) return;

    const tombol = $(this);
    tampilkanLoading(tombol);

    $.ajax({
      url: "produk/hapus.php",
      method: "POST",
      data: {
        product_id: productToDelete,
        csrf_token: csrfToken,
      },
      success: function (response) {
        if (response.success) {
          tampilkanNotifikasi("success", "Produk berhasil dihapus");
          setTimeout(() => location.reload(), 1000);
        } else {
          tampilkanNotifikasi(
            "danger",
            response.message || "Gagal menghapus produk"
          );
          $("#deleteModal").modal("hide");
        }
      },
      error: function () {
        tampilkanNotifikasi(
          "danger",
          "Terjadi kesalahan saat menghapus produk"
        );
        $("#deleteModal").modal("hide");
      },
      complete: function () {
        sembunyikanLoading(tombol);
      },
    });
  });
});
$(".btn-like").click(function () {
  const productId = $(this).data("product-id");
  $.post(
    "api/like.php",
    { product_id: productId, csrf_token: csrfToken },
    function (response) {
      // Update tampilan like
    }
  );
});
$("#confirmDelete").click(function () {
  const productId = $(this).data("id");
  $.post(
    "api/delete_product.php",
    { id: productId, csrf_token: csrfToken },
    function () {
      location.reload();
    }
  );
});
$.post(
  "api/like.php",
  {
    product_id: productId,
    csrf_token: "YOUR_CSRF_TOKEN",
  },
  function (response) {
    if (response.success) {
      // Update UI
      $(".like-count").text(response.like_count);
    }
  }
);
