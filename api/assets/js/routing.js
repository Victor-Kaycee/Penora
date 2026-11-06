
document.getElementById("logoutBtn").addEventListener("click", function(e){

    e.preventDefault();

    Swal.fire({

        title: 'Logout?',
        text: "Are you sure you want to logout?",
        icon: 'warning',

        showCancelButton: true,

        confirmButtonColor: '#eb2020',
        cancelButtonColor: '#6b7280',

        confirmButtonText: 'Yes, Logout',
        cancelButtonText: 'Cancel',

        background: '#fff',
        borderRadius: '20px',

        showClass: {
            popup: `
                animate__animated
                animate__fadeInDown
            `
        },

        hideClass: {
            popup: `
                animate__animated
                animate__fadeOutUp
            `
        }

    }).then((result) => {

        if(result.isConfirmed){

            Swal.fire({

                title: 'Logged Out',
                text: 'See you again 👋',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false

            });

            setTimeout(() => {

                window.location.href = "logout.php";

            }, 1500);

        }

    });

});