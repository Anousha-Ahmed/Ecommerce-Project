<?php

// Only logged-in admins can access this page.
require_once __DIR__ . '/../auth-check.php';

// Load required classes.
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Validator.php';
require_once __DIR__ . '/../../core/Session.php';


// Create database connection.
$database = new Database();
$mysqli = $database->getConnection();


// ==========================================
// GET CATEGORY ID
// ==========================================

/*
 * The Edit button from index.php sends:
 *
 * edit.php?id=2
 *
 * $_GET['id'] receives the value 2.
 */
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;


// ID must be a valid positive integer.
if ($id <= 0) {

    die('Invalid category ID.');

}


// ==========================================
// FETCH EXISTING CATEGORY
// ==========================================

$stmt = $mysqli->prepare(
    "SELECT id, name, slug, image, status
     FROM categories
     WHERE id = ?
     LIMIT 1"
);


/*
 * "i" means integer.
 *
 * Here $id is an integer, so we use:
 *
 * bind_param("i", $id)
 */
$stmt->bind_param("i", $id);

$stmt->execute();

$result = $stmt->get_result();


// Category does not exist.
if ($result->num_rows !== 1) {

    $stmt->close();

    die('Category not found.');

}


// Get existing category data.
$category = $result->fetch_assoc();

$stmt->close();


// ==========================================
// STORE EXISTING VALUES
// ==========================================

$name = $category['name'];
$slug = $category['slug'];
$status = (int) $category['status'];
$oldImage = $category['image'];


// Store validation errors.
$errors = [];


// ==========================================
// HANDLE UPDATE
// ==========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    // Get updated form values.
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');

    $status = isset($_POST['status'])
        ? (int) $_POST['status']
        : 1;


    // ==========================================
    // TEXT VALIDATION
    // ==========================================

    $validator = new Validator();

    $validator
        ->required(
            'name',
            $name,
            'Category name is required.'
        )
        ->required(
            'slug',
            $slug,
            'Category slug is required.'
        )
        ->minLength(
            'name',
            $name,
            2,
            'Category name must be at least 2 characters.'
        );


    $errors = $validator->errors();


    // ==========================================
    // CHECK DUPLICATE SLUG
    // ==========================================

    if ($validator->isValid()) {

        /*
         * We want to check whether another category
         * already uses this slug.
         *
         * We EXCLUDE the current category:
         *
         * id != ?
         *
         * Example:
         *
         * Current category = ID 2
         * Slug = clothing
         *
         * Category ID 2 having "clothing" is okay.
         *
         * But another category ID 5 having "clothing"
         * is not okay.
         */
        $stmt = $mysqli->prepare(
            "SELECT id
             FROM categories
             WHERE slug = ?
             AND id != ?
             LIMIT 1"
        );


        /*
         * First ? = slug → string
         * Second ? = id   → integer
         */
        $stmt->bind_param(
            "si",
            $slug,
            $id
        );

        $stmt->execute();

        $result = $stmt->get_result();


        if ($result->num_rows > 0) {

            $errors['slug'] =
                'This slug already exists.';

        }

        $stmt->close();
    }


    // ==========================================
    // IMAGE HANDLING
    // ==========================================

    /*
     * By default, keep the old image.
     *
     * If user does not select a new image,
     * old image will remain unchanged.
     */
    $newImage = $oldImage;


    /*
     * Check whether user selected a new image.
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
            // FILE SIZE VALIDATION
            // ==========================================

            /*
             * Maximum file size = 2 MB.
             */
            $maxFileSize = 2 * 1024 * 1024;


            if ($image['size'] > $maxFileSize) {

                $errors['image'] =
                    'Image size must not exceed 2 MB.';

            }


            // ==========================================
            // MIME TYPE VALIDATION
            // ==========================================

            if (!isset($errors['image'])) {

                /*
                 * Check the actual MIME type.
                 *
                 * We don't trust only the extension.
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
            // UPLOAD NEW IMAGE
            // ==========================================

            if (!isset($errors['image'])) {


                // Category image upload directory.
                $uploadDirectory =
                    __DIR__ . '/../../public/uploads/categories/';


                // Create directory if it doesn't exist.
                if (!is_dir($uploadDirectory)) {

                    mkdir(
                        $uploadDirectory,
                        0755,
                        true
                    );
                }


                /*
                 * Convert MIME type into safe extension.
                 */
                $extensions = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp'
                ];


                $extension = $extensions[$mimeType];


                /*
                 * Generate a random filename.
                 */
                $fileName =
                    bin2hex(random_bytes(10))
                    . '.'
                    . $extension;


                // Complete destination path.
                $destination =
                    $uploadDirectory . $fileName;


                /*
                 * Move uploaded file to permanent folder.
                 */
                if (move_uploaded_file(
                    $image['tmp_name'],
                    $destination
                )) {

                    // New image successfully uploaded.
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

    if ($validator->isValid() && empty($errors)) {


        /*
         * UPDATE changes an existing database row.
         *
         * WHERE id = ?
         * ensures that ONLY the selected category
         * is updated.
         */
        $stmt = $mysqli->prepare(
            "UPDATE categories
             SET name = ?,
                 slug = ?,
                 image = ?,
                 status = ?
             WHERE id = ?"
        );


        /*
         * Parameter types:
         *
         * name   = string
         * slug   = string
         * image  = string
         * status = integer
         * id     = integer
         */
        $stmt->bind_param(
            "sssii",
            $name,
            $slug,
            $newImage,
            $status,
            $id
        );


        // Execute UPDATE query.
        if ($stmt->execute()) {


            /*
             * If a NEW image was uploaded,
             * delete the OLD image from the server.
             *
             * We don't delete it before successful
             * database update because we may still
             * need the old image if something fails.
             */
            if (
                $newImage !== $oldImage &&
                !empty($oldImage)
            ) {

                $oldImagePath =
                    __DIR__
                    . '/../../public/uploads/categories/'
                    . $oldImage;


                if (file_exists($oldImagePath)) {

                    unlink($oldImagePath);
                }
            }


            Session::flash(
                'success',
                'Category updated successfully.'
            );


            $stmt->close();


            // Redirect back to category list.
            header('Location: index.php');
            exit;

        } else {


            /*
             * If database update failed AND a new image
             * was uploaded, remove the new image.
             *
             * Otherwise an unused file would remain
             * inside uploads.
             */
            if (
                $newImage !== $oldImage
            ) {

                $newImagePath =
                    __DIR__
                    . '/../../public/uploads/categories/'
                    . $newImage;


                if (file_exists($newImagePath)) {

                    unlink($newImagePath);
                }
            }


            $errors['database'] =
                'Failed to update category.';
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

    <title>Edit Category - Admin</title>


    <!-- Temporary Bootstrap styling. -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>


<body>

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-md-7">


            <!-- Page heading -->

            <div class="d-flex justify-content-between align-items-center mb-4">

                <h1>
                    Edit Category
                </h1>


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
                             CATEGORY NAME
                             ========================================== -->

                        <div class="mb-3">

                            <label
                                for="name"
                                class="form-label"
                            >
                                Category Name
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
                             CURRENT IMAGE
                             ========================================== -->

                        <div class="mb-3">

                            <label class="form-label">
                                Current Image
                            </label>


                            <?php if (!empty($oldImage)): ?>

                                <div class="mb-2">

                                    <img
                                        src="../../public/uploads/categories/<?= htmlspecialchars($oldImage) ?>"
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
                                Leave empty to keep the current image.
                                JPG, PNG or WEBP. Maximum size: 2 MB.
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
                            Update Category
                        </button>


                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

</body>

</html>