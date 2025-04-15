document.addEventListener("DOMContentLoaded", function () {
  const button = document.querySelector(".custom-button");
  const form = document.querySelector("form");
  const nama = document.getElementById("nama");
  const email = document.getElementById("email");
  const pesan = document.getElementById("pesan");
  const counter = document.getElementById("counter");

  // Event click untuk tombol kirim
  button.addEventListener("click", function () {
    if (nama.value && email.value && pesan.value) {
      alert(`Terima kasih, ${nama.value}! Pesanmu telah dikirim.`);
      form.reset();
      counter.textContent = `${pesan.getAttribute("maxlength")} karakter tersisa`; // reset counter juga
    } else {
      alert("Mohon isi semua kolom sebelum mengirim pesan.");
    }
  });

  // Karakter counter textarea
  pesan.addEventListener("input", () => {
    const maxLength = pesan.getAttribute("maxlength");
    const currentLength = pesan.value.length;
    counter.textContent = `${maxLength - currentLength} karakter tersisa`;
  });
});