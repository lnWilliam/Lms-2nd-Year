document.addEventListener('DOMContentLoaded', function () {
    const assignmentForm = document.getElementById('assignmentForm');

    if (assignmentForm) {
        assignmentForm.addEventListener('submit', function (event) {
            event.preventDefault();
            const formData = new FormData(assignmentForm);
            fetch('assets/handlers/assignment_handler.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Assignment created successfully!');
                        location.reload();
                    } else {
                        alert('Error creating assignment: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while creating the assignment.');
                });
        });
    }

    const assignmentPointsInput = document.getElementById('assignmentPoints');

    if (assignmentPointsInput) {
        assignmentPointsInput.addEventListener('input', function () {
            let value = parseInt(this.value);
            if (isNaN(value) || value < 0) {
                this.value = 0;
            } else if (value > 100) {
                this.value = 100;
            }
        });
    }

    const assignmentDueDateInput = document.getElementById('assignmentDueDate');

    if (assignmentDueDateInput) {
        const now = new Date();
        const localNow = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
        assignmentDueDateInput.min = localNow;
    }
    if (assignmentDueDateInput) {
        assignmentDueDateInput.addEventListener('input', function () {
            const selectedDate = new Date(this.value);
            const now = new Date();
            if (selectedDate < now) {
                alert('Due date cannot be in the past.');
                this.value = '';
            }
        });
    }
    });
