document.addEventListener('DOMContentLoaded', () => {
    // Hamburger menu toggle for mobile navigation
    const hamburger = document.querySelector('.hamburger');
    const navMenu = document.querySelector('.nav-menu');

    if (hamburger && navMenu) {
        hamburger.addEventListener('click', () => {
            hamburger.classList.toggle('active');
            navMenu.classList.toggle('active');
        });

        document.querySelectorAll('.nav-link').forEach(n => n.addEventListener('click', () => {
            if (hamburger.classList.contains('active')) {
                hamburger.classList.remove('active');
                navMenu.classList.remove('active');
            }
        }));
    }

    // Dropdown menu toggle for desktop
    const dropdown = document.querySelector('.dropdown');
    if (dropdown) {
        const dropdownToggle = dropdown.querySelector('.dropdown-toggle');
        const dropdownMenu = dropdown.querySelector('.dropdown-menu');

        // This handles hover on desktop
        if (window.innerWidth > 768) {
            dropdown.addEventListener('mouseenter', () => {
                dropdownMenu.style.opacity = '1';
                dropdownMenu.style.visibility = 'visible';
                dropdownMenu.style.transform = 'translateY(0)';
            });
            dropdown.addEventListener('mouseleave', () => {
                dropdownMenu.style.opacity = '0';
                dropdownMenu.style.visibility = 'hidden';
                dropdownMenu.style.transform = 'translateY(-10px)';
            });
        } else {
            // This handles click on mobile
            dropdownToggle.addEventListener('click', (e) => {
                e.preventDefault();
                const isExpanded = dropdownMenu.style.display === 'block';
                dropdownMenu.style.display = isExpanded ? 'none' : 'block';
            });
        }
    }

    // Close alerts
    const alertCloseButtons = document.querySelectorAll('.alert .close-btn');
    alertCloseButtons.forEach(button => {
        button.addEventListener('click', () => {
            button.parentElement.style.display = 'none';
        });
    });
});
