// ACTIVITY TEXT (PROFESSIONAL)
const activities = [
  "Searching for lost items...",
  "Analyzing submitted reports...",
  "User communication in progress...",
  "Verifying report data...",
  "Match identified successfully"
];

let i = 0;

setInterval(() => {
  const box = document.getElementById("activityBox");
  if (!box) return;

  box.style.opacity = 0;

  setTimeout(() => {
    i = (i + 1) % activities.length;
    box.textContent = activities[i];
    box.style.opacity = 1;
  }, 300);

}, 2500);


// SLIDER
let slides = document.querySelectorAll(".slide");
let index = 0;

setInterval(() => {
  if (slides.length === 0) return;

  slides[index].classList.remove("active");
  index = (index + 1) % slides.length;
  slides[index].classList.add("active");
}, 4000);