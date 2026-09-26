<?php

// Only logged-in admins can access this page.
require_once __DIR__ . '/../auth-check.php';

// Load required classes.
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Validator.php';
require_once __DIR__ . '/../../core/Session.php';

$database = new Database();
$mysqli = $database->getConnection();

$validator = new Validator();

$errors = [];

$name = '';
$slug = '';
$status = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $status = isset($_POST['status']) ? (int) $_POST['status'] : 1;

    $validator
        ->required('name', $name, 'Category name is required.')
        ->required('slug', $slug, 'Category slug is required.')
        ->minLength('name', $name, 2, 'Category name must be at least 2 characters.');

    $errors = $validator->errors();

    // Image is required for a new category.
    if (
        !isset($_FILES['image']) ||
        $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE
    ) {

        $errors['image'] = 'Category image is required.';

    } else {

        $image = $_FILES['image'];

        if ($image['error'] !== UPLOAD_ERR_OK) {

            $errors['image'] = 'There was an error uploading the image.';

        } else {

            $maxFileSize = 2 * 1024 * 1024;

            if ($image['size'] > $maxFileSize) {
                $errors['image'] = 'Image size must not exceed 2 MB.';
            }

            if (empty($errors['image'])) {

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
        }
    }

    // Check duplicate slug.
    if ($validator->isValid() && empty($errors)) {

        $stmt = $mysqli->prepare(
            "SELECT id
             FROM categories
             WHERE slug = ?
             LIMIT 1"
        );

        $stmt->bind_param("s", $slug);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $errors['slug'] = 'This slug already exists.';
        }

        $stmt->close();
    }

    // Insert category.
    if ($validator->isValid() && empty($errors)) {

        $uploadDirectory = __DIR__ . '/../../public/uploads/categories/';

        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0755, true);
        }

        $mimeType = mime_content_type($_FILES['image']['tmp_name']);

        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp'
        ];

        $extension = $extensions[$mimeType];

        $fileName = bin2hex(random_bytes(10)) . '.' . $extension;

        $destination = $uploadDirectory . $fileName;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
            $errors['image'] = 'Failed to save the uploaded image.';
        }

        if (empty($errors)) {

            $stmt = $mysqli->prepare(
                "INSERT INTO categories
                 (name, slug, image, status)
                 VALUES (?, ?, ?, ?)"
            );

            $stmt->bind_param("sssi", $name, $slug, $fileName, $status);

            if ($stmt->execute()) {

                Session::flash('success', 'Category created successfully.');

                $stmt->close();

                header('Location: index.php');
                exit;

            } else {

                if (file_exists($destination)) {
                    unlink($destination);
                }

                $errors['database'] = 'Failed to create category.';
            }

            $stmt->close();
        }
    }
}

$basePath = '../';
$pageTitle = 'Add Category';

require_once __DIR__ . '/../../includes/admin-header.php';
?>

    <div class="row">
        <div class="col-lg-7 mx-auto">
            <div class="card">

                <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                    <h6>Add Category</h6>
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
                            <input type="text" name="slug" class="form-control" value="<?= htmlspecialchars($slug) ?>" placeholder="electronics" required>
                            <?php if (isset($errors['slug'])): ?>
                                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['slug']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Category Image</label>
                            <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp" required>
                            <p class="text-xs text-secondary mt-1 mb-0">JPG, PNG or WEBP. Maximum size: 2 MB.</p>
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

                        <button type="submit" class="btn bg-gradient-dark w-100">Create Category</button>

                    </form>

                </div>
            </div>
        </div>
    </div>

<?php require_once __DIR__ . '/../../includes/admin-footer.php'; ?>