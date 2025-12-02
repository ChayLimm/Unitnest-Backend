// Mobile menu toggle
const menuToggle = document.getElementById('menu-toggle');
const mobileMenu = document.getElementById('mobile-menu');

menuToggle.addEventListener('click', () => {
    mobileMenu.classList.toggle('hidden');
});

// Navbar scroll effect
const navbar = document.getElementById('navbar');
const navText = document.querySelectorAll('.navText');

window.addEventListener('scroll', () => {
    if (window.scrollY > 50) {
        navbar.classList.add('nav-scrolled');
        navText.forEach(navtext=>{
            navtext.classList.remove('nav-not-scroll');
        })
    } else {
        navbar.classList.remove('nav-scrolled');
        navText.forEach(navtext=>{
            navtext.classList.add('nav-not-scroll');
        })
    }
});

// Smooth scroll for navigation links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        
        const targetId = this.getAttribute('href');
        if (targetId === '#') return;
        
        const targetElement = document.querySelector(targetId);
        if (targetElement) {
            window.scrollTo({
                top: targetElement.offsetTop - 80,
                behavior: 'smooth'
            });
            
            // Close mobile menu if open
            mobileMenu.classList.add('hidden');
        }
    });
});

// Carousel functionality
const carouselInner = document.getElementById('carousel-inner');
const indicators = document.querySelectorAll('.carousel-indicator');
let currentIndex = 0;

function updateCarousel() {
    const itemWidth = document.querySelector('.carousel-item').offsetWidth;
    carouselInner.style.transform = `translateX(-${currentIndex * itemWidth}px)`;
    
    // Update indicators
    indicators.forEach((indicator, index) => {
        if (index === currentIndex) {
            indicator.classList.add('active');
        } else {
            indicator.classList.remove('active');
        }
    });
}

// Set up indicator clicks
indicators.forEach((indicator, index) => {
    indicator.addEventListener('click', () => {
        currentIndex = index;
        updateCarousel();
    });
});

// Auto-advance carousel
setInterval(() => {
    currentIndex = (currentIndex + 1) % indicators.length;
    updateCarousel();
}, 5000);

// Initialize carousel
updateCarousel();
//