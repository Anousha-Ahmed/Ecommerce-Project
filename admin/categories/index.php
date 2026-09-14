<?php

// Protect this page: only logged-in admins can access it.
require_once __DIR__ . '/../auth-check.php';

// Load database connection class.
require_once __DIR__ . '/../../core/Database.php';

// Load Session class for flash messages.
require_once __DIR__ . '/../../core/Session.php';


// Create database connection.
$database = new Database();
$mysqli = $database->getConnection();


// Get success flash message.
//
// This message is created in create.php after
// a category is successfully inserted.
$successMessage = Session::getFlash('success');


/*
 * READ:
 * Fetch all categories from the database.
 *
 * SELECT is used because we are reading data.
 *
 * ORDER BY id DESC means the newest category
 * will appear first.
 */
$stmt = $mysqli->prepare(
    "SELECT id, name, slug, image, status, created_at
     FROM categories
     ORDER BY id DESC"
);


// Execute the SELECT query.
$stmt->execute();


// Get the result returned by MySQL.
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Categories - Admin</title>


    <!--
        Temporary Bootstrap styling.

        Later we will replace/use the Material Dashboard
        template provided by your sir.
    -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

</head>


<body>

    <div class="container py-5">


        <!-- ==========================================
         PAGE HEADER
         ========================================== -->

        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h1 class="mb-1">
                    Categories
                </h1>

                <p class="text-muted mb-0">
                    Manage product categories.
                </p>

            </div>


            <!--
            Button to open create.php.

            create.php will be responsible for
            adding a new category.
        -->

            <a
                href="create.php"
                class="btn btn-primary">
                Add Category
            </a>

        </div>


        <!-- ==========================================
         SUCCESS MESSAGE
         ========================================== -->

        <?php if ($successMessage): ?>

            <div class="alert alert-success">

                <?= htmlspecialchars($successMessage) ?>

            </div>

        <?php endif; ?>


        <!-- ==========================================
         CATEGORIES TABLE
         ========================================== -->

        <div class="card shadow-sm">

            <div class="card-body">

                <div class="table-responsive">

                    <table
                        class="table table-bordered table-hover align-middle">

                        <!-- Table headings -->

                        <thead>

                            <tr>

                                <th>ID</th>

                                <th>Image</th>

                                <th>Name</th>

                                <th>Slug</th>

                                <th>Status</th>

                                <th>Created</th>

                                <th>Actions</th>

                            </tr>

                        </thead>


                        <!-- Table body -->

                        <tbody>


                            <?php if ($result->num_rows > 0): ?>


                                <!--
                            fetch_assoc() gets one database row
                            at a time.

                            The while loop continues until
                            there are no rows left.
                        -->

                                <?php while ($category = $result->fetch_assoc()): ?>

                                    <tr>


                                        <!-- ==================================
                                     CATEGORY ID
                                     ================================== -->

                                        <td>

                                            <?= (int) $category['id'] ?>

                                        </td>


                                        <!-- ==================================
                                     CATEGORY IMAGE
                                     ================================== -->

                                        <td>

                                            <?php if (!empty($category['image'])): ?>

                                                <img
                                                    src="../../public/uploads/categories/<?= htmlspecialchars($category['image']) ?>"
                                                    alt="<?= htmlspecialchars($category['name']) ?>"
                                                    width="70"
                                                    height="50"
                                                    style="object-fit: cover;">

                                            <?php else: ?>

                                                <span class="text-muted">

                                                    No image

                                                </span>

                                            <?php endif; ?>

                                        </td>


                                        <!-- ==================================
                                     CATEGORY NAME
                                     ================================== -->

                                        <td>

                                            <?= htmlspecialchars($category['name']) ?>

                                        </td>


                                        <!-- ==================================
                                     CATEGORY SLUG
                                     ================================== -->

                                        <td>

                                            <?= htmlspecialchars($category['slug']) ?>

                                        </td>


                                        <!-- ==================================
                                     CATEGORY STATUS
                                     ================================== -->

                                        <td>

                                            <?php if ((int) $category['status'] === 1): ?>

                                                <span class="badge bg-success">

                                                    Active

                                                </span>

                                            <?php else: ?>

                                                <span class="badge bg-secondary">

                                                    Inactive

                                                </span>

                                            <?php endif; ?>

                                        </td>


                                        <!-- ==================================
                                     CREATED DATE
                                     ================================== -->

                                        <td>

                                            <?= htmlspecialchars($category['created_at']) ?>

                                        </td>


                                        <!-- ==================================
                                     ACTIONS
                                     ================================== -->

                                        <td>


                                            <!--
                                        Edit button.

                                        Example:

                                        edit.php?id=5

                                        This tells edit.php that
                                        category ID 5 should be edited.
                                    -->

                                            <a
                                                href="edit.php?id=<?= (int) $category['id'] ?>"
                                                class="btn btn-sm btn-warning">
                                                Edit
                                            </a>


                                            <!--
                                        Delete button.

                                        delete.php will be created later.

                                        IMPORTANT:
                                        We are NOT implementing delete yet.
                                    -->

                                            <form
                                                method="POST"
                                                action="delete.php"
                                                style="display: inline;"
                                                onsubmit="return confirm('Are you sure you want to delete this category?');">
                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= (int) $category['id'] ?>">

                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-danger">
                                                    Delete
                                                </button>
                                            </form>

                                        </td>

                                    </tr>


                                <?php endwhile; ?>


                            <?php else: ?>


                                <!--
                            This is shown when the categories
                            table contains zero records.
                        -->

                                <tr>

                                    <td
                                        colspan="7"
                                        class="text-center text-muted py-4">

                                        No categories found.

                                    </td>

                                </tr>


                            <?php endif; ?>


                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>


</body>

</html>


<?php

// Close the prepared statement.
$stmt->close();

?>