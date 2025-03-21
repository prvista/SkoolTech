document.addEventListener("DOMContentLoaded", function () {
    const notifToggles = document.querySelectorAll(".notif-toggle");

    notifToggles.forEach(toggle => {
        toggle.addEventListener("click", function (e) {
            e.preventDefault(); // Prevent default anchor behavior

            const parentDropdown = this.parentElement;

            // Close other dropdowns
            document.querySelectorAll(".notif-dropdown.open").forEach(dropdown => {
                if (dropdown !== parentDropdown) {
                    dropdown.classList.remove("open");
                }
            });

            // Toggle the 'open' class on the clicked dropdown
            parentDropdown.classList.toggle("open");
        });
    });

    // Close dropdown when clicking outside
    document.addEventListener("click", function (e) {
        if (!e.target.closest(".notif-dropdown")) {
            document.querySelectorAll(".notif-dropdown.open").forEach(dropdown => {
                dropdown.classList.remove("open");
            });
        }
    });
});