<?php
    session_start();

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Replace these with your actual authentication logic

    $entered_username = $_POST["username"];
    $entered_password = $_POST["password"];

    // Database connection (replace with your database details)
    $server = "localhost";
    $username = "root"; // Default username for XAMPP MySQL
    $password = "";     // Default password for XAMPP MySQL (empty by default)
    $database = "itb"; // Replace with your database name

    // Create a connection
    $conn = new mysqli($server, $username, $password, $database);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // TODO FIX FOR LOGIN BYPASS
    $stmt = $conn->prepare("SELECT * FROM logininfo WHERE username = ? AND password = ? AND failed_try < 5");
    $stmt->bind_param("ss", $entered_username, $entered_password);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    if ($result->num_rows > 0) {
        // Authentication successful
        $_SESSION["authenticated"] = true;
        $_SESSION['user_id'] = $entered_username;

        $stmt = $conn->prepare("UPDATE logininfo SET failed_try = 0 WHERE Username = ?");
        $stmt->bind_param("s", $entered_username);
        $stmt->execute();
        $stmt->close();

        header("Location: ../../public/dashboard.php");
    } else {
        // Authentication failed
        $_SESSION["authenticated"] = false;
        $_SESSION["error"] = "Invalid username or password.";

        // TODO FIX Brute Force
        $sql = "SELECT * FROM logininfo WHERE username = '$entered_username'";
        $result = mysqli_query($conn, $sql);

        $row = mysqli_fetch_assoc($result);

        // Extract and convert the 'failed_try' column to an integer
        $failedTry = $row['failed_try'];

        if ($failedTry<5){
            $tried = $failedTry+1;
            
            $sql = $conn->prepare("UPDATE logininfo SET failed_try = ? WHERE Username = ?");
            $sql->bind_param("ss", $tried, $entered_username);
            $sql->execute();
            $sql->close();
            
            header("Location: ../../public/login.php");
        }else{
            echo "You have tried 5 failed attempts. Contact your RM to resolve your issues.";
        }
    }
    $conn->close();
}else {
    // Handle unauthorized access or other HTTP methods
    header("HTTP/1.1 401 Unauthorized");
    exit();
}
?>
