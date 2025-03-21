const dropdownToggle = document.querySelectorAll(".dropdown-toggle");

dropdownToggle.forEach(toggle => {
    toggle.addEventListener("click", function(e) {
        e.preventDefault(); 

        const parentLi = this.parentElement;
        parentLi.classList.toggle("open");
    });
});



