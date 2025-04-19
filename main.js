function searchFlights() {
    const from = document.getElementById("from").value;
    const to = document.getElementById("to").value;
  
    if (from === "" || to === "") {
      alert("Please enter both origin and destination.");
      return;
    }
  
    alert(`Searching flights from ${from} to ${to}...`);
  }
  
  // Optional: highlight active nav link
  document.querySelectorAll(".nav-link").forEach((btn) => {
    btn.addEventListener("click", function () {
      document.querySelectorAll(".nav-link").forEach((b) => b.classList.remove("active"));
      this.classList.add("active");
    });
  });
  
  // Optional: tab switcher logic
  document.querySelectorAll(".tab-button").forEach((tab) => {
    tab.addEventListener("click", function () {
      document.querySelectorAll(".tab-button").forEach((t) => t.classList.remove("active-tab"));
      this.classList.add("active-tab");
    });
  });
  