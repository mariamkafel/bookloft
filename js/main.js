
window.addEventListener("scroll", () => {
    const elements = document.querySelectorAll(".fade-in-up");

    elements.forEach(element => {
        const rect = element.getBoundingClientRect();
        const elementTop = rect.top;
        if (elementTop < window.innerHeight && elementTop >= 0) {
            element.classList.add("show");
        } else {
            element.classList.remove("show");
        }
    });
});

window.addEventListener("scroll", () => {
    const elements = document.querySelectorAll(".fade-left");

    elements.forEach(element => {
        const rect = element.getBoundingClientRect();
        const elementTop = rect.top;

        if (elementTop < window.innerHeight && elementTop >= 0) {
            element.classList.add("show");
        } else {
            element.classList.remove("show");
        }
    });
});
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menu-toggle');
    const hamburger = document.querySelector('.hamburger');
    
    hamburger.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            menuToggle.checked = !menuToggle.checked;
        }
    });
    hamburger.setAttribute('tabindex', '0');
});
