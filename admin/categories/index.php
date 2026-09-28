<?php

// Protect this page: only logged-in admins can access it.
require_once __DIR__ . '/../auth-check.php';

// Load database connection class.
require_once __DIR__ . '/../../core/Database.php';

// Load Session class for flash messages.
require_once __DIR__ . '/../../core/Session.php';

$database = new Database();
$mysqli = $database->getConnection();

$successMessage = Session::getFlash('success');
$errorMessage = Session::getFlash('error');

// Fetch all categories from the database.
$stmt = $mysqli->prepare(
    "SELECT id, name, slug, image, status, created_at
     FROM categories
     ORDER BY id DESC"
);

$stmt->execute();

$result = $stmt->get_result();

$basePath = '../';
$pageTitle = 'Categories';

require_once __DIR__ . '/../../includes/admin-header.php';
?>

    <?php if ($successMessage): ?>
        <div class="alert alert-success text-white">
            <?= htmlspecialchars($successMessage) ?>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
        <div class="alert alert-danger text-white">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <div class="card mb-4">

                <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                    <h6>Categories</h6>
                    <a href="create.php" class="btn bg-gradient-dark btn-sm mb-0">
                        <i class="material-symbols-rounded text-sm align-middle">add</i>
                        Add Category
                    </a>
                </div>

                <div class="card-body px-0 pt-0 pb-2">
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-xs font-weight-bolder opacity-7 ps-4">Category</th>
                                    <th class="text-uppercase text-xs font-weight-bolder opacity-7">Slug</th>
                                    <th class="text-uppercase text-xs font-weight-bolder opacity-7">Status</th>
                                    <th class="text-uppercase text-xs font-weight-bolder opacity-7">Created</th>
                                    <th></th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php if ($result->num_rows > 0): ?>

                                    <?php while ($category = $result->fetch_assoc()): ?>

                                        <tr>
                                            <td class="ps-4">
                                                <div class="d-flex px-2 py-1 align-items-center">
                                                    <?php if (!empty($category['image'])): ?>
                                                        <img src="../../public/uploads/categories/<?= htmlspecialchars($category['image']) ?>" class="avatar avatar-sm me-3" style="object-fit:cover;" alt="<?= htmlspecialchars($category['name']) ?>">
                                                    <?php else: ?>
                                                        <div class="avatar avatar-sm me-3 bg-gradient-secondary d-flex align-items-center justify-content-center">
                                                            <i class="material-symbols-rounded text-white text-sm">category</i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="d-flex flex-column justify-content-center">
                                                        <h6 class="mb-0 text-sm"><?= htmlspecialchars($category['name']) ?></h6>
                                                        <p class="text-xs text-secondary mb-0">#<?= (int) $category['id'] ?></p>
                                                    </div>
                                                </div>
                                            </td>

                                            <td>
                                                <p class="text-xs font-weight-bold mb-0"><?= htmlspecialchars($category['slug']) ?></p>
                                            </td>

                                            <td>
                                                <?php if ((int) $category['status'] === 1): ?>
                                                    <span class="badge badge-sm bg-gradient-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge badge-sm bg-gradient-secondary">Inactive</span>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <p class="text-xs text-secondary mb-0"><?= htmlspecialchars($category['created_at']) ?></p>
                                            </td>

                                            <td class="text-end pe-4">
                                                <a href="edit.php?id=<?= (int) $category['id'] ?>" class="text-secondary font-weight-bold text-xs" title="Edit"><i class="material-symbols-rounded text-sm align-middle">edit</i></a>
                                            </td>
                                        </tr>

                                    <?php endwhile; ?>

                                <?php else: ?>

                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">No categories found.</td>
                                    </tr>

                                <?php endif; ?>

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php

require_once __DIR__ . '/../../includes/admin-footer.php';

$stmt->close();

?>