function toggleFollow(btn) {

    let form = btn.closest("form");
    let formData = new FormData(form);

    fetch("follow.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.text())
    .then(data => {

        if (data.trim() === "followed") {
            btn.innerText = "Following";
            btn.classList.remove("btn-primary");
            btn.classList.add("btn-secondary");
        }

        if (data.trim() === "unfollowed") {
            btn.innerText = "Follow";
            btn.classList.remove("btn-secondary");
            btn.classList.add("btn-primary");
        }
    });
}