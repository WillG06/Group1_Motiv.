document.addEventListener('DOMContentLoaded', function() {
    const dropdowns = document.querySelectorAll('nav ul li.dropdown');
    
    dropdowns.forEach(dropdown => {
        const dropbtn = dropdown.querySelector('.dropbtn');
        const dropdownContent = dropdown.querySelector('.dropdown-content');
        
        dropdown.addEventListener('mouseenter', function() {
            dropdownContent.style.display = 'block';
        });
        
        dropdown.addEventListener('mouseleave', function() {
            dropdownContent.style.display = 'none';
        });
        
        dropdownContent.addEventListener('mouseenter', function() {
            this.style.display = 'block';
        });
        
        dropdownContent.addEventListener('mouseleave', function() {
            this.style.display = 'none';
        });
    });
});
