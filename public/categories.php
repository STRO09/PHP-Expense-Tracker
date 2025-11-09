<?php
require_once '../config/db.php';
include '../includes/header.php';

session_start();
$user_id = $_SESSION['user_id'];

// Handle Add
if (isset($_POST['add_category'])) {
    $name = trim($_POST['name']);

    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO main.categories (user_id, name) VALUES (?, ?)");
        $stmt->execute([$user_id, $name]);
    }
    header("Location: categories.php");
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM main.categories WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    header("Location: categories.php");
    exit;
}

// Handle Edit
$edit_category = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM main.categories WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    $edit_category = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (isset($_POST['update_category'])) {
    $id = $_POST['category_id'];
    $name = trim($_POST['name']);

    if (!empty($name)) {
        $stmt = $pdo->prepare("UPDATE main.categories SET name=? WHERE id=? AND user_id=?");
        $stmt->execute([$name, $id, $user_id]);
    }
    header("Location: categories.php");
    exit;
}

// Fetch all categories
$stmt = $pdo->prepare("SELECT * FROM main.categories WHERE user_id = ? ORDER BY name ASC");
$stmt->execute([$user_id]);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container py-4">
    <h2 class="mb-4 text-center">Category Manager</h2>

    <!-- Add / Edit Category Form -->
    <div class="card mb-4">
        <div class="card-header bg-dark text-light">
            <?= $edit_category ? 'Edit Category' : 'Add New Category' ?>
        </div>
        <div class="card-body">
            <form method="POST" class="row g-3">
                <?php if ($edit_category): ?>
                    <input type="hidden" name="category_id" value="<?= $edit_category['id'] ?>">
                <?php endif; ?>

                <div class="col-md-8">
                    <label class="form-label">Category Name</label>
                    <input type="text" class="form-control" name="name"
                        value="<?= htmlspecialchars($edit_category['name'] ?? '') ?>" required>
                </div>

                <div class="col-md-4 d-flex align-items-end">
                    <?php if ($edit_category): ?>
                        <button type="submit" name="update_category" class="btn btn-success w-100">Update</button>
                    <?php else: ?>
                        <button type="submit" name="add_category" class="btn btn-primary w-100">Add</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Category List -->
    <div class="card">
        <div class="card-header bg-dark text-white">Your Categories</div>
        <div class="card-body">
            <?php if (count($categories) === 0): ?>
                <p class="text-muted">No categories yet. Add one above.</p>
            <?php else: ?>
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $index => $cat): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($cat['name']) ?></td>
                                <td>
                                    <a href="?edit=<?= $cat['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                    <a href="?delete=<?= $cat['id'] ?>" class="btn btn-sm btn-danger"
                                        onclick="return confirm('Delete this category?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>