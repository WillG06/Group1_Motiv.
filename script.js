const paymentForm = document.getElementById("paymentForm");

paymentForm.addEventListener("submit", function (e) {
  e.preventDefault(); 

  alert("Payment successful! Thank you for your purchase.");

  paymentForm.reset(); 
});

