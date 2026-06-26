const email_input = document.getElementById("email");
const password_input = document.getElementById("password");
const login_form = document.getElementById("login-form");
const email_span = document.getElementById("email-error");
const password_span = document.getElementById("password-error");

email_input.addEventListener("focus", function () {
  email_span.textContent = "";
});

password_input.addEventListener("focus", function () {
  password_span.textContent = "";
});

login_form.addEventListener("submit", function () {
  email_span.textContent = "";
  password_span.textContent = "";
});


