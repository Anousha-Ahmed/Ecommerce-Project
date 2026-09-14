<?php

// Only logged-in admins can access this page.
require_once __DIR__ . '/../auth-check.php';

// Load database connection class.
require_once __DIR__ . '/../../core/Database.php';

// Load validation class.
require_once __DIR__ . '/../../core/Validator.php';

// Load session class for flash messages.
require_once __DIR__ . '/../../core/Session.php';


// Create database connection.
$database = new Database();
$mysqli = $database->getConnection();


// Create validator object.
$validator = new Validator();


// Store validation errors.
$errors = [];


// Store submitted values so the form
// keeps the user's input if validation fails.
$name = '';
$slug = '';
$status = 1;


// Check whether the form was submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
     * Get text values from the submitted form.
     *
     * trim() removes unnecessary spaces
     * from the beginning and end.
     */
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');

    /*
     * Convert status to integer.
     *
     * 1 = Active
     * 0 = Inactive
     */
    $status = isset($_POST['status'])
        ? (int) $_POST['status']
        : 1;


    // ==========================================
    // TEXT VALIDATION
    // ==========================================

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


    // Get validation errors.
    $errors = $validator->errors();


    // ==========================================
    // IMAGE VALIDATION
    // ==========================================

    /*
     * Check whether the user actually selected
     * an image.
     *
     * Because image is required for a new category.
     */
    if (
        !isset($_FILES['image']) ||
        $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE
    ) {

        $errors['image'] = 'Category image is required.';

    } else {

        $image = $_FILES['image'];


        /*
         * Check whether PHP reported an upload error.
         */
        if ($image['error'] !== UPLOAD_ERR_OK) {

            $errors['image'] =
                'There was an error uploading the image.';

        } else {

            /*
             * Maximum allowed image size.
             *
             * 2 * 1024 * 1024 = 2 MB
             */
            $maxFileSize = 2 * 1024 * 1024;


            if ($image['size'] > $maxFileSize) {

                $errors['image'] =
                    'Image size must not exceed 2 MB.';

            }


            /*
             * Get the actual MIME type of the uploaded file.
             *
             * We DO NOT trust the file extension alone.
             *
             * Example:
             *
             * fake.jpg
             *
             * could actually contain another file type.
             */
            if (empty($errors['image'])) {

                $mimeType = mime_content_type(
                    $image['tmp_name']
                );


                /*
                 * Only allow these image types.
                 */
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
        }
    }


    // ==========================================
    // DUPLICATE SLUG CHECK
    // ==========================================

    if ($validator->isValid() && empty($errors)) {

        $stmt = $mysqli->prepare(
            "SELECT id
             FROM categories
             WHERE slug = ?
             LIMIT 1"
        );


        // "s" means slug is a string.
        $stmt->bind_param("s", $slug);

        $stmt->execute();

        $result = $stmt->get_result();


        if ($result->num_rows > 0) {

            $errors['slug'] =
                'This slug already exists.';
        }


        $stmt->close();
    }


    // ==========================================
    // INSERT CATEGORY
    // ==========================================

    if ($validator->isValid() && empty($errors)) {

        /*
         * Create upload directory path.
         *
         * __DIR__ = admin/categories/
         *
         * ../../ = ecommerce-project/
         *
         * public/uploads/categories/
         * = destination folder
         */
        $uploadDirectory =
            __DIR__ . '/../../public/uploads/categories/';


        /*
         * Create the folder if it does not exist.
         */
        if (!is_dir($uploadDirectory)) {

            mkdir(
                $uploadDirectory,
                0755,
                true
            );
        }


        /*
         * Get the MIME type again.
         */
        $mimeType = mime_content_type(
            $_FILES['image']['tmp_name']
        );


        /*
         * Convert MIME type into a safe extension.
         *
         * We do not simply trust the user's
         * original filename.
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
         * Example:
         *
         * 8f3a9c2d1e4b6a7c8d9e.jpg
         *
         * This prevents filename collisions and
         * avoids trusting the original filename.
         */
        $fileName =
            bin2hex(random_bytes(10))
            . '.'
            . $extension;


        /*
         * Complete path where the image
         * will be stored on the server.
         */
        $destination =
            $uploadDirectory . $fileName;


        /*
         * Move the uploaded temporary file
         * into our categories upload folder.
         */
        if (!move_uploaded_file(
            $_FILES['image']['tmp_name'],
            $destination
        )) {

            $errors['image'] =
                'Failed to save the uploaded image.';
        }


        // ==========================================
        // SAVE CATEGORY IN DATABASE
        // ==========================================

        if (empty($errors)) {

            /*
             * Insert category data.
             *
             * image column stores the filename,
             * NOT the complete physical server path.
             */
            $stmt = $mysqli->prepare(
                "INSERT INTO categories
                 (name, slug, image, status)
                 VALUES (?, ?, ?, ?)"
            );


            /*
             * Parameter types:
             *
             * s = name (string)
             * s = slug (string)
             * s = image filename (string)
             * i = status (integer)
             */
            $stmt->bind_param(
                "sssi",
                $name,
                $slug,
                $fileName,
                $status
            );


            // Execute INSERT query.
            if ($stmt->execute()) {

                /*
                 * Store success message temporarily.
                 */
                Session::flash(
                    'success',
                    'Category created successfully.'
                );


                $stmt->close();


                /*
                 * Redirect to categories list.
                 *
                 * This also prevents the form from
                 * being submitted again on refresh.
                 */
                header('Location: index.php');
                exit;

            } else {

                /*
                 * If database insertion fails,
                 * remove the image we already uploaded.
                 *
                 * This prevents unused files from
                 * remaining in the uploads folder.
                 */
                if (file_exists($destination)) {

                    unlink($destination);
                }


                $errors['database'] =
                    'Failed to create category.';
            }


            $stmt->close();
        }
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

    <title>Add Category - Admin</title>


    <!--
        Temporary Bootstrap styling.

        Later we will integrate the Material Dashboard
        template provided by your sir.
    -->
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
                    Add Category
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


                    <!-- ==========================================
                         DATABASE ERROR
                         ========================================== -->

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


                        <!--
                            IMPORTANT:

                            enctype="multipart/form-data"

                            is required when uploading files.

                            Without it, $_FILES will not receive
                            the uploaded image.
                        -->


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
                             CATEGORY SLUG
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
                                placeholder="electronics"
                                required
                            >


                            <?php if (isset($errors['slug'])): ?>

                                <div class="text-danger small mt-1">

                                    <?= htmlspecialchars($errors['slug']) ?>

                                </div>

                            <?php endif; ?>

                        </div>


                        <!-- ==========================================
                             CATEGORY IMAGE
                             ========================================== -->

                        <div class="mb-3">

                            <label
                                for="image"
                                class="form-label"
                            >
                                Category Image
                            </label>


                            <input
                                type="file"
                                id="image"
                                name="image"
                                class="form-control"
                                accept=".jpg,.jpeg,.png,.webp"
                                required
                            >


                            <div class="form-text">
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


                        <!-- ==========================================
                             SUBMIT BUTTON
                             ========================================== -->

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Create Category
                        </button>


                    </form>

                </div>

            </div>

        </div>

    </div>

</div>


</body>

</html>