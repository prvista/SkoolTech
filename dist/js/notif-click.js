function markNotificationAsRead(event, notifId, targetPage) {
    event.preventDefault(); // Prevent the default link redirection

    console.log("notifId:", notifId); // Debugging line to check if notifId is being passed

    // Prepare data to send
    const requestData = { id: notifId };

    // Log the request data to verify it's being sent correctly
    console.log("Request Data:", requestData);

    // AJAX request using fetch
    fetch('mark_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(requestData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mark the notification as read in the UI
            const notifElement = document.querySelector(`#notif-${notifId}`);
            if (notifElement) {
                notifElement.classList.remove('unread');
                notifElement.classList.add('read');
            }

            // Redirect to the target page after marking as read
            window.location.href = targetPage;
        } else {
            console.error('Error:', data.message);
            alert('Failed to mark notification as read.');
        }
    })
    .catch(error => {
        console.error('AJAX Error:', error);
        alert('An error occurred. Please try again.');
    });
}