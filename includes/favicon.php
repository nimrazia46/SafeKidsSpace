<?php
// ============================================================
// Shared Favicon Include
// ============================================================
// Include this ONE file in the <head> of every page instead of
// writing the <link rel="icon" ...> tag separately everywhere.
//
// To change the favicon image or force browsers to refresh it:
//   - Change the filename below (upload the new image to /images/ first), or
//   - Just bump the "?v=2" number (e.g. to "?v=3") to bust the browser cache.
// That's the ONLY place you'll ever need to edit again.
//
// Usage in any page's <head>:
//   <?php include __DIR__ . '/../includes/favicon.php'; 
// (Adjust the '../' depending on how deep the page is from the project root.)
// ============================================================

// $base should already be defined near the top of the including page
// (e.g. $base = '../';). Fall back to './' if it isn't set.
$favicon_base = isset($base) ? $base : './';
?>
<link rel="icon" type="image/png" href="<?= $favicon_base ?>images/gg.png?v=2">
