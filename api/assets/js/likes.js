function handleLike(btn) {

    const form = btn.closest(".like-form");
    const postId = form.querySelector(".post-id").value;

    fetch("likes.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: "post_id=" + encodeURIComponent(postId)
    })
    .then(res => res.json())
    .then(data => {

        console.log(data);

        // update UI instantly
        const icon = btn.querySelector(".like-icon");
        const text = btn.querySelector(".like-text");
        const count = btn.querySelector(".like-count");

        count.textContent = data.likes;

        if (data.liked) {
            btn.classList.add("liked");
            icon.className = "ri-heart-fill like-icon";
            text.textContent = "Liked";
        } else {
            btn.classList.remove("liked");
            icon.className = "ri-heart-line like-icon";
            text.textContent = "Like";
        }

    });

    btn.classList.add("pop");
    setTimeout(() => btn.classList.remove("pop"), 300);
}