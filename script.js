const paymentForm = document.getElementById("paymentForm");

paymentForm.addEventListener("submit", function (e) {
  e.preventDefault(); // Stop page reload

  alert("Payment successful! Thank you for your purchase.");

  paymentForm.reset(); // Clear the form
});
