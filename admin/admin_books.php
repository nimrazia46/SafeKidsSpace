<?php
$base = '../'; // used by includes/navbar.php, footer.php etc for depth-correct links

session_start();
require_once __DIR__ . '/../includes/db.php';

// Admin only
$user_role = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : '';
if ($user_role !== 'admin' && $user_role !== 'administrator') {
    header("Location: ../index.php");
    exit();
}

$book_success = '';
$book_error   = '';

// ── Helper: validate + move an uploaded file, return filename / null / false ──
// Returns: string filename on success, null if no file was chosen, false if invalid.
function abm_save_upload($file, string $dest_dir, array $allowed_ext, int $max_bytes, string $prefix) {
    if (empty($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null; // no file chosen — caller decides if that's ok (e.g. keep existing on edit)
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_ext, true) || $file['size'] > $max_bytes) {
        return false;
    }
    if (!is_dir($dest_dir)) {
        mkdir($dest_dir, 0755, true);
    }
    $safe_name = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dest_dir . $safe_name)) {
        return false;
    }
    return $safe_name;
}

// ── Add Book ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_add_book'])) {
    $title   = trim($_POST['title']   ?? '');
    $author  = trim($_POST['author']  ?? '');
    $section = trim($_POST['section'] ?? 'Trending');

    $img_result = abm_save_upload($_FILES['img_file'] ?? null, __DIR__ . '/../images/', ['jpg','jpeg','png','webp','gif'], 5 * 1024 * 1024, 'cover');
    $pdf_result = abm_save_upload($_FILES['pdf_file'] ?? null, __DIR__ . '/../pdf/',    ['pdf'], 50 * 1024 * 1024, 'book');

    if ($title === '') {
        $book_error = "Please enter a book title.";
    } elseif ($img_result === null) {
        $book_error = "Please choose a cover image.";
    } elseif ($img_result === false) {
        $book_error = "Cover image must be JPG/PNG/WEBP/GIF and under 5MB.";
    } elseif ($pdf_result === null) {
        $book_error = "Please choose a PDF file.";
    } elseif ($pdf_result === false) {
        $book_error = "PDF must be a valid .pdf file under 50MB.";
    } else {
        try {
            $bstmt = $pdo->prepare(
                "INSERT INTO books (title, author, img_url, pdf_url, section)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $bstmt->execute([$title, $author, $img_result, $pdf_result, $section]);
            $book_success = "Book added to library!";
            notify_role($pdo, 'student', "📚 New book!", "\"$title\" just arrived in the Library!", "library.php", "fa-solid fa-book-open");
        } catch (PDOException $e) {
            $book_error = "Database error: " . htmlspecialchars($e->getMessage());
        }
    }
}

// ── Edit Book ───────────────────────────────────────────────
$is_edit_submit = isset($_POST['_edit_book']);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_edit_submit) {
    $edit_id      = intval($_POST['book_id'] ?? 0);
    $title        = trim($_POST['title'] ?? '');
    $author       = trim($_POST['author'] ?? '');
    $section      = trim($_POST['section'] ?? 'Trending');
    $existing_img = trim($_POST['current_img'] ?? '');
    $existing_pdf = trim($_POST['current_pdf'] ?? '');

    // A new file is optional here — if the admin doesn't choose one, we keep the current file.
    $img_result = abm_save_upload($_FILES['img_file'] ?? null, __DIR__ . '/../images/', ['jpg','jpeg','png','webp','gif'], 5 * 1024 * 1024, 'cover');
    $pdf_result = abm_save_upload($_FILES['pdf_file'] ?? null, __DIR__ . '/../pdf/',    ['pdf'], 50 * 1024 * 1024, 'book');

    $final_img = ($img_result === null) ? $existing_img : $img_result;
    $final_pdf = ($pdf_result === null) ? $existing_pdf : $pdf_result;

    if ($edit_id <= 0 || $title === '') {
        $book_error = "Title is required.";
    } elseif ($img_result === false) {
        $book_error = "Cover image must be JPG/PNG/WEBP/GIF and under 5MB.";
    } elseif ($pdf_result === false) {
        $book_error = "PDF must be a valid .pdf file under 50MB.";
    } elseif ($final_img === '' || $final_pdf === '') {
        $book_error = "Cover image and PDF are required.";
    } else {
        try {
            $pdo->prepare(
                "UPDATE books SET title = ?, author = ?, img_url = ?, pdf_url = ?, section = ? WHERE id = ?"
            )->execute([$title, $author, $final_img, $final_pdf, $section, $edit_id]);
            $book_success = "✏️ Book updated successfully!";
        } catch (PDOException $e) {
            $book_error = "Database error: " . htmlspecialchars($e->getMessage());
        }
    }
}

// ── Delete Book ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_delete_book'])) {
    $del_id = intval($_POST['book_id'] ?? 0);
    if ($del_id > 0) {
        try {
            $pdo->prepare("DELETE FROM books WHERE id = ?")->execute([$del_id]);
            $book_success = "🗑️ Book deleted successfully.";
        } catch (PDOException $e) {
            $book_error = "Database error: " . htmlspecialchars($e->getMessage());
        }
    }
}

// ── Fetch all books ─────────────────────────────────────────
try {
    $all_books = $pdo->query("SELECT * FROM books ORDER BY id DESC")->fetchAll();
} catch (PDOException $e) {
    $all_books = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../includes/favicon.php'; ?>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeKidsSpace — Manage Books</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@400;500;600;700;800&family=Orbitron:wght@500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/layout.css">
    <link rel="stylesheet" href="../assets/admin.css">
</head>
<body>

<?php include __DIR__ . '/../includes/admin_navbar.php'; ?>

<!-- ══════════════════════════════════════════════════════════════
     ADD BOOK MODAL POPUP
══════════════════════════════════════════════════════════════ -->
<div class="abm-overlay" id="addBookOverlay">
    <div class="abm-modal" role="dialog" aria-modal="true" aria-labelledby="abm-title">
        <div class="abm-header">
            <h2 class="abm-title" id="abm-title"><i class="fa-solid fa-book-open"></i> Add Library Book</h2>
            <button class="abm-close-btn" id="abmCloseBtn" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form action="admin_books.php" method="POST" id="addBookForm" enctype="multipart/form-data">
            <input type="hidden" name="_add_book" value="1">

            <div class="apm-group">
                <label class="apm-label" for="abm_title">Book Title <span style="color:#f87171;">*</span></label>
                <input type="text" id="abm_title" name="title" class="apm-input"
                       placeholder="e.g., Zac The Rat" required maxlength="255">
            </div>

            <div class="apm-group">
                <label class="apm-label" for="abm_author">Author</label>
                <input type="text" id="abm_author" name="author" class="apm-input"
                       placeholder="e.g., by Starfall" maxlength="255">
            </div>

            <div class="apm-group">
                <label class="apm-label" for="abm_section">Section <span style="color:#f87171;">*</span></label>
                <select id="abm_section" name="section" class="apm-select" required>
                    <option value="Trending">Trending</option>
                    <option value="Featured">Featured</option>
                    <option value="New">New</option>
                    <option value="Staff Picks">Staff Picks</option>
                </select>
                <div class="abm-section-chips">
                    <span class="abm-chip" onclick="document.getElementById('abm_section').value='Trending'">Trending</span>
                    <span class="abm-chip" onclick="document.getElementById('abm_section').value='Featured'">Featured</span>
                    <span class="abm-chip" onclick="document.getElementById('abm_section').value='New'">New Arrivals</span>
                    <span class="abm-chip" onclick="document.getElementById('abm_section').value='Staff Picks'">Staff Picks</span>
                </div>
            </div>

            <div class="apm-two-col">
                <div class="apm-group" style="margin-bottom:0;">
                    <label class="apm-label" for="abm_img">Cover Image <span style="color:#f87171;">*</span></label>
                    <input type="file" id="abm_img" name="img_file" class="apm-input"
                           accept="image/png,image/jpeg,image/webp,image/gif"
                           onchange="abmShowImgFile(this)" required>
                    <img id="abmImgPreview" class="abm-img-preview" src="" alt="Cover preview" style="display:none;">
                </div>
                <div class="apm-group" style="margin-bottom:0;">
                    <label class="apm-label" for="abm_pdf">PDF File <span style="color:#f87171;">*</span></label>
                    <input type="file" id="abm_pdf" name="pdf_file" class="apm-input"
                           accept="application/pdf" required>
                </div>
            </div>

            <button type="submit" class="abm-submit-btn" id="abmSubmitBtn">
                <span class="abm-spinner" id="abmSpinner"></span>
                <i class="fa-solid fa-circle-plus" id="abmBtnIcon"></i>
                <span id="abmBtnText">Add Book to Library</span>
            </button>
        </form>
    </div>
</div><!-- /.abm-overlay -->

<!-- ══════════════════════════════════════════════════════════════
     EDIT BOOK MODAL POPUP (single reusable modal, filled via JS)
══════════════════════════════════════════════════════════════ -->
<div class="abm-overlay" id="editBookOverlay">
    <div class="abm-modal" role="dialog" aria-modal="true" aria-labelledby="ebm-title">
        <div class="abm-header">
            <h2 class="abm-title" id="ebm-title"><i class="fa-solid fa-pen"></i> Edit Book</h2>
            <button class="abm-close-btn" id="ebmCloseBtn" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form action="admin_books.php" method="POST" id="editBookForm" enctype="multipart/form-data">
            <input type="hidden" name="_edit_book" value="1">
            <input type="hidden" name="book_id" id="ebm_id">
            <input type="hidden" name="current_img" id="ebm_current_img">
            <input type="hidden" name="current_pdf" id="ebm_current_pdf">

            <div class="apm-group">
                <label class="apm-label" for="ebm_title">Book Title <span style="color:#f87171;">*</span></label>
                <input type="text" id="ebm_title" name="title" class="apm-input" required maxlength="255">
            </div>

            <div class="apm-group">
                <label class="apm-label" for="ebm_author">Author</label>
                <input type="text" id="ebm_author" name="author" class="apm-input" maxlength="255">
            </div>

            <div class="apm-group">
                <label class="apm-label" for="ebm_section">Section <span style="color:#f87171;">*</span></label>
                <select id="ebm_section" name="section" class="apm-select" required>
                    <option value="Trending">Trending</option>
                    <option value="Featured">Featured</option>
                    <option value="New">New</option>
                    <option value="Staff Picks">Staff Picks</option>
                </select>
            </div>

            <div class="apm-two-col">
                <div class="apm-group" style="margin-bottom:0;">
                    <label class="apm-label" for="ebm_img">Cover Image <span style="color:#64748b; font-weight:400; font-size:.78rem;">(leave blank to keep current)</span></label>
                    <input type="file" id="ebm_img" name="img_file" class="apm-input" accept="image/png,image/jpeg,image/webp,image/gif" onchange="ebmShowImgFile(this)">
                    <img id="ebmImgPreview" class="abm-img-preview" src="" alt="Cover preview">
                </div>
                <div class="apm-group" style="margin-bottom:0;">
                    <label class="apm-label" for="ebm_pdf">PDF File <span style="color:#64748b; font-weight:400; font-size:.78rem;">(leave blank to keep current)</span></label>
                    <input type="file" id="ebm_pdf" name="pdf_file" class="apm-input" accept="application/pdf">
                    <p id="ebmCurrentPdfName" style="color:#64748b; font-size:.78rem; margin-top:6px;"></p>
                </div>
            </div>

            <button type="submit" class="abm-submit-btn" id="ebmSubmitBtn">
                <span class="abm-spinner" id="ebmSpinner"></span>
                <i class="fa-solid fa-floppy-disk" id="ebmBtnIcon"></i>
                <span id="ebmBtnText">Save Changes</span>
            </button>
        </form>
    </div>
</div><!-- /.editBookOverlay -->

<!-- Custom confirmation modal (replaces native confirm()) -->
<div class="adc-overlay" id="adcOverlay">
    <div class="adc-modal">
        <div class="adc-icon" id="adcIcon"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <h3 class="adc-title" id="adcTitle">Are you sure?</h3>
        <p class="adc-message" id="adcMessage"></p>
        <div class="adc-actions">
            <button type="button" class="adc-btn adc-btn-cancel" id="adcCancelBtn">Cancel</button>
            <button type="button" class="adc-btn adc-btn-confirm" id="adcConfirmBtn">Yes, Confirm</button>
        </div>
    </div>
</div>

<div class="main-content ad-wrap">

    <?php if ($book_success): ?>
        <div class="ad-flash ad-flash-success" id="adFlash">
            <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($book_success) ?>
        </div>
    <?php endif; ?>
    <?php if ($book_error): ?>
        <div class="ad-flash ad-flash-error" id="adFlash">
            <i class="fa-solid fa-triangle-exclamation"></i> <?= $book_error ?>
        </div>
    <?php endif; ?>

    <div class="ad-hero">
        <div class="ad-hero-left">
            <div class="ad-hero-icon"><i class="fa-solid fa-book-open"></i></div>
            <div>
                <h1 class="ad-hero-title">Manage Books</h1>
                <p class="ad-hero-sub">Add, edit, and remove books shown in the Library</p>
                <span class="ad-hero-badge"><i class="fa-solid fa-circle-check"></i> <?= count($all_books) ?> Total Books</span>
            </div>
        </div>
        <div class="ad-hero-right">
            <button type="button" class="ad-back-btn" id="openAddBookBtn" style="border:none; cursor:pointer;">
                <i class="fa-solid fa-circle-plus"></i> Add Book
            </button>
        </div>
    </div>

    <p class="ad-section-title"><i class="fa-solid fa-list"></i> All Books</p>

    <?php if (empty($all_books)): ?>
        <div class="ad-empty">
            <i class="fa-solid fa-book"></i>
            <p>No books yet. Click "Add Book" to add your first one.</p>
        </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="ad-table">
            <thead>
                <tr>
                    <th>Cover</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Section</th>
                    <th>PDF File</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all_books as $b): ?>
                <tr>
                    <td>
                        <img src="../images/<?= htmlspecialchars($b['img_url'] ?: 'banner.png') ?>"
                             style="width:44px;height:60px;object-fit:cover;border-radius:6px;" alt="">
                    </td>
                    <td style="font-weight:700;"><?= htmlspecialchars($b['title']) ?></td>
                    <td><?= htmlspecialchars($b['author'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($b['section'] ?? '—') ?></td>
                    <td style="font-size:.8rem; color:#94a3b8;"><?= htmlspecialchars($b['pdf_url'] ?? '—') ?></td>
                    <td style="white-space:nowrap;">
                        <a href="../pdf/<?= rawurlencode($b['pdf_url']) ?>" target="_blank" class="ad-back-btn" style="padding:6px 10px; font-size:.8rem;">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                        <button type="button" class="ad-back-btn adm-edit-book-btn" style="padding:6px 10px; font-size:.8rem;"
                            data-id="<?= (int)$b['id'] ?>"
                            data-title="<?= htmlspecialchars($b['title'], ENT_QUOTES) ?>"
                            data-author="<?= htmlspecialchars($b['author'] ?? '', ENT_QUOTES) ?>"
                            data-section="<?= htmlspecialchars($b['section'] ?? 'Trending', ENT_QUOTES) ?>"
                            data-img="<?= htmlspecialchars($b['img_url'] ?? '', ENT_QUOTES) ?>"
                            data-pdf="<?= htmlspecialchars($b['pdf_url'] ?? '', ENT_QUOTES) ?>">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <form action="admin_books.php" method="POST" style="display:inline;" class="ad-confirm-form" data-confirm-msg="Delete this book permanently? This cannot be undone.">
                            <input type="hidden" name="_delete_book" value="1">
                            <input type="hidden" name="book_id" value="<?= (int)$b['id'] ?>">
                            <button type="submit" class="ad-back-btn" style="padding:6px 10px; font-size:.8rem; background:rgba(248,113,113,.12); color:#f87171; border-color:rgba(248,113,113,.3);">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

</div><!-- /.main-content -->

<script>
/* ── Add Book Modal ───────────────────────────────────────── */
const abmOverlay  = document.getElementById('addBookOverlay');
const abmOpenBtn  = document.getElementById('openAddBookBtn');
const abmCloseBtn = document.getElementById('abmCloseBtn');
const abmForm     = document.getElementById('addBookForm');

function abmOpen()  { abmOverlay.classList.add('open');    document.body.style.overflow = 'hidden'; }
function abmClose() { abmOverlay.classList.remove('open'); document.body.style.overflow = ''; }

abmOpenBtn.addEventListener('click', abmOpen);
abmCloseBtn.addEventListener('click', abmClose);
abmOverlay.addEventListener('click', e => { if (e.target === abmOverlay) abmClose(); });
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') { abmClose(); ebmClose(); }
});

function abmShowImgFile(input) {
    const img = document.getElementById('abmImgPreview');
    if (input.files && input.files[0]) {
        img.src = URL.createObjectURL(input.files[0]);
        img.style.display = 'block';
    } else {
        img.style.display = 'none';
    }
}
abmForm.addEventListener('submit', function() {
    document.getElementById('abmSpinner').style.display = 'inline-block';
    document.getElementById('abmBtnIcon').style.display = 'none';
    document.getElementById('abmBtnText').textContent   = 'Saving…';
    document.getElementById('abmSubmitBtn').disabled    = true;
});
<?php if ($book_error && !$is_edit_submit): ?> abmOpen(); <?php endif; ?>

/* ── Edit Book Modal ──────────────────────────────────────── */
const ebmOverlay  = document.getElementById('editBookOverlay');
const ebmCloseBtn = document.getElementById('ebmCloseBtn');
const ebmForm     = document.getElementById('editBookForm');

function ebmOpen()  { ebmOverlay.classList.add('open');    document.body.style.overflow = 'hidden'; }
function ebmClose() { ebmOverlay.classList.remove('open'); document.body.style.overflow = ''; }

ebmCloseBtn.addEventListener('click', ebmClose);
ebmOverlay.addEventListener('click', e => { if (e.target === ebmOverlay) ebmClose(); });

// Shows the book's CURRENT cover (already on the server) when the edit modal opens
function ebmShowImg(filename) {
    const img = document.getElementById('ebmImgPreview');
    if (filename) {
        img.src = '../images/' + filename;
        img.style.display = 'block';
        img.onerror = () => { img.style.display = 'none'; };
    } else { img.style.display = 'none'; }
}
// Shows a live preview of a NEWLY chosen replacement cover file
function ebmShowImgFile(input) {
    const img = document.getElementById('ebmImgPreview');
    if (input.files && input.files[0]) {
        img.src = URL.createObjectURL(input.files[0]);
        img.style.display = 'block';
    }
}

document.querySelectorAll('.adm-edit-book-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
        document.getElementById('ebm_id').value      = btn.dataset.id;
        document.getElementById('ebm_title').value   = btn.dataset.title;
        document.getElementById('ebm_author').value  = btn.dataset.author;
        document.getElementById('ebm_section').value = btn.dataset.section;
        document.getElementById('ebm_current_img').value = btn.dataset.img;
        document.getElementById('ebm_current_pdf').value = btn.dataset.pdf;
        document.getElementById('ebm_img').value = '';
        document.getElementById('ebm_pdf').value = '';
        document.getElementById('ebmCurrentPdfName').textContent = 'Current file: ' + (btn.dataset.pdf || '—');
        ebmShowImg(btn.dataset.img);
        ebmOpen();
    });
});

ebmForm.addEventListener('submit', function() {
    document.getElementById('ebmSpinner').style.display = 'inline-block';
    document.getElementById('ebmBtnIcon').style.display = 'none';
    document.getElementById('ebmBtnText').textContent   = 'Saving…';
    document.getElementById('ebmSubmitBtn').disabled    = true;
});
<?php if ($book_error && $is_edit_submit): ?> ebmOpen(); <?php endif; ?>

/* ── Custom confirmation modal ───────────────────────────── */
(function(){
    const adcOverlay    = document.getElementById('adcOverlay');
    const adcMessage    = document.getElementById('adcMessage');
    const adcConfirmBtn = document.getElementById('adcConfirmBtn');
    const adcCancelBtn  = document.getElementById('adcCancelBtn');
    let adcPendingForm  = null;

    document.querySelectorAll('form.ad-confirm-form').forEach(function(form){
        form.addEventListener('submit', function(e){
            e.preventDefault();
            adcPendingForm = form;
            adcMessage.textContent = form.getAttribute('data-confirm-msg') || 'Are you sure you want to continue?';
            adcOverlay.classList.add('open');
        });
    });
    adcConfirmBtn.addEventListener('click', function(){
        adcOverlay.classList.remove('open');
        if (adcPendingForm) { adcPendingForm.submit(); }
    });
    adcCancelBtn.addEventListener('click', function(){
        adcOverlay.classList.remove('open');
        adcPendingForm = null;
    });
    adcOverlay.addEventListener('click', function(e){
        if (e.target === adcOverlay) {
            adcOverlay.classList.remove('open');
            adcPendingForm = null;
        }
    });
})();

/* Auto-dismiss flash alerts */
document.querySelectorAll('#adFlash').forEach(el => {
    setTimeout(() => {
        el.style.transition = 'opacity .4s ease';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 400);
    }, 6000);
});
</script>
</body>
</html>