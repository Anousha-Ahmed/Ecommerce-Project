<?php

// Only logged-in admins can access this page.
require_once __DIR__ . '/../auth-check.php';

// Load required classes.
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Validator.php';
require_once __DIR__ . '/../../core/Session.php';

$database = new Database();
$mysqli = $database->getConnection();

// Edit button sends: edit.php?id=2
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    die('Invalid category ID.');
}

// Fetch existing category.
$stmt = $mysqli->prepare(
    "SELECT id, name, slug, image, status
     FROM categories
     WHERE id = ?
     LIMIT 1"
);

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $stmt->close();
    die('Category not found.');
}

$category = $result->fetch_assoc();
$stmt->close();

// Store existing values.
$name = $category['name'];
$slug = $category['slug'];
$status = (int) $category['status'];
$oldImage = $category['image'];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $status = isset($_POST['status']) ? (int) $_POST['status'] : 1;

    $validator = new Validator();

    $validator
        ->required('name', $name, 'Category name is required.')
        ->required('slug', $slug, 'Category slug is required.')
        ->minLength('name', $name, 2, 'Category name must be at least 2 characters.');

    $errors = $validator->errors();

    // Check duplicate slug (excluding this category).
    if ($validator->isValid()) {

        $stmt = $mysqli->prepare(
            "SELECT id
             FROM categories
             WHERE slug = ?
             AND id != ?
             LIMIT 1"
        );

        $stmt->bind_param("si", $slug, $id);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $errors['slug'] = 'This slug already exists.';
        }

        $stmt->close();
    }

    // Keep old image by default.
    $newImage = $oldImage;

    if (
        isset($_FILES['image']) &&
        $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        $image = $_FILES['image'];

        if ($image['error'] !== UPLOAD_ERR_OK) {

            $errors['image'] = 'There was an error uploading the image.';

        } else {

            $maxFileSize = 2 * 1024 * 1024;

            if ($image['size'] > $maxFileSize) {
                $errors['image'] = 'Image size must not exceed 2 MB.';
            }

            if (!isset($errors['image'])) {

                $mimeType = mime_content_type($image['tmp_name']);

                $allowedMimeTypes = [
                    'image/jpeg',
                    'image/png',
                    'image/webp'
                ];

                if (!in_array($mimeType, $allowedMimeTypes, true)) {
                    $errors['image'] = 'Only JPG, PNG and WEBP images are allowed.';
                }
            }

            if (!isset($errors['image'])) {

                $uploadDirectory = __DIR__ . '/../../public/uploads/categories/';

                if (!is_dir($uploadDirectory)) {
                    mkdir($uploadDirectory, 0755, true);
                }

                $extensions = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp'
                ];

                $extension = $extensions[$mimeType];

                $fileName = bin2hex(random_bytes(10)) . '.' . $extension;

                $destination = $uploadDirectory . $fileName;

                if (move_uploaded_file($image['tmp_name'], $destination)) {
                    $newImage = $fileName;
                } else {
                    $errors['image'] = 'Failed to save the uploaded image.';
                }
            }
        }
    }

    // Update the category.
    if ($validator->isValid() && empty($errors)) {

        $stmt = $mysqli->prepare(
            "UPDATE categories
             SET name = ?,
                 slug = ?,
                 image = ?,
                 status = ?
             WHERE id = ?"
        );

        $stmt->bind_param("sssii", $name, $slug, $newImage, $status, $id);

        if ($stmt->execute()) {

            // Delete old image only after a successful update.
            if ($newImage !== $oldImage && !empty($oldImage)) {

                $oldImagePath = __DIR__ . '/../../public/uploads/categories/' . $oldImage;

                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }

            Session::flash('success', 'Category updated successfully.');

            $stmt->close();

            header('Location: index.php');
            exit;

        } else {

            // Delete the new image if the DB update failed.
            if ($newImage !== $oldImage) {

                $newImagePath = __DIR__ . '/../../public/uploads/categories/' . $newImage;

                if (file_exists($newImagePath)) {
                    unlink($newImagePath);
                }
            }

            $errors['database'] = 'Failed to update category.';
        }

        $stmt->close();
    }
}

$basePath = '../';
$pageTitle = 'Edit Category';

require_once __DIR__ . '/../../includes/admin-header.php';
?>

    <div class="row">
        <div class="col-lg-7 mx-auto">
            <div class="card">

                <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                    <h6>Edit Category</h6>
                    <a href="index.php" class="btn btn-outline-dark btn-sm mb-0">Back</a>
                </div>

                <div class="card-body">

                    <?php if (isset($errors['database'])): ?>
                        <div class="alert alert-danger text-white">
                            <?= htmlspecialchars($errors['database']) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" enctype="multipart/form-data">

                        <div class="mb-3">
                            <label class="form-label">Category Name</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($name) ?>" required>
                            <?php if (isset($errors['name'])): ?>
                                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['name']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" class="form-control" value="<?= htmlspecialchars($slug) ?>" required>
                            <?php if (isset($errors['slug'])): ?>
                                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['slug']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Current Image</label>
                            <?php if (!empty($oldImage)): ?>
                                <div class="mb-2">
                                    <img src="../../public/uploads/categories/<?= htmlspecialchars($oldImage) ?>" alt="<?= htmlspecialchars($name) ?>" width="120" height="90" style="object-fit:cover; border-radius:0.5rem;">
                                </div>
                            <?php else: ?>
                                <p class="text-muted">No image uploaded.</p>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Replace Image</label>
                            <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                            <p class="text-xs text-secondary mt-1 mb-0">Leave empty to keep the current image. JPG, PNG or WEBP. Maximum size: 2 MB.</p>
                            <?php if (isset($errors['image'])): ?>
                                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['image']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control">
                                <option value="1" <?= $status === 1 ? 'selected' : '' ?>>Active</option>
                                <option value="0" <?= $status === 0 ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>

                        <button type="submit" class="btn bg-gradient-dark w-100">Update Category</button>

                    </form>

                </div>
            </div>
        </div>
    </div>

<?php require_once __DIR__ . '/../../includes/admin-footer.php'; ?>