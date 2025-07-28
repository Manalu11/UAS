// Fungsi untuk toggle password visibility
document.addEventListener("DOMContentLoaded", function () {
  // Toggle show/hide password
  document.querySelectorAll(".toggle-password").forEach(function (button) {
    button.addEventListener("click", function () {
      const passwordInput = this.closest(".input-group").querySelector("input");
      const icon = this.querySelector("i");

      if (passwordInput.type === "password") {
        passwordInput.type = "text";
        icon.classList.replace("fa-eye", "fa-eye-slash");
      } else {
        passwordInput.type = "password";
        icon.classList.replace("fa-eye-slash", "fa-eye");
      }
    });
  });

  // Validasi form sebelum submit
  const form = document.querySelector(".auth-form");
  if (form) {
    form.addEventListener("submit", function (e) {
      const password = document.getElementById("password").value;
      const confirmPassword = document.getElementById("confirm_password").value;

      if (password !== confirmPassword) {
        e.preventDefault();
        alert("Konfirmasi password tidak sama dengan password");
        return;
      }

      // Validasi tambahan bisa ditambahkan di sini
    });
  }
});
