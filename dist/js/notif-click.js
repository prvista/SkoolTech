function markNotificationAsRead(event, element) {
    event.preventDefault();

    const notificationId = element.getAttribute("data-id");
    const targetPage = element.getAttribute("href");

    fetch("mark_notification.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: `id=${notificationId}`
    })
    .then(response => {
        if (response.ok) {
            // Remove the notification from the dropdown
            const notificationItem = element.closest("p");
            notificationItem.remove();

            // Redirect to the target page
            window.location.href = targetPage;
        } else {
            alert("Failed to mark the notification as read.");
        }
    })
    .catch(error => {
        console.error("Error:", error);
    });
}
