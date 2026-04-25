// ACTIVITY
const activities = [
  "Searching for lost items...",
  "Analyzing reports...",
  "Users communicating...",
  "Verifying data...",
  "Match found successfully"
];

let i = 0;

setInterval(() => {
  const el = document.getElementById("activityText");
  if (!el) return;

  i = (i + 1) % activities.length;
  el.textContent = activities[i];
}, 2500);


// SCROLL ANIMATION
const observer = new IntersectionObserver(entries => {
  entries.forEach(entry => {
    if(entry.isIntersecting){
      entry.target.classList.add("active");
    }
  });
});

document.querySelectorAll(".reveal").forEach(el => observer.observe(el));


// SLIDER
let slides = document.querySelectorAll(".slide");
let index = 0;

setInterval(() => {
  slides[index].classList.remove("active");
  index = (index + 1) % slides.length;
  slides[index].classList.add("active");
}, 4000);