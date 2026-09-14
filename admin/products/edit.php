<?php

// Only logged-in admins can access this page.
require_once __DIR__ . '/../auth-check.php';

// Load required classes.
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Validator.php';
require_once __DIR__ . '/../../core/Session.php';


// ==========================================
// DATABASE CONNECTION
// ==========================================

$database = new Database();
$mysqli = $database->getConnection();


// ==========================================
// GET PRODUCT ID
// ==========================================

/*
 * Edit button sends:
 *
 * edit.php?id=1
 *
 * $_GET['id'] receives the product ID.
 */
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;


// Make sure ID is valid.
if ($id <= 0) {

    die('Invalid product ID.');

}


// ==========================================
// FETCH EXISTING PRODUCT
// ==========================================

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


// Product doesn't exist.
if ($result->num_rows !== 1) {

    $stmt->close();

    die('Product not found.');

}


// Store existing product data.
$product = $result->fetch_assoc();

$stmt->close();


// ==========================================
// STORE EXISTING VALUES
// ==========================================

$categoryId = (int) $product['category_id'];
$name = $product['name'];
$slug = $product['slug'];
$description = $product['description'];
$price = $product['price'];
$stock = (int) $product['stock'];
$oldImage = $product['image'];
$status = (int) $product['status'];


// Validation errors.
$errors = [];


// ==========================================
// FETCH ACTIVE CATEGORIES
// ==========================================

$categoryStmt = $mysqli->prepare(
    "SELECT id, name
     FROM categories
     WHERE status = 1
     ORDER BY name ASC"
);

$categoryStmt->execute();

$categoryResult = $categoryStmt->get_result();


// ==========================================
// HANDLE UPDATE
// ==========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    // Get updated form values.
    $categoryId = isset($_POST['category_id'])
        ? (int) $_POST['category_id']
        : 0;

    $name = trim($_POST['name'] ?? '');

    $slug = trim($_POST['slug'] ?? '');

    $description = trim($_POST['description'] ?? '');

    $price = trim($_POST['price'] ?? '');

    $stock = isset($_POST['stock'])
        ? (int) $_POST['stock']
        : 0;

    $status = isset($_POST['status'])
        ? (int) $_POST['status']
        : 1;


    // ==========================================
    // BASIC VALIDATION
    // ==========================================

    $validator = new Validator();

    $validator
        ->required(
            'name',
            $name,
            'Product name is required.'
        )
        ->required(
            'slug',
            $slug,
            'Product slug is required.'
        )
        ->required(
            'price',
            $price,
            'Product price is required.'
        );


    $errors = $validator->errors();


    // ==========================================
    // CATEGORY VALIDATION
    // ==========================================

    if ($categoryId <= 0) {

        $errors['category_id'] =
            'Please select a category.';

    }


    // ==========================================
    // PRICE VALIDATION
    // ==========================================

    if ($price !== '') {

        if (!is_numeric($price)) {

            $errors['price'] =
                'Price must be a valid number.';

        } elseif ((float) $price < 0) {

            $errors['price'] =
                'Price cannot be negative.';
        }
    }


    // ==========================================
    // STOCK VALIDATION
    // ==========================================

    if ($stock < 0) {

        $errors['stock'] =
            'Stock cannot be negative.';

    }


    // ==========================================
    // VERIFY CATEGORY
    // ==========================================

    if (!isset($errors['category_id'])) {

        /*
         * Make sure selected category actually exists
         * and is active.
         */
        $stmt = $mysqli->prepare(
            "SELECT id
             FROM categories
             WHERE id = ?
             AND status = 1
             LIMIT 1"
        );

        $stmt->bind_param(
            "i",
            $categoryId
        );

        $stmt->execute();

        $result = $stmt->get_result();


        if ($result->num_rows !== 1) {

            $errors['category_id'] =
                'Selected category is not valid.';
        }


        $stmt->close();
    }


    // ==========================================
    // CHECK DUPLICATE SLUG
    // ==========================================

    if (!isset($errors['slug'])) {

        /*
         * Check if another product already has
         * this slug.
         *
         * Current product is excluded using:
         *
         * id != ?
         */
        $stmt = $mysqli->prepare(
            "SELECT id
             FROM products
             WHERE slug = ?
             AND id != ?
             LIMIT 1"
        );

        $stmt->bind_param(
            "si",
            $slug,
            $id
        );

        $stmt->execute();

        $result = $stmt->get_result();


        if ($result->num_rows > 0) {

            $errors['slug'] =
                'This product slug already exists.';
        }


        $stmt->close();
    }


    // ==========================================
    // IMAGE HANDLING
    // ==========================================

    /*
     * By default, keep existing image.
     */
    $newImage = $oldImage;


    // Check whether a new image was selected.
    if (
        isset($_FILES['image']) &&
        $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
    ) {


        $image = $_FILES['image'];


        // Check upload error.
        if ($image['error'] !== UPLOAD_ERR_OK) {

            $errors['image'] =
                'There was an error uploading the image.';

        } else {


            // ==========================================
            // FILE SIZE
            // ==========================================

            $maxFileSize = 2 * 1024 * 1024;


            if ($image['size'] > $maxFileSize) {

                $errors['image'] =
                    'Image size must not exceed 2 MB.';
            }


            // ==========================================
            // MIME TYPE
            // ==========================================

            if (!isset($errors['image'])) {

                /*
                 * Check actual MIME type.
                 */
                $mimeType = mime_content_type(
                    $image['tmp_name']
                );


                $allowedMimeTypes = [
                    'image/jpeg',
                    'image/png',
                    'image/webp'
                ];


                if (!in_array(
                    $mimeType,
                    $allowedMimeTypes,
                    true
                )) {

                    $errors['image'] =
                        'Only JPG, PNG and WEBP images are allowed.';
                }
            }


            // ==========================================
            // SAVE NEW IMAGE
            // ==========================================

            if (!isset($errors['image'])) {


                $uploadDirectory =
                    __DIR__ . '/../../public/uploads/products/';


                // Create directory if needed.
                if (!is_dir($uploadDirectory)) {

                    mkdir(
                        $uploadDirectory,
                        0755,
                        true
                    );
                }


                // Safe extension based on MIME type.
                $extensions = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp'
                ];


                $extension = $extensions[$mimeType];


                // Generate random filename.
                $fileName =
                    bin2hex(random_bytes(10))
                    . '.'
                    . $extension;


                $destination =
                    $uploadDirectory . $fileName;


                // Move new image.
                if (move_uploaded_file(
                    $image['tmp_name'],
                    $destination
                )) {

                    $newImage = $fileName;

                } else {

                    $errors['image'] =
                        'Failed to save the uploaded image.';
                }
            }
        }
    }


    // ==========================================
    // UPDATE DATABASE
    // ==========================================

    if (empty($errors)) {


        $priceValue = (float) $price;


        /*
         * UPDATE modifies the selected product.
         *
         * WHERE id = ?
         * makes sure only this product changes.
         */
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


        /*
         * Types:
         *
         * category_id = i
         * name        = s
         * slug        = s
         * description = s
         * price       = d
         * stock       = i
         * image       = s
         * status      = i
         * id          = i
         *
         * issdisiii
         */
        $stmt->bind_param(
            "issdsisii",
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


        // Execute UPDATE.
        if ($stmt->execute()) {


            // Delete old image only after successful update.
            if (
                $newImage !== $oldImage &&
                !empty($oldImage)
            ) {

                $oldImagePath =
                    __DIR__
                    . '/../../public/uploads/products/'
                    . $oldImage;


                if (file_exists($oldImagePath)) {

                    unlink($oldImagePath);
                }
            }


            // Success flash message.
            Session::flash(
                'success',
                'Product updated successfully.'
            );


            $stmt->close();


            // Redirect to products list.
            header('Location: index.php');
            exit;

        } else {


            /*
             * If DB update fails after uploading
             * a new image, delete the unused image.
             */
            if ($newImage !== $oldImage) {

                $newImagePath =
                    __DIR__
                    . '/../../public/uploads/products/'
                    . $newImage;


                if (file_exists($newImagePath)) {

                    unlink($newImagePath);
                }
            }


            $errors['database'] =
                'Failed to update product.';
        }


        $stmt->close();
    }
}

?>


<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Edit Product - Admin</title>


    <!-- Temporary Bootstrap styling. -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>


<body>

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-md-8">


            <!-- Page heading -->

            <div class="d-flex justify-content-between align-items-center mb-4">

                <div>

                    <h1>
                        Edit Product
                    </h1>

                    <p class="text-muted mb-0">
                        Update product information.
                    </p>

                </div>


                <a
                    href="index.php"
                    class="btn btn-secondary"
                >
                    Back
                </a>

            </div>


            <div class="card shadow-sm">

                <div class="card-body p-4">


                    <!-- Database error -->

                    <?php if (isset($errors['database'])): ?>

                        <div class="alert alert-danger">

                            <?= htmlspecialchars($errors['database']) ?>

                        </div>

                    <?php endif; ?>


                    <form
                        method="POST"
                        action=""
                        enctype="multipart/form-data"
                    >


                        <!-- ==========================================
                             CATEGORY
                             ========================================== -->

                        <div class="mb-3">

                            <label
                                for="category_id"
                                class="form-label"
                            >
                                Category
                            </label>


                            <select
                                id="category_id"
                                name="category_id"
                                class="form-select"
                                required
                            >

                                <option value="">
                                    Select Category
                                </option>


                                <?php while ($category = $categoryResult->fetch_assoc()): ?>

                                    <option
                                        value="<?= (int) $category['id'] ?>"
                                        <?= $categoryId === (int) $category['id']
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        <?= htmlspecialchars($category['name']) ?>
                                    </option>

                                <?php endwhile; ?>

                            </select>


                            <?php if (isset($errors['category_id'])): ?>

                                <div class="text-danger small mt-1">

                                    <?= htmlspecialchars($errors['category_id']) ?>

                                </div>

                            <?php endif; ?>

                        </div>


                        <!-- ==========================================
                             PRODUCT NAME
                             ========================================== -->

                        <div class="mb-3">

                            <label
                                for="name"
                                class="form-label"
                            >
                                Product Name
                            </label>


                            <input
                                type="text"
                                id="name"
                                name="name"
                                class="form-control"
                                value="<?= htmlspecialchars($name) ?>"
                                required
                            >


                            <?php if (isset($errors['name'])): ?>

                                <div class="text-danger small mt-1">

                                    <?= htmlspecialchars($errors['name']) ?>

                                </div>

                            <?php endif; ?>

                        </div>


                        <!-- ==========================================
                             SLUG
                             ========================================== -->

                        <div class="mb-3">

                            <label
                                for="slug"
                                class="form-label"
                            >
                                Slug
                            </label>


                            <input
                                type="text"
                                id="slug"
                                name="slug"
                                class="form-control"
                                value="<?= htmlspecialchars($slug) ?>"
                                required
                            >


                            <?php if (isset($errors['slug'])): ?>

                                <div class="text-danger small mt-1">

                                    <?= htmlspecialchars($errors['slug']) ?>

                                </div>

                            <?php endif; ?>

                        </div>


                        <!-- ==========================================
                             DESCRIPTION
                             ========================================== -->

                        <div class="mb-3">

                            <label
                                for="description"
                                class="form-label"
                            >
                                Description
                            </label>


                            <textarea
                                id="description"
                                name="description"
                                class="form-control"
                                rows="5"
                            ><?= htmlspecialchars($description) ?></textarea>

                        </div>


                        <!-- ==========================================
                             PRICE
                             ========================================== -->

                        <div class="mb-3">

                            <label
                                for="price"
                                class="form-label"
                            >
                                Price
                            </label>


                            <input
                                type="number"
                                id="price"
                                name="price"
                                class="form-control"
                                value="<?= htmlspecialchars($price) ?>"
                                min="0"
                                step="0.01"
                                required
                            >


                            <?php if (isset($errors['price'])): ?>

                                <div class="text-danger small mt-1">

                                    <?= htmlspecialchars($errors['price']) ?>

                                </div>

                            <?php endif; ?>

                        </div>


                        <!-- ==========================================
                             STOCK
                             ========================================== -->

                        <div class="mb-3">

                            <label
                                for="stock"
                                class="form-label"
                            >
                                Stock
                            </label>


                            <input
                                type="number"
                                id="stock"
                                name="stock"
                                class="form-control"
                                value="<?= (int) $stock ?>"
                                min="0"
                                required
                            >


                            <?php if (isset($errors['stock'])): ?>

                                <div class="text-danger small mt-1">

                                    <?= htmlspecialchars($errors['stock']) ?>

                                </div>

                            <?php endif; ?>

                        </div>


                        <!-- ==========================================
                             CURRENT IMAGE
                             ========================================== -->

                        <div class="mb-3">

                            <label class="form-label">
                                Current Image
                            </label>


                            <?php if (!empty($oldImage)): ?>

                                <div class="mb-2">

                                    <img
                                        src="../../public/uploads/products/<?= htmlspecialchars($oldImage) ?>"
                                        alt="<?= htmlspecialchars($name) ?>"
                                        width="150"
                                        height="100"
                                        style="object-fit: cover;"
                                    >

                                </div>

                            <?php else: ?>

                                <p class="text-muted">
                                    No image uploaded.
                                </p>

                            <?php endif; ?>

                        </div>


                        <!-- ==========================================
                             NEW IMAGE
                             ========================================== -->

                        <div class="mb-3">

                            <label
                                for="image"
                                class="form-label"
                            >
                                Replace Image
                            </label>


                            <input
                                type="file"
                                id="image"
                                name="image"
                                class="form-control"
                                accept=".jpg,.jpeg,.png,.webp"
                            >


                            <div class="form-text">

                                Leave empty to keep current image.
                                JPG, PNG or WEBP.
                                Maximum size: 2 MB.

                            </div>


                            <?php if (isset($errors['image'])): ?>

                                <div class="text-danger small mt-1">

                                    <?= htmlspecialchars($errors['image']) ?>

                                </div>

                            <?php endif; ?>

                        </div>


                        <!-- ==========================================
                             STATUS
                             ========================================== -->

                        <div class="mb-4">

                            <label
                                for="status"
                                class="form-label"
                            >
                                Status
                            </label>


                            <select
                                id="status"
                                name="status"
                                class="form-select"
                            >

                                <option
                                    value="1"
                                    <?= $status === 1 ? 'selected' : '' ?>
                                >
                                    Active
                                </option>


                                <option
                                    value="0"
                                    <?= $status === 0 ? 'selected' : '' ?>
                                >
                                    Inactive
                                </option>

                            </select>

                        </div>


                        <!-- Submit -->

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Update Product
                        </button>


                    </form>

                </div>

            </div>

        </div>

    </div>

</div>


</body>

</html>


<?php

// Close category statement.
$categoryStmt->close();

?>