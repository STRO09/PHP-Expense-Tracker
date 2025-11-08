<?php

require '../config/db.php';

// Initialize messages
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === "POST") {

    // Get form inputs
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);

    // Basic validation
    if ($_POST["pass"] !== $_POST["cpass"]) {
        $error = "Passwords do not match!";
    } else {
        try {
            // Check if email already exists
            $check = $pdo->prepare("SELECT * FROM main.user WHERE email = :email");
            $check->execute(array(':email' => $email));

            if ($check->rowCount() > 0) {
                $error = "Email already registered!";
            } else {
                // Hash password (works fine in PHP 5.6+)
                $hashed = password_hash($_POST['pass'], PASSWORD_DEFAULT);

                // Insert new user
                $stmt = $pdo->prepare("INSERT INTO main.user (name, email, password) VALUES (:name, :email, :password)");
                $stmt->execute(array(
                    ':name' => $name,
                    ':email' => $email,
                    ':password' => $hashed
                ));

                $success = "Registration successful! You can now log in.";

                sleep(1);
                header("Location:login.php");
                exit();

            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>

<!doctype html>
<html lang="en">

<head>
    <title>Title</title>
    <!-- Required meta tags -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />

    <!-- Bootstrap CSS v5.2.1 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous" />
</head>

<body>

    <main>
        <div class="container mt-2 ml-5 p-5">
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                <?php if ($error != '') { ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php } elseif ($success != '') { ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php } ?>
                <h2 class="text-center p-4">
                    Register Form
                </h2>
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" name="name" placeholder="" required />
                    <label>Name</label>
                </div>

                <div class="form-floating mb-3">
                    <input type="mail" class="form-control" name="email" placeholder="" required />
                    <label> Email
                    </label>
                </div>
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" name="pass" placeholder="" required />
                    <label> Password
                    </label>
                </div>
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" name="cpass" placeholder="" required />
                    <label> Confirm password
                    </label>
                </div>

                <button type="submit" class="btn btn-primary">
                    Register
                </button>
            </form>
            <br>
            <span class="mt-4 pt-5">Account Created Already? <a
                    href="/Expense-Tracker/public/login.php">Login</a></span>


        </div>
    </main>



    <!-- Bootstrap JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
        integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r"
        crossorigin="anonymous"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"
        integrity="sha384-BBtl+eGJRgqQAUMxJ7pMwbEyER4l1g+O15P+16Ep7Q9Q+zqX6gSbd85u4mG4QzX+"
        crossorigin="anonymous"></script>
</body>

</html>