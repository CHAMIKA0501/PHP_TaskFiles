<?php
$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "employees";


$conn = new mysqli($servername, $username, $password, $dbname);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    $firstname = $_POST['first_name'];
    $lastname = $_POST['last_name'];
    $age = $_POST['age'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $sql = "INSERT INTO info (firstname, lastname, age, email, password) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ssiss", $firstname, $lastname, $age, $email, $password);

    if ($stmt->execute()) {
        echo "New record created successfully. <a href='info_table.php'>Go back</a>";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_delete'])) {
    if (!empty($_POST['selected_ids'])) {
        $ids = implode(',', $_POST['selected_ids']);
        $sql = "DELETE FROM info WHERE id IN ($ids)";
        if ($conn->query($sql) === TRUE) {
            echo "Records deleted successfully";
        } else {
            echo "Error deleting records: " . $conn->error;
        }
    }
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];
    $sql = "DELETE FROM info WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "Record deleted successfully";
    } else {
        echo "Error deleting record: " . $stmt->error;
    }

    $stmt->close();
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_id'])) {
    $id = $_POST['update_id'];
    $firstname = $_POST['update_first_name'];
    $lastname = $_POST['update_last_name'];
    $age = $_POST['update_age'];
    $email = $_POST['update_email'];
    
    $sql = "UPDATE info SET firstname=?, lastname=?, age=?, email=? WHERE id=?";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ssisi", $firstname, $lastname, $age, $email, $id);

    if ($stmt->execute()) {
        echo "Record updated successfully. <a href='info_table.php'>Go back</a>";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}


$sql = "SELECT id, firstname, lastname, age, email FROM info";
$result = $conn->query($sql);
?>

<head>
    <link rel="stylesheet" href="styles.css">
</head>


<div class="table-container">
    <form id="dataForm" method="post" action="">
        <?php
        if ($result->num_rows > 0) {
            echo "<table>
                    <tr>
                        <th>Select</th>
                        <th>ID</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Age</th>
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td><input type='checkbox' name='selected_ids[]' value='" . $row['id'] . "'></td>
                        <td>" . $row['id'] . "</td>
                        <td>" . $row['firstname'] . "</td>
                        <td>" . $row['lastname'] . "</td>
                        <td>" . $row['age'] . "</td>
                        <td>" . $row['email'] . "</td>
                        <td class='actions'>
                            <button type='button' class='edit' onclick='editRow(" . $row['id'] . ", \"" . $row['firstname'] . "\", \"" . $row['lastname'] . "\", " . $row['age'] . ", \"" . $row['email'] . "\")'>Update</button>
                            <button type='button' class='delete' onclick='confirmDelete(" . $row['id'] . ")'>Delete</button>
                        </td>
                      </tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No records found</p>";
        }
        ?>
        <div class="form-actions">
            <button type="button" class="delete-selected" onclick="confirmBulkDelete()">Delete Selected</button>
            <button type="button" class="update-selected" onclick="editSelected()">Update Selected</button>
        </div>
    </form>

    <form id="editForm" method="post" action="" style="display: none; margin-top: 20px;">
        <h2>Edit Record</h2>
        <input type="hidden" name="update_id" id="update_id">
        <label for="update_first_name">First Name:</label>
        <input type="text" name="update_first_name" id="update_first_name"><br><br>
        <label for="update_last_name">Last Name:</label>
        <input type="text" name="update_last_name" id="update_last_name"><br><br>
        <label for="update_age">Age:</label>
        <input type="number" name="update_age" id="update_age"><br><br>
        <label for="update_email">Email:</label>
        <input type="email" name="update_email" id="update_email"><br><br>
        <button type="submit">Update Record</button>
    </form>
</div>

<script>
function editRow(id, firstName, lastName, age, email) {
    document.getElementById('editForm').style.display = 'block';
    document.getElementById('update_id').value = id;
    document.getElementById('update_first_name').value = firstName;
    document.getElementById('update_last_name').value = lastName;
    document.getElementById('update_age').value = age;
    document.getElementById('update_email').value = email;
}

function confirmDelete(id) {
    if (confirm("Are you sure you want to delete this record?")) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '';

        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'delete_id';
        input.value = id;
        form.appendChild(input);

        document.body.appendChild(form);
        form.submit();
    }
}

function confirmBulkDelete() {
    if (confirm("Are you sure you want to delete the selected records?")) {
        var form = document.getElementById('dataForm');
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'confirm_delete';
        input.value = '1';
        form.appendChild(input);
        form.submit();
    }
}

function editSelected() {
    var selected = document.querySelectorAll('input[name="selected_ids[]"]:checked');
    if (selected.length > 0) {
        var id = selected[0].value;
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = 'info_form.php';

        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'edit_id';
        input.value = id;
        form.appendChild(input);

        document.body.appendChild(form);
        form.submit();
    } else {
        alert("Please select at least one record to update.");
    }
}
</script>
