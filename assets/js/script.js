const sliderEl = document.getElementById("slider");
const dotsEl = document.getElementById("dots");
const slideEls = [...sliderEl.querySelectorAll(".slide")];
const transitions = slideEls.map(el => el.dataset.transition || "fade");

let current = 0;
let isAnimating = false;
let autoTimer;

// Build dots from existing slides
slideEls.forEach((_, i) => {
  const dot = document.createElement("button");
  dot.className = "dot";
  dot.setAttribute("aria-label", `Go to slide ${i + 1}`);
  dot.addEventListener("click", () => { goTo(i); resetAuto(); });
  dotsEl.appendChild(dot);
});
const dotEls = [...dotsEl.querySelectorAll(".dot")];

function setActiveDot(i) {
  dotEls.forEach((d, idx) => d.classList.toggle("active", idx === i));
}

function animateIn(slide, type) {
  const card = slide.querySelector(".slide-card");
  const cat = slide.querySelector(".slide-cat");
  const title = slide.querySelector(".slide-title");
  const author = slide.querySelector(".slide-author");

  gsap.set(slide, { opacity: 1 });

  switch (type) {
    case "slide":
      gsap.fromTo(slide, { xPercent: 100 }, { xPercent: 0, duration: 0.9, ease: "power3.out" });
      break;
    case "zoom":
      gsap.fromTo(slide, { scale: 1.15, opacity: 0 }, { scale: 1, opacity: 1, duration: 1.1, ease: "power2.out" });
      break;
    case "reveal":
      gsap.fromTo(slide, { clipPath: "inset(0 0 0 100%)" }, { clipPath: "inset(0 0 0 0%)", duration: 1, ease: "power3.inOut" });
      break;
    case "fade":
    default:
      gsap.fromTo(slide, { opacity: 0 }, { opacity: 1, duration: 0.8, ease: "power2.out" });
  }

  gsap.fromTo(card,
    { y: 60, opacity: 0 },
    { y: 0, opacity: 1, duration: 0.8, ease: "power3.out", delay: 0.25 }
  );
  gsap.fromTo([cat, title, author],
    { y: 30, opacity: 0 },
    { y: 0, opacity: 1, duration: 0.7, stagger: 0.12, ease: "power2.out", delay: 0.45 }
  );
}

function animateOut(slide, type, dir = 1) {
  return new Promise(resolve => {
    switch (type) {
      case "slide":
        gsap.to(slide, { xPercent: -100 * dir, duration: 0.8, ease: "power3.in", onComplete: () => { gsap.set(slide, { xPercent: 0, opacity: 0 }); resolve(); } });
        break;
      case "zoom":
        gsap.to(slide, { scale: 0.9, opacity: 0, duration: 0.6, ease: "power2.in", onComplete: () => { gsap.set(slide, { scale: 1 }); resolve(); } });
        break;
      case "reveal":
        gsap.to(slide, { clipPath: "inset(0 100% 0 0)", duration: 0.8, ease: "power3.inOut", onComplete: () => { gsap.set(slide, { clipPath: "inset(0 0 0 0)", opacity: 0 }); resolve(); } });
        break;
      default:
        gsap.to(slide, { opacity: 0, duration: 0.5, ease: "power2.in", onComplete: resolve });
    }
  });
}

async function goTo(index, dir = 1) {
  if (isAnimating || index === current) return;
  isAnimating = true;
  const prev = slideEls[current];
  const next = slideEls[index];
  const transition = transitions[index];

  next.classList.add("active");
  next.style.zIndex = 2;
  prev.style.zIndex = 1;

  animateIn(next, transition);
  await animateOut(prev, transition, dir);

  prev.classList.remove("active");
  current = index;
  setActiveDot(current);
  isAnimating = false;
}

function next() { goTo((current + 1) % slideEls.length, 1); }
function prev() { goTo((current - 1 + slideEls.length) % slideEls.length, -1); }

document.querySelector(".next").addEventListener("click", () => { next(); resetAuto(); });
document.querySelector(".prev").addEventListener("click", () => { prev(); resetAuto(); });

// Touch swipe
let startX = 0;
sliderEl.addEventListener("touchstart", e => startX = e.touches[0].clientX, { passive: true });
sliderEl.addEventListener("touchend", e => {
  const dx = e.changedTouches[0].clientX - startX;
  if (Math.abs(dx) > 50) { dx < 0 ? next() : prev(); resetAuto(); }
});

// Init
slideEls[0].classList.add("active");
gsap.set(slideEls[0], { opacity: 1 });
animateIn(slideEls[0], "fade");
setActiveDot(0);

function startAuto() { autoTimer = setInterval(next, 6000); }
function resetAuto() { clearInterval(autoTimer); startAuto(); }
startAuto();



function toggleDropdown(){
    document
        .getElementById("dropdownMenu")
        .classList.toggle("show");
}

window.addEventListener("click", function(e){

    const profile = document.querySelector(".profile-dropdown");

    if(!profile.contains(e.target)){
        document
            .getElementById("dropdownMenu")
            .classList.remove("show");
    }

});




