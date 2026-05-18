document.addEventListener('DOMContentLoaded', function () {
    const activityForm = document.getElementById('activityForm');

    if (activityForm) {
        activityForm.addEventListener('submit', function (event) {
            event.preventDefault();
            const formData = new FormData(activityForm);
            fetch('assets/handlers/activity_handler.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Activity created successfully!');
                        location.reload();
                    } else {
                        alert('Error creating activity: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while creating the activity.');
                });
        });
    }

    const activityPointsInput = document.getElementById('activityPoints');

    if (activityPointsInput) {
        activityPointsInput.addEventListener('input', function () {
            let value = parseInt(this.value);
            if (isNaN(value) || value < 0) {
                this.value = 0;
            } else if (value > 100) {
                this.value = 100;
            }
        });
    }

    const activityDueDateInput = document.getElementById('activityDueDate');

    if (activityDueDateInput) {
        const now = new Date();
        const localNow = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
        activityDueDateInput.min = localNow;
    }
    if (activityDueDateInput) {
        activityDueDateInput.addEventListener('input', function () {
            const selectedDate = new Date(this.value);
            const now = new Date();
            if (selectedDate < now) {
                alert('Due date cannot be in the past.');
                this.value = '';
            }
        });
    }
    });
