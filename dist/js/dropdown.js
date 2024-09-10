// Select the dropdown toggle (the "Tasks" link)
const dropdownToggle = document.querySelectorAll(".dropdown-toggle");

dropdownToggle.forEach(toggle => {
    toggle.addEventListener("click", function(e) {
        e.preventDefault(); // Prevent default link behavior

        // Find the parent <li> and toggle the 'open' class
        const parentLi = this.parentElement;
        parentLi.classList.toggle("open");
    });
});
