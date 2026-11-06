function toggleMenu() { document.getElementById("menu").classList.toggle("active"); } function toggleDark() { document.body.classList.toggle("dark"); }


document.addEventListener("DOMContentLoaded", () => {
  const sidebar = document.querySelector(".sidebar");
  const toggle = document.querySelector("#menuToggle");
  const overlay = document.querySelector(".sidebar-overlay");

  toggle.addEventListener("click", () => {
    sidebar.classList.toggle("show");
    overlay.classList.toggle("show");
  });

  overlay.addEventListener("click", () => {
    sidebar.classList.remove("show");
    overlay.classList.remove("show");
  });
});

document.getElementById("logoutBtn").addEventListener("click", function(e){

    e.preventDefault();

    Swal.fire({

        title: "Are you sure?",
        text: "You want to logout from your account?",
        icon: "warning",

        showCancelButton: true,

        confirmButtonColor: "#eb2020",
        cancelButtonColor: "#6b7280",

        confirmButtonText: "Yes, Logout",

        showClass: {
            popup: 'animate__animated animate__zoomIn'
        },

        hideClass: {
            popup: 'animate__animated animate__zoomOut'
        }

    }).then((result) => {

        if (result.isConfirmed) {

            Swal.fire({

                title: "Logged Out!",
                text: "See you again 👋",
                icon: "success",

                showConfirmButton: false,
                timer: 1500,

                showClass: {
                    popup: 'animate__animated animate__zoomIn'
                },

                hideClass: {
                    popup: 'animate__animated animate__zoomOut'
                }

            });

            setTimeout(() => {

                window.location.href = "logout.php";

            }, 1500);

        }

    });

});
