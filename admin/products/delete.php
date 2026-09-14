<?php

// Only logged-in admins can access this page.
require_once __DIR__ . '/../auth-check.php';

// Load required classes.
require_once __DIR__ . '/../../core/Database.php';
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
 * Delete button index.php se id bhejta hai:
 *
 * delete.php?id=1
 *
 * $_GET['id'] mein product ID milegi.
 */
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;


// Check whether ID is valid.
if ($id <= 0) {

    Session::flash(
        'error',
        'Invalid product ID.'
    );

    header('Location: index.php');
    exit;
}


// ==========================================
// GET PRODUCT IMAGE
// ==========================================

/*
 * Delete karne se pehle product ki image ka
 * filename nikal rahe hain.
 *
 * Database row delete hone ke baad humein
 * image ka naam nahi milega.
 */
$stmt = $mysqli->prepare(
    "SELECT image
     FROM products
     WHERE id = ?
     LIMIT 1"
);

$stmt->bind_param(
    "i",
    $id
);

$stmt->execute();

$result = $stmt->get_result();


// Product doesn't exist.
if ($result->num_rows !== 1) {

    $stmt->close();

    Session::flash(
        'error',
        'Product not found.'
    );

    header('Location: index.php');
    exit;
}


// Get product data.
$product = $result->fetch_assoc();

$oldImage = $product['image'];

$stmt->close();


// ==========================================
// DELETE PRODUCT
// ==========================================

$stmt = $mysqli->prepare(
    "DELETE FROM products
     WHERE id = ?"
);

$stmt->bind_param(
    "i",
    $id
);


// Execute DELETE query.
if ($stmt->execute()) {


    // ==========================================
    // DELETE PRODUCT IMAGE
    // ==========================================

    /*
     * Database row successfully delete hone ke
     * baad associated image ko bhi filesystem se
     * remove kar rahe hain.
     */
    if (!empty($oldImage)) {

        $imagePath =
            __DIR__
            . '/../../public/uploads/products/'
            . $oldImage;


        if (file_exists($imagePath)) {

            unlink($imagePath);
        }
    }


    // Success message.
    Session::flash(
        'success',
        'Product deleted successfully.'
    );

} else {


    // Database deletion failed.
    Session::flash(
        'error',
        'Failed to delete product.'
    );
}


$stmt->close();


// ==========================================
// REDIRECT
// ==========================================

/*
 * Delete ke baad user ko products list par
 * wapas bhej dete hain.
 */
header('Location: index.php');
exit;