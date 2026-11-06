const ctx = document.getElementById('patientChart').getContext('2d');
let deleteId = null;


new Chart(ctx, {
  type: 'doughnut',
  data: {
    labels: ["Reader Distribution", "Top Performing Article", "Reading Trends"],
    datasets: [{
      data: [35, 45, 20],
      backgroundColor: [
        "#e5e7eb", // gray
        "#eb2020", // purple
        "#5a0505"  // orange
      ],
      borderWidth: 0,
      hoverOffset: 6
    }]
  },
  options: {
    cutout: "70%",
    maintainAspectRatio: true, // 👈 keeps it circular

    animation: {
      animateRotate: true,
      animateScale: true,
      duration: 1200,
      easing: "easeOutQuart"
    },

    plugins: {
      legend: {
        display: false
      }
    }
  }
});
