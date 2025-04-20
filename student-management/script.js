document.getElementById("studentForm").addEventListener("submit", function(event) {
    // Prevent the default form submission
    event.preventDefault();
    
    // Get form data
    let name = document.getElementById("name").value;
    let marks = document.getElementById("marks").value;
  
    // Clear the form fields
    document.getElementById("name").value = '';
    document.getElementById("marks").value = '';
  
    // Optionally, show a success message
    alert("Student added: " + name + " - " + marks);
  
    // To dynamically update the student list, you would typically use an AJAX request here.
    // Since we're not implementing AJAX, the page will reload with updated list after form submission.
  });
  