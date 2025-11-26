document.addEventListener('DOMContentLoaded', function() {
    const servicesScroll = document.querySelector('.services-scroll');
    const scrollLeftBtn = document.querySelector('.scroll-left');
    const scrollRightBtn = document.querySelector('.scroll-right');
    
    if (servicesScroll && scrollLeftBtn && scrollRightBtn) {
        const scrollAmount = 350;
        
        scrollRightBtn.addEventListener('click', function() {
            servicesScroll.scrollBy({
                left: scrollAmount,
                behavior: 'smooth'
            });
        });
        
        scrollLeftBtn.addEventListener('click', function() {
            servicesScroll.scrollBy({
                left: -scrollAmount,
                behavior: 'smooth'
            });
        });
        
        function updateScrollButtons() {
            const maxScrollLeft = servicesScroll.scrollWidth - servicesScroll.clientWidth;
            
            if (servicesScroll.scrollLeft <= 10) {
                scrollLeftBtn.style.opacity = '0.5';
                scrollLeftBtn.style.cursor = 'default';
            } else {
                scrollLeftBtn.style.opacity = '1';
                scrollLeftBtn.style.cursor = 'pointer';
            }
            
            if (servicesScroll.scrollLeft >= maxScrollLeft - 10) {
                scrollRightBtn.style.opacity = '0.5';
                scrollRightBtn.style.cursor = 'default';
            } else {
                scrollRightBtn.style.opacity = '1';
                scrollRightBtn.style.cursor = 'pointer';
            }
        }
        
        servicesScroll.addEventListener('scroll', updateScrollButtons);
        updateScrollButtons(); // Initial check
    }
});
