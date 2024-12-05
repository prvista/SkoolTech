<?php
session_start();

// Check if the user is logged in and is a professor
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'professor') {
    header("Location: login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "skooltech";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get all students
$sql_students = "SELECT * FROM students";
$result_students = $conn->query($sql_students);

// Handle form submission for adding, editing, and deleting students
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Add new student
    if (isset($_POST['add_student'])) {
        $student_number = $_POST['student_number'];
        $username = $_POST['username'];
        $name = $_POST['name'];
        $password = $_POST['password'];  // New password field

        // Hash the password before storing it
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $sql_add = "INSERT INTO students (student_number, username, name, password) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql_add);
        $stmt->bind_param("ssss", $student_number, $username, $name, $hashed_password);
        $stmt->execute();

        // Show success notification on the same page
        $status = "success";
        $message = "Student added successfully.";
    }

    // Edit student (handled via another form)
    if (isset($_POST['edit_student'])) {
        $student_id = $_POST['student_id'];
        $student_number = $_POST['student_number'];
        $username = $_POST['username'];
        $name = $_POST['name'];
        $password = $_POST['password'];  // Password for editing

        // Prepare query to update student details
        if (!empty($password)) {
            // Hash the new password before updating
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql_edit = "UPDATE students SET student_number = ?, username = ?, name = ?, password = ? WHERE id = ?";
            $stmt = $conn->prepare($sql_edit);
            $stmt->bind_param("ssssi", $student_number, $username, $name, $hashed_password, $student_id);
        } else {
            // If no password is provided, don't update the password
            $sql_edit = "UPDATE students SET student_number = ?, username = ?, name = ? WHERE id = ?";
            $stmt = $conn->prepare($sql_edit);
            $stmt->bind_param("sssi", $student_number, $username, $name, $student_id);
        }

        if ($stmt->execute()) {
            // Show success notification
            $status = "success";
            $message = "Student updated successfully.";
        } else {
            $status = "error";
            $message = "Error updating student.";
        }
    }
}


// Delete student (triggered via GET request)
if (isset($_GET['delete_id'])) {
    $student_id = $_GET['delete_id'];

    $sql_delete = "DELETE FROM students WHERE id = ?";
    $stmt = $conn->prepare($sql_delete);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();

    // Show success notification on the same page
    $status = "success";
    $message = "Student deleted successfully.";
}

// Fetch professor's details
$stmt = $conn->prepare("SELECT name FROM professors WHERE username = ?");
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$result = $stmt->get_result();
$professor = $result->num_rows > 0 ? $result->fetch_assoc() : null;

// Fetch status and message from query parameters
$status = isset($status) ? $status : '';
$message = isset($message) ? $message : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SkoolTech</title>
    <link rel="stylesheet" href="./dist/scss/main.min.css">
    <link rel="icon" href="./dist/img/skooltech-icon.png">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <style>
        /* Style for the floating edit modal */
        #editStudentModal, #addStudentModal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            padding: 20px;
            background-color: #fff;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            width: 40rem;
            height:30rem;
            border-radius: 8px;
            font-family: "Poppins", sans-serif;
            transition: all 0.3s ease-in-out;
        }

        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        /* Styling the buttons */
        button {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: 0.05s ease;
            font-family: "Poppins", "sans-serif";
            font-size: 1rem;
            transition: 0.3s ease;
        }

        button:hover {
            background-color: #0068d9;
            transition: 0.3s ease;
        }

        /* Style the form inputs */
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 8px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 1rem;
        }

        /* Input focus style */
        input:focus {
            outline: none;
            border-color: #007bff;
        }

        .delete_btn{
            background-color: #d50000;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: 0.05s ease;
            font-family: "Poppins", "sans-serif";
            font-size: 1rem;
            transition: 0.3s ease;
        }
        .delete_btn:hover{
            background-color: #c50303;
            transition: 0.3s ease;
        }

        .search-bar{
            display: flex;
            justify-content:space-between;
            align-items: center;
        }
    </style>
</head>
<body>
    <div class="grid-container">
        <div class="header">
            <div class="container">
                <div class="header__wrapper"></div>
            </div>
        </div>

        <div id="sidenav">
            <div class="sidenav__wrapper">
                <div class="sidenav__img">
                    <img src="./dist/img/skooltech-logo.png" alt="">
                </div>
                <div class="sidenav-list">
                    <ul>
                        <li><a href="./admin_dashboard.php"><span class="material-icons-outlined">dashboard</span>Dashboard</a></li>
                        <li>
                            <a href="#" class="dropdown-toggle">
                                <span class="material-icons-outlined">app_registration</span> Task Creator
                                <div class="arrow-down">
                                    <span class="material-icons-outlined chevron-icon">keyboard_arrow_down</span>
                                </div>
                            </a>
                            <ul class="dropdown-content">
                                <li><a href="./task_creator_assignment.php">Assignment</a></li>
                                <li><a href="./task_creator.php">Quiz</a></li>
                                <li><a href="./task_creator_exam.php">Exam</a></li>
                            </ul>
                        </li>
                        
                        <li>
                            <a href="#" class="dropdown-toggle">
                            <span class="material-icons-outlined">sort</span> Results
                                <div class="arrow-down">
                                    <span class="material-icons-outlined chevron-icon">keyboard_arrow_down</span>
                                </div>
                            </a>
                            <ul class="dropdown-content">
                                <li><a href="./admin_analysis.php">Analysis</a></li>
                                <li><a href="./admin_assignments.php">Ass Results</a></li>
                            </ul>
                        </li>

                        <li><a href="./admin_students.php"><span class="material-icons-outlined">group</span>Students</a></li>
                        <li><a href="./admin_reportcard.php"><span class="material-icons-outlined">credit_card</span>Report Card</a></li>
                        <li><a href="logout.php"><span class="material-icons-outlined">logout</span>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <main class="main-container">
            <div class="notification <?php echo htmlspecialchars($status); ?> <?php echo $status ? 'show' : ''; ?>" id="notification">
                <?php echo htmlspecialchars($message); ?>
            </div>

            <h2>Class List</h2>
            <h2>Number of Students <?php echo $result_students->num_rows; ?></h2>
            <div class="search-bar">
                <input type="text" class="search-input" id="searchInput" placeholder="Search by Name or Username...">
                <button onclick="document.getElementById('addStudentModal').style.display='block'">Add New Student</button>
            </div>

            <!-- Add Student Modal (Floating) -->
            <div id="addStudentModal" style="display:none;">
                <h3>Add Student</h3>
                <form method="POST">
                    <label for="student_number">Student Number:</label><br>
                    <input type="text" name="student_number" required><br>
                    <label for="username">Username:</label><br>
                    <input type="text" name="username" required><br>
                    <label for="name">Name:</label><br>
                    <input type="text" name="name" required><br>
                    <label for="password">Password:</label><br>
                    <input type="password" name="password" required><br>
                    <button onclick="document.getElementById('addStudentModal').style.display='none'">Cancel</button>
                    <button type="submit" name="add_student">Add Student</button>
                </form>
            </div>

            <!-- Edit Student Modal -->
            <div id="editStudentModal" class="overlay">
                <div class="modal-content">
                    <h3>Edit Student</h3>
                    <form id="editStudentForm">
                        <input type="hidden" name="student_id" id="edit_student_id">
                        <label for="student_number">Student Number:</label><br>
                        <input type="text" name="student_number" id="edit_student_number" required><br>
                        <label for="username">Username:</label><br>
                        <input type="text" name="username" id="edit_username" required><br>
                        <label for="name">Name:</label><br>
                        <input type="text" name="name" id="edit_name" required><br>
                        <label for="password">Password (optional):</label><br>
                        <input type="password" name="password" id="edit_password"><br>
                        <button onclick="closeEditModal()">Cancel</button>
                        <button type="submit" name="edit_student">Save Changes</button>
                    </form>
                </div>
            </div>

            <!-- Students Table -->
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Student Number</th>
                        <th>Username</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="studentsTable">
                    <?php
                    if ($result_students->num_rows > 0) {
                        while ($row = $result_students->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['student_number']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                            echo "<td>
                                    <button onclick=\"openEditModal(" . $row['id'] . ", '" . $row['student_number'] . "', '" . $row['username'] . "', '" . $row['name'] . "')\">Edit</button>
                                    <a class='delete_btn' href='?delete_id=" . $row['id'] . "' onclick='return confirm(\"Are you sure?\")'>Delete</a>
                                  </td>";
                            echo "</tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>
        </main>
    </div>

    <script src="./dist/js/dropdown.js"></script>   
    <script>
        // Search functionality
        const searchInput = document.getElementById('searchInput');
        searchInput.addEventListener('keyup', function () {
            const filter = searchInput.value.toLowerCase();
            const rows = document.querySelectorAll('#studentsTable tr');
            rows.forEach(row => {
                const nameCell = row.cells[0].textContent.toLowerCase();
                const usernameCell = row.cells[2].textContent.toLowerCase();
                if (nameCell.indexOf(filter) > -1 || usernameCell.indexOf(filter) > -1) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        });

        // Edit Modal functions
        function openEditModal(id, student_number, username, name) {
            document.getElementById('edit_student_id').value = id;
            document.getElementById('edit_student_number').value = student_number;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_name').value = name;
            document.getElementById('editStudentModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editStudentModal').style.display = 'none';
        }
    </script>
</body>
</html>
