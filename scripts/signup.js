const form = document.getElementById("signup-form");
const fields = [
  { id: "first_name", error_id: "first-name-error" },
  { id: "last_name", error_id: "last-name-error" },
  { id: "email", error_id: "email-error" },
  { id: "password", error_id: "password-error" },
  { id: "business_name", error_id: "business-name-error" },
  { id: "address", error_id: "address-error" },
  { id: "mobile", error_id: "mobile-error" },
];

fields.forEach((field) => {
  document.getElementById(field.id).addEventListener("focus", () => {
    document.getElementById(field.error_id).textContent = "";
  });
});

form.addEventListener("submit", () => {
  fields.forEach((field) => {
    document.getElementById(field.error_id).textContent = "";
  });
});
