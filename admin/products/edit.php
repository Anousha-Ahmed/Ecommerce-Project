<?php

// Only logged-in admins can access this page.
require_once __DIR__ . '/../auth-check.php';

// Load required classes.
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Validator.php';
require_once __DIR__ . '/../../core/Session.php';

$database = new Database();
$mysqli = $database->getConnection();

// Edit button sends: edit.php?id=1
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    die('Invalid product ID.');
}

// Fetch existing product.
$stmt = $mysqli->prepare(
    "SELECT
        id,
        category_id,
        name,
        slug,
        description,
        price,
        stock,
        image,
        status
     FROM products
     WHERE id = ?
     LIMIT 1"
);

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $stmt->close();
    die('Product not found.');
}

$product = $result->fetch_assoc();
$stmt->close();

// Store existing values.
$categoryId = (int) $product['category_id'];
$name = $product['name'];
$slug = $product['slug'];
$description = $product['description'];
$price = $product['price'];
$stock = (int) $product['stock'];
$oldImage = $product['image'];
$status = (int) $product['status'];

$errors = [];

// Fetch active categories.
$categoryStmt = $mysqli->prepare(
    "SELECT id, name
     FROM categories
     WHERE status = 1
     ORDER BY name ASC"
);

$categoryStmt->execute();

$categoryResult = $categoryStmt->get_result();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $categoryId = isset($_POST['category_id']) ? (int) $_POST['category_id'] : 0;
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $stock = isset($_POST['stock']) ? (int) $_POST['stock'] : 0;
    $status = isset($_POST['status']) ? (int) $_POST['status'] : 1;

    $validator = new Validator();

    $validator
        ->required('name', $name, 'Product name is required.')
        ->required('slug', $slug, 'Product slug is required.')
        ->required('price', $price, 'Product price is required.');

    $errors = $validator->errors();

    if ($categoryId <= 0) {
        $errors['category_id'] = 'Please select a category.';
    }

    if ($price !== '') {
        if (!is_numeric($price)) {
            $errors['price'] = 'Price must be a valid number.';
        } elseif ((float) $price < 0) {
            $errors['price'] = 'Price cannot be negative.';
        }
    }

    if ($stock < 0) {
        $errors['stock'] = 'Stock cannot be negative.';
    }

    // Verify category exists.
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

    // Check duplicate slug (excluding this product).
    if (!isset($errors['slug'])) {

        $stmt = $mysqli->prepare(
            "SELECT id
             FROM products
             WHERE slug = ?
             AND id != ?
             LIMIT 1"
        );

        $stmt->bind_param("si", $slug, $id);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $errors['slug'] = 'This product slug already exists.';
        }

        $stmt->close();
    }

    // Keep existing image by default.
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

    // Update the product.
    if (empty($errors)) {

        $priceValue = (float) $price;

        $stmt = $mysqli->prepare(
            "UPDATE products
             SET category_id = ?,
                 name = ?,
                 slug = ?,
                 description = ?,
                 price = ?,
                 stock = ?,
                 image = ?,
                 status = ?
             WHERE id = ?"
        );

        $stmt->bind_param(
            "isssdisii",
            $categoryId,
            $name,
            $slug,
            $description,
            $priceValue,
            $stock,
            $newImage,
            $status,
            $id
        );

        if ($stmt->execute()) {

            // Delete old image only after a successful update.
            if ($newImage !== $oldImage && !empty($oldImage)) {

                $oldImagePath = __DIR__ . '/../../public/uploads/products/' . $oldImage;

                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }

            Session::flash('success', 'Product updated successfully.');

            $stmt->close();

            header('Location: index.php');
            exit;

        } else {

            // Delete the new image if the DB update failed.
            if ($newImage !== $oldImage) {

                $newImagePath = __DIR__ . '/../../public/uploads/products/' . $newImage;

                if (file_exists($newImagePath)) {
                    unlink($newImagePath);
                }
            }

            $errors['database'] = 'Failed to update product.';
        }

        $stmt->close();
    }
}

$basePath = '../';
$pageTitle = 'Edit Product';

require_once __DIR__ . '/../../includes/admin-header.php';
?>

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">

                <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                    <h6>Edit Product</h6>
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
                            <input type="text" name="slug" class="form-control" value="<?= htmlspecialchars($slug) ?>" required>
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
                            <label class="form-label">Current Image</label>
                            <?php if (!empty($oldImage)): ?>
                                <div class="mb-2">
                                    <img src="../../public/uploads/products/<?= htmlspecialchars($oldImage) ?>" alt="<?= htmlspecialchars($name) ?>" width="120" height="90" style="object-fit:cover; border-radius:0.5rem;">
                                </div>
                            <?php else: ?>
                                <p class="text-muted">No image uploaded.</p>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Replace Image</label>
                            <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                            <p class="text-xs text-secondary mt-1 mb-0">Leave empty to keep current image. JPG, PNG or WEBP. Maximum size: 2 MB.</p>
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

                        <button type="submit" class="btn bg-gradient-dark w-100">Update Product</button>

                    </form>

                </div>
            </div>
        </div>
    </div>

<?php

require_once __DIR__ . '/../../includes/admin-footer.php';

$categoryStmt->close();

?>