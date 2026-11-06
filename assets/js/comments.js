
// const commentCount = document.querySelector(".comment-count-" + postId);

// if(commentCount){
//     commentCount.textContent =
//         parseInt(commentCount.textContent) + 1;
// }


function submitComment(btn) {

    // FORM
    const form = btn.closest(".comment-form");

    // POST ID
    const postId =
        form.querySelector(".comment-post-id").value;

    // INPUT
    const input =
        form.querySelector(".comment-input");

    // COMMENT TEXT
    const comment = input.value.trim();

    // COMMENTS CONTAINER
    const commentsContainer =
        form.nextElementSibling;

    // VALIDATION
    if(comment === "") return;

    // AJAX REQUEST
    fetch("comments.php", {

        method: "POST",

        headers: {
            "Content-Type":
            "application/x-www-form-urlencoded"
        },

        body:
            "post_id=" +
            encodeURIComponent(postId)
            +
            "&comment=" +
            encodeURIComponent(comment)
    })

    .then(response => response.json())

    .then(data => {

        console.log(data);

        // ERROR
        if(data.error){

            alert(data.error);

            return;
        }

        // NEW COMMENT
        const newComment = `

        <div class="comment-card">

            <img
                src="includes/${data.file_name}"
                class="comment-avatar"
            >

            <div class="comment-content">

                <p>

                    <strong>

                        ${data.firstname}
                        ${data.lastname}

                    </strong>

                    ${data.comment}

                </p>

                <span class="comment-time">

                    ${data.time}

                </span>

            </div>

        </div>

        `;

        // ADD COMMENT
        commentsContainer.insertAdjacentHTML(
            "afterbegin",
            newComment
        );

        // CLEAR INPUT
        input.value = "";

    })

    .catch(error => {

        console.log(error);

    });

}

document.addEventListener("DOMContentLoaded", () => {

    const commentInputs =
        document.querySelectorAll(".comment-input");

    commentInputs.forEach(input => {

        input.addEventListener("keydown", function(e) {

            // PRESS ENTER
            if(e.key === "Enter") {

                e.preventDefault();

                // FIND BUTTON
                const form =
                    this.closest(".comment-form");

                const btn =
                    form.querySelector(".comment-btn");

                // SUBMIT COMMENT
                submitComment(btn);
            }

        });

    });

});

