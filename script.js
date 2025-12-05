const paymentForm = document.getElementById("paymentForm");
const cardNumber = document.getElementById("cardNumber");
const cvv = document.getElementById("cvv");

cardNumber.addEventListener("input", function () {
    this.value = this.value.slice(0, 16);
});

cvv.addEventListener("input", function () {
    this.value = this.value.slice(0, 3);
});


paymentForm.addEventListener("submit", function (e) {

    if (cardNumber.value.length !== 16) {
        alert("Card number must be exactly 16 digits.");
        e.preventDefault();
        return;
    }

    if (cvv.value.length !== 3) {
        alert("CVV must be exactly 3 digits.");
        e.preventDefault();
        return;
    }

    alert("Payment successful! Thank you for your purchase.");
    paymentForm.reset();
});