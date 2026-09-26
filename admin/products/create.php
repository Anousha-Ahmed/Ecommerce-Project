<?php

// Only logged-in admins can access this page.
require_once __DIR__ . '/../auth-check.php';

// Load required classes.
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Validator.php';
require_once __DIR__ . '/../../core/Session.php';

$database = new Database();
$mysqli = $database->getConnection();

// Fetch categories for the dropdown.
$categoryStmt = $mysqli->prepare(
    "SELECT id, name
     FROM categories
     WHERE status = 1
     ORDER BY name ASC"
);

$categoryStmt->execute();

$categoryResult = $categoryStmt->get_result();

// Default form values.
$name = '';
$slug = '';
$description = '';
$price = '';
$stock = 0;
$categoryId = 0;
$status = 1;

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $stock = isset($_POST['stock']) ? (int) $_POST['stock'] : 0;
    $categoryId = isset($_POST['category_id']) ? (int) $_POST['category_id'] : 0;
    $status = isset($_POST['status']) ? (int) $_POST['status'] : 1;

    // Basic validation
    $validator = new Validator();

    $validator
        ->required('name', $name, 'Product name is required.')
        ->required('slug', $slug, 'Product slug is required.')
        ->required('price', $price, 'Product price is required.');

    $errors = $validator->errors();

    // Category validation
    if ($categoryId <= 0) {
        $errors['category_id'] = 'Please select a category.';
    }

    // Price validation
    if ($price !== '') {
        if (!is_numeric($price)) {
            $errors['price'] = 'Price must be a valid number.';
        } elseif ((float) $price < 0) {
            $errors['price'] = 'Price cannot be negative.';
        }
    }

    // Stock validation
    if ($stock < 0) {
        $errors['stock'] = 'Stock cannot be negative.';
    }

    // Check category exists
    if (!isset($errors['category_id'])) {

        $stmt = $mysqli->prepare(
            "SELECT id
             FROM categories
             WHERE id = ?
             AND status = 1
             LIMIT 1"
        );

        $stmt->bind_param("i", $categoryId);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows !== 1) {
            $errors['category_id'] = 'Selected category is not valid.';
        }

        $stmt->close();
    }

    // Check duplicate slug
    if (!isset($errors['slug'])) {

        $stmt = $mysqli->prepare(
            "SELECT id
             FROM products
             WHERE slug = ?
             LIMIT 1"
        );

        $stmt->bind_param("s", $slug);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $errors['slug'] = 'This product slug already exists.';
        }

        $stmt->close();
    }

    // Image validation and upload
    $imageName = null;

    if (
        isset($_FILES['image']) &&
        $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        $image = $_FILES['image'];

        if ($image['error'] !== UPLOAD_ERR_OK) {

            $errors['image'] = 'There was an error uploading the image.';

        } else {

            // File size, max 2 MB
            $maxFileSize = 2 * 1024 * 1024;

            if ($image['size'] > $maxFileSize) {
                $errors['image'] = 'Image size must not exceed 2 MB.';
            }

            // Mime type check
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

            // Move image
            if (!isset($errors['image'])) {

                $uploadDirectory = __DIR__ . '/../../public/uploads/products/';

                if (!is_dir($uploadDirectory)) {
                    mkdir($uploadDirectory, 0755, true);
                }

                $extensions = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp'
                ];

                $extension = $extensions[$mimeType];

                $imageName = bin2hex(random_bytes(10)) . '.' . $extension;

                $destination = $uploadDirectory . $imageName;

                if (!move_uploaded_file($image['tmp_name'], $destination)) {
                    $errors['image'] = 'Failed to save the uploaded image.';
                    $imageName = null;
                }
            }
        }
    }

    // Insert product
    if (empty($errors)) {

        $stmt = $mysqli->prepare(
            "INSERT INTO products
            (
                category_id,
                name,
                slug,
                description,
                price,
                stock,
                image,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $priceValue = (float) $price;

        $stmt->bind_param(
            "isssdisi",
            $categoryId,
            $name,
            $slug,
            $description,
            $priceValue,
            $stock,
            $imageName,
            $status
        );

        if ($stmt->execute()) {

            Session::flash('success', 'Product created successfully.');

            $stmt->close();

            header('Location: index.php');
            exit;

        } else {

            if ($imageName !== null) {

                $imagePath = __DIR__ . '/../../public/uploads/products/' . $imageName;

                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            $errors['database'] = 'Failed to create product.';
        }

        $stmt->close();
    }
}

$basePath = '../';
$pageTitle = 'Add Product';

require_once __DIR__ . '/../../includes/admin-header.php';
?>

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">

                <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                    <h6>Add Product</h6>
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
                            <label class="form-label">Category</label>
                            <select id="category_id" name="category_id" class="form-control" required>
                                <option value="">Select Category</option>
                                <?php while ($category = $categoryResult->fetch_assoc()): ?>
                                    <option value="<?= (int) $category['id'] ?>" <?= $categoryId === (int) $category['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category['name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <?php if (isset($errors['category_id'])): ?>
                                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['category_id']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Product Name</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($name) ?>" required>
                            <?php if (isset($errors['name'])): ?>
                                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['name']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" class="form-control" value="<?= htmlspecialchars($slug) ?>" placeholder="example-product" required>
                            <?php if (isset($errors['slug'])): ?>
                                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['slug']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($description) ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Price</label>
                            <input type="number" name="price" class="form-control" value="<?= htmlspecialchars($price) ?>" min="0" step="0.01" required>
                            <?php if (isset($errors['price'])): ?>
                                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['price']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Stock</label>
                            <input type="number" name="stock" class="form-control" value="<?= (int) $stock ?>" min="0" required>
                            <?php if (isset($errors['stock'])): ?>
                                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['stock']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Product Image</label>
                            <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
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

                        <button type="submit" class="btn bg-gradient-dark w-100">Create Product</button>

                    </form>

                </div>
            </div>
        </div>
    </div>

<?php

require_once __DIR__ . '/../../includes/admin-footer.php';

$categoryStmt->close();

?>