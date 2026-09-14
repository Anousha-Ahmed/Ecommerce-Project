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
// FETCH CATEGORIES
// ==========================================

/*
 * Products belong to categories.
 *
 * We fetch categories so that the admin can select
 * a category from a dropdown instead of manually
 * typing category_id.
 */
$categoryStmt = $mysqli->prepare(
    "SELECT id, name
     FROM categories
     WHERE status = 1
     ORDER BY name ASC"
);

$categoryStmt->execute();

$categoryResult = $categoryStmt->get_result();


// ==========================================
// DEFAULT FORM VALUES
// ==========================================

$name = '';
$slug = '';
$description = '';
$price = '';
$stock = 0;
$categoryId = 0;
$status = 1;


// Store validation errors.
$errors = [];


// ==========================================
// HANDLE FORM SUBMISSION
// ==========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    // Get submitted form values.
    $name = trim($_POST['name'] ?? '');

    $slug = trim($_POST['slug'] ?? '');

    $description = trim($_POST['description'] ?? '');

    $price = trim($_POST['price'] ?? '');

    $stock = isset($_POST['stock'])
        ? (int) $_POST['stock']
        : 0;

    $categoryId = isset($_POST['category_id'])
        ? (int) $_POST['category_id']
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


    // Get validator errors.
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

        /*
         * Check that price is actually numeric.
         *
         * Examples:
         * 100       ✅
         * 99.99     ✅
         * abc       ❌
         */
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
    // CHECK CATEGORY EXISTS
    // ==========================================

    if (!isset($errors['category_id'])) {

        /*
         * Never blindly trust category_id sent
         * from the browser.
         *
         * We verify that the selected category
         * actually exists and is active.
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

        $stmt = $mysqli->prepare(
            "SELECT id
             FROM products
             WHERE slug = ?
             LIMIT 1"
        );

        $stmt->bind_param(
            "s",
            $slug
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
    // IMAGE VALIDATION & UPLOAD
    // ==========================================

    $imageName = null;


    /*
     * Image is optional.
     *
     * If admin doesn't upload an image,
     * image will remain NULL.
     */
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

            /*
             * Maximum allowed size = 2 MB.
             */
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
                 * Check actual file type instead of
                 * trusting only the file extension.
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
            // MOVE IMAGE
            // ==========================================

            if (!isset($errors['image'])) {


                // Product upload directory.
                $uploadDirectory =
                    __DIR__ . '/../../public/uploads/products/';


                // Create directory if it doesn't exist.
                if (!is_dir($uploadDirectory)) {

                    mkdir(
                        $uploadDirectory,
                        0755,
                        true
                    );
                }


                /*
                 * Convert MIME type into a safe extension.
                 */
                $extensions = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp'
                ];


                $extension = $extensions[$mimeType];


                /*
                 * Generate a random filename.
                 *
                 * We don't use the original filename
                 * directly because it may contain unsafe
                 * or duplicate names.
                 */
                $imageName =
                    bin2hex(random_bytes(10))
                    . '.'
                    . $extension;


                $destination =
                    $uploadDirectory . $imageName;


                /*
                 * Move temporary uploaded file
                 * into our products upload folder.
                 */
                if (!move_uploaded_file(
                    $image['tmp_name'],
                    $destination
                )) {

                    $errors['image'] =
                        'Failed to save the uploaded image.';

                    $imageName = null;
                }
            }
        }
    }


    // ==========================================
    // INSERT PRODUCT
    // ==========================================

    if (empty($errors)) {


        /*
         * INSERT creates a new product row.
         *
         * category_id connects the product
         * with the selected category.
         */
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


        /*
         * Parameter types:
         *
         * i = category_id
         * s = name
         * s = slug
         * s = description
         * d = price
         * i = stock
         * s = image
         * i = status
         *
         * Therefore:
         * issdsisi
         */
        $priceValue = (float) $price;


        $stmt->bind_param(
            "issdsisi",
            $categoryId,
            $name,
            $slug,
            $description,
            $priceValue,
            $stock,
            $imageName,
            $status
        );


        // Execute INSERT query.
        if ($stmt->execute()) {


            // Success message for next page.
            Session::flash(
                'success',
                'Product created successfully.'
            );


            $stmt->close();


            // Redirect to products list.
            header('Location: index.php');
            exit;

        } else {


            /*
             * If database INSERT fails after image upload,
             * remove the uploaded image because it is no
             * longer needed.
             */
            if ($imageName !== null) {

                $imagePath =
                    __DIR__
                    . '/../../public/uploads/products/'
                    . $imageName;


                if (file_exists($imagePath)) {

                    unlink($imagePath);
                }
            }


            $errors['database'] =
                'Failed to create product.';
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

    <title>Create Product - Admin</title>


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


            <!-- ==========================================
                 PAGE HEADING
                 ========================================== -->

            <div class="d-flex justify-content-between align-items-center mb-4">

                <div>

                    <h1>
                        Add Product
                    </h1>

                    <p class="text-muted mb-0">
                        Create a new product.
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
                                placeholder="example-product"
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
                             IMAGE
                             ========================================== -->

                        <div class="mb-3">

                            <label
                                for="image"
                                class="form-label"
                            >
                                Product Image
                            </label>


                            <input
                                type="file"
                                id="image"
                                name="image"
                                class="form-control"
                                accept=".jpg,.jpeg,.png,.webp"
                            >


                            <div class="form-text">

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


                        <!-- ==========================================
                             SUBMIT
                             ========================================== -->

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Create Product
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