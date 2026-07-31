<?php
if (!isset($current_page)) {
    $current_page = basename($_SERVER['PHP_SELF']);
}
if (!isset($base)) { $base = ''; } // safety fallback if caller forgot to set $base
?>

<footer class="galaxy-footer" style="background: transparent !important; border: none !important;">
    <div class="planet planet-1"></div>
    <div class="planet planet-2"></div>
    <div class="gf-container">
        <div class="gf-wrapper">
            <div class="gf-grid">
                <div>
                    <div class="gf-brand">
    <a href="<?= $base ?>index.php" style="text-decoration: none; display: flex; align-items: center; gap: 18px;">
        <div class="gf-logo">
            <img src="<?= $base ?>images/gg.png" alt="SafeKidsSpace">
        </div>
        <div>
            <h2>SafeKidsSpace</h2>
            <p>FUTURE LEARNING UNIVERSE</p>
        </div>
    </a>
</div>
                    <p class="gf-description">
                        SafeKidsSpace is a futuristic learning universe designed for kids, parents, and teachers with safe videos, learning adventures, AI education, and interactive galaxy experiences.
                    </p>
         <div class="gf-socials">
    <a href="https://accounts.google.com/signup" target="_blank" title="Create YouTube Account">
        <i class="fab fa-youtube"></i>
    </a>

    <a href="https://discord.com/register" target="_blank" title="Create Discord Account">
        <i class="fab fa-discord"></i>
    </a>

    <a href="https://www.instagram.com/accounts/emailsignup/" target="_blank" title="Create Instagram Account">
        <i class="fab fa-instagram"></i>
    </a>

    <a href="https://x.com/i/flow/signup" target="_blank" title="Create X Account">
        <i class="fab fa-x-twitter"></i>
    </a>
</div>
                </div>

                <div>
                    <h3 class="gf-title">Explore</h3>
                    <ul class="gf-links">
                        <li>
                            <a href="<?= $base ?>index.php" class="<?= ($current_page == 'index.php') ? 'active-galaxy-link' : ''; ?>">
                                <span></span> Home
                            </a>
                        </li>
                        <li>
                            <a href="<?= $base ?>videos.php" class="<?= ($current_page == 'videos.php') ? 'active-galaxy-link' : ''; ?>">
                                <span></span> Videos
                            </a>
                        </li>
                        <li>
                            <a href="<?= $base ?>learning.php" class="<?= ($current_page == 'learning.php') ? 'active-galaxy-link' : ''; ?>">
                                <span></span> Learning Zone
                            </a>
                        </li>
                        <li>
                            <a href="<?= $base ?>store/store.php" class="<?= ($current_page == 'store.php') ? 'active-galaxy-link' : ''; ?>">
                                <span></span> Kids Store
                            </a>
                        </li>
                    </ul>
                </div>

                <div>
                    <h3 class="gf-title">Support</h3>
                    <ul class="gf-links">
                        <li><a href="<?= $base ?>parent/parent_dashboard.php"><span></span> Parents</a></li>
                        <li><a href="<?= $base ?>teacher/teacher_dashboard.php"><span></span> Teachers</a></li>
                        <li><a href="<?= $base ?>careers.php"><span></span> Careers</a></li>
                        <li><a href="<?= $base ?>qa/qa.php"><span></span> FAQ</a></li>
                        <li><a href="<?= $base ?>about.php"><span></span> About Us</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="gf-title">Join The Galaxy</h3>
                    <p class="gf-description">
                        Get futuristic learning updates, premium activities, safe content, and exciting galaxy adventures weekly.
                    </p>
                    <form class="gf-newsletter" id="newsletterForm">
                        <div class="gf-newsletter-box">
                            <input type="email" name="email" id="newsletterEmail" placeholder="Enter your email address" required>
                            <button type="submit" id="newsletterBtn">Join Now</button>
                        </div>
                        <p id="newsletterMsg" style="margin-top:10px; font-size:0.85rem;"></p>
                    </form>
                </div>
            </div>

            <div class="gf-bottom">
                <p>© 2026 SafeKidsSpace • Designed For The Next Generation</p>
                <div class="gf-bottom-links">
                    <a href="<?= $base ?>admin/admin_dashboard.php">Admin</a>
                    <a href="<?= $base ?>qa/qa.php">faq</a>
                    <a href="<?= $base ?>terms.php">Privacy</a>
                    <a href="<?= $base ?>terms.php">Terms</a>
                    <a href="<?= $base ?>terms.php#cookies">Cookies</a>
                </div>
            </div>
        </div>
    </div>
</footer>

<script>
document.getElementById('newsletterForm').addEventListener('submit', function(e){
    e.preventDefault();

    const form  = e.target;
    const btn   = document.getElementById('newsletterBtn');
    const msg   = document.getElementById('newsletterMsg');
    const email = document.getElementById('newsletterEmail').value.trim();

    if (!email) return;

    btn.disabled = true;
    const originalText = btn.textContent;
    btn.textContent = 'Joining...';
    msg.textContent = '';

    fetch('<?= $base ?>account/Newslettersubscribe.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'email=' + encodeURIComponent(email)
    })
    .then(res => res.json())
    .then(data => {
        msg.textContent = data.message;
        msg.style.color = data.success ? '#34d399' : '#f87171';
        if (data.success) {
            form.reset();
        }
    })
    .catch(() => {
        msg.textContent = 'Something went wrong. Please try again.';
        msg.style.color = '#f87171';
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = originalText;
    });
});
</script>