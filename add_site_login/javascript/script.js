// Function to switch between forms
function showForm(formId) {
    document.querySelectorAll('.form-section').forEach(form => form.classList.remove('active'));
    document.getElementById(formId).classList.add('active');

    // Update active class for toggle buttons
    document.querySelectorAll('.toggle-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelector(`[onclick="showForm('${formId}')"]`).classList.add('active');
}


// Handle OK button click for alert boxes
document.addEventListener('DOMContentLoaded', function() {
    const okButton = document.querySelector('.alert-ok-btn');
    if (okButton) {
        okButton.addEventListener('click', function() {
            document.querySelector('.alert-box').style.display = 'none';
            document.querySelector('.overlay').style.display = 'none';
        });
    }
});