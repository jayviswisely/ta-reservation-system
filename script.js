document.getElementById("login-form").addEventListener("submit", function(event) {
    const username = document.getElementById("username").value.trim();
    const password = document.getElementById("password").value.trim();

    if (username === "" || password === "") {
        alert("Please enter a username and password.");
        event.preventDefault(); // Prevent submission if fields are empty
    }
});

// before backend was implemented
/*document.getElementById("login-form").addEventListener("submit", function(event) {
    event.preventDefault(); // Prevent default form submission

    // Get form values
    const username = document.getElementById("username").value;
    const password = document.getElementById("password").value;
    const role = document.getElementById("role").value;

    // Simple validation (Replace with actual authentication later)
    if (username.trim() === "" || password.trim() === "") {
        alert("Please enter a username and password.");
        return;
    }

    // Simulating login by storing user role (later replaced with backend auth)
    localStorage.setItem("loggedInUser", JSON.stringify({ username, role }));

    // Redirect based on role
    switch (role) {
        case "student":
            window.location.href = "student.php";
            break;
        case "ta":
            window.location.href = "ta.php";
            break;
        case "admin":
            window.location.href = "admin.php";
            break;
        default:
            alert("Invalid role selected.");
    }
});*/
