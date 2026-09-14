<?php

// Only logged-in admins can access this page.
require_once __DIR__ . '/../auth-check.php';

// Load database connection.
require_once __DIR__ . '/../../core/Database.php';

// Load session class for flash messages.
require_once __DIR__ . '/../../core/Session.php';


// ==========================================
// ONLY ALLOW POST REQUEST
// ==========================================

/*
 * Delete is a destructive action.
 *
 * We don't want a simple URL visit to delete data.
 * Therefore, only POST requests are accepted.
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    die('Method Not Allowed.');
}


// ==========================================
// GET CATEGORY ID FROM POST
// ==========================================

$id = isset($_POST['id'])
    ? (int) $_POST['id']
    : 0;


// ID must be valid.
if ($id <= 0) {

    die('Invalid category ID.');
}


// Create database connection.
$database = new Database();
$mysqli = $database->getConnection();


// ==========================================
// FIRST GET CATEGORY INFORMATION
// ==========================================

/*
 * We need the image filename before deleting
 * the database record.
 *
 * Why?
 *
 * Because after DELETE, we would no longer know
 * which image belonged to this category.
 */
$stmt = $mysqli->prepare(
    "SELECT image
     FROM categories
     WHERE id = ?
     LIMIT 1"
);

$stmt->bind_param("i", $id);

$stmt->execute();

$result = $stmt->get_result();


// Category doesn't exist.
if ($result->num_rows !== 1) {

    $stmt->close();

    die('Category not found.');
}


// Get category data.
$category = $result->fetch_assoc();

$stmt->close();


// Store old image filename.
$image = $category['image'];


// ==========================================
// DELETE DATABASE RECORD
// ==========================================

$stmt = $mysqli->prepare(
    "DELETE FROM categories
     WHERE id = ?"
);


/*
 * "i" = integer
 */
$stmt->bind_param("i", $id);


// Execute DELETE query.
if ($stmt->execute()) {


    // ==========================================
    // DELETE CATEGORY IMAGE
    // ==========================================

    /*
     * If this category had an image,
     * remove the physical file too.
     */
    if (!empty($image)) {

        $imagePath =
            __DIR__
            . '/../../public/uploads/categories/'
            . $image;


        if (file_exists($imagePath)) {

            unlink($imagePath);
        }
    }


    // Store success message.
    Session::flash(
        'success',
        'Category deleted successfully.'
    );


    $stmt->close();


    // Redirect to category list.
    header('Location: index.php');
    exit;


} else {

    $stmt->close();

    die('Failed to delete category.');
}