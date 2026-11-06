const togglePassword = document.getElementById('togglePassword');
const password = document.getElementById('password');

togglePassword.addEventListener('click', () => {

    const type = password.getAttribute('type') === 'password'
        ? 'text'
        : 'password';

    password.setAttribute('type', type);

    togglePassword.classList.toggle('ri-eye-fill');
    togglePassword.classList.toggle('ri-eye-off-fill');

});

const slides = document.querySelectorAll(".slide");
const lines = document.querySelectorAll(".line");

let currentSlide = 0;

function showSlide(index) {

    slides.forEach(slide => {
        slide.classList.remove("active");
    });

    lines.forEach(line => {
        line.classList.remove("active");
    });

    slides[index].classList.add("active");
    lines[index].classList.add("active");

    currentSlide = index;
}

// Click navigation
lines.forEach((line, index) => {
    line.addEventListener("click", () => {
        showSlide(index);
    });
});

// Auto slide
setInterval(() => {

    currentSlide++;

    if (currentSlide >= slides.length) {
        currentSlide = 0;
    }

    showSlide(currentSlide);

}, 5000);
