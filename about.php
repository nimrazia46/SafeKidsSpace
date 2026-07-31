<?php
$base = ''; // used by includes/navbar.php, footer.php etc for depth-correct links

// Set current page indicator for components
$current_page = 'aboutus.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/includes/favicon.php'; ?>

  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SafeKidsSpace - About Our Galaxy</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500&family=Orbitron:wght@700;900&family=Space+Grotesk:wght@600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="assets/layout.css">
</head>
<body class="about-page-body">

  <?php include __DIR__ . '/includes/navbar.php'; ?>

  <div class="global-starfield"></div>

  <div class="container-fluid px-4">
    <main class="main-content" id="content">

      <section class="hero-galaxy-banner my-3">
        <div class="banner-wrapper">
          <div class="banner-header text-center mb-4">
            <span class="pill-badge mb-2">Welcome to Next Gen Digital</span>
            <h1 class="main-title">About Us</h1>
            <p class="subtitle mb-0">Welcome Explorers.</p>
          </div>

          <div class="cards-grid-4">
            <div class="glow-card hero-card">
              <div class="icon-wrapper mb-3">
                <svg viewBox="0 0 24 24"><path d="M4.5 16.5c-1.5 1.26-2.5 3.19-2.5 5.5h5.5c0-2.31-1-4.24-2.5-5.5zM22 2s-4 0-7 3-4 7-4 7 2 4 2 4 4-1 7-4 3-7 3-7zM9 15l3-3M16 8a1 1 0 100-2 1 1 0 000 2z"/></svg>
              </div>
              <h3>100% Safe</h3>
              <p class="mb-0">Child friendly and ad free environment.</p>
            </div>

            <div class="glow-card hero-card">
              <div class="icon-wrapper mb-3">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg>
              </div>
              <h3>Expert Content</h3>
              <p class="mb-0">Curriculum designed by experts.</p>
            </div>

            <div class="glow-card hero-card">
              <div class="icon-wrapper mb-3">
                <svg viewBox="0 0 24 24"><path d="M12 3v4M12 17v4M3 12h4M17 12h4M5.6 5.6l2.8 2.8M15.6 15.6l2.8 2.8M5.6 18.4l2.8-2.8M15.6 8.4l2.8-2.8"/></svg>
              </div>
              <h3>Privacy First</h3>
              <p class="mb-0">We protect your children's privacy first.</p>
            </div>

            <div class="glow-card hero-card">
              <div class="icon-wrapper mb-3">
                <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
              </div>
              <h3>Loved By Parents</h3>
              <p class="mb-0">Trusted by 500,000 families worldwide.</p>
            </div>
          </div>
        </div>
      </section>

      <section class="mission-section my-5">
        <div class="mission-grid">
          <div class="mission-image-container">
            <img src="images/banner.png" alt="Our Cosmic Mission" class="mission-img">
          </div>
          <div class="mission-content">
            <span class="pill-badge mb-3">Strategic Navigation</span>
            <h2 class="mission-title">Our Cosmic Mission</h2>
            <p class="mission-text">
              We are dedicated to building infrastructure for a future where digital boundaries no longer exist. Our team maps out uncharted technical territories to engineer safe, high-velocity pathways for your enterprise data.
            </p>
            <p class="mission-text mb-0">
              By combining premium aesthetics with resilient core architecture, we transform complex multi-layered environments into beautifully functional systems designed to scale endlessly.
            </p>
          </div>
        </div>
      </section>

      <section class="trust-section my-5">
        <div class="trust-header text-center mb-4">
          <span class="pill-badge mb-2">Family Guardian Ecosystem</span>
          <h2 class="trust-title-small">Why Parents Trust Us</h2>
          <p class="trust-subtitle mb-0">Every feature is designed to protect, nurture, and optimize your child's digital exploration path safely.</p>
        </div>

        <div class="cards-grid-6-inline">
          <div class="glow-card medium-card">
            <div class="icon-wrapper mb-2">
              <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            </div>
            <h3>Safe & Secure</h3>
            <p class="mb-0">End-to-end encryption layers ensure environments stay fortified.</p>
          </div>

          <div class="glow-card medium-card">
            <div class="icon-wrapper mb-2">
              <svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 1010 10A10 10 0 0012 2zm0 14a4 4 0 01-3.16-1.55l1.41-1.42A2 2 0 0012 14a2 2 0 001.75-.97l1.41 1.42A4 4 0 0112 16zm3-7a1.5 1.5 0 11-1.5 1.5A1.5 1.5 0 0115 9zm-6 0a1.5 1.5 0 11-1.5 1.5A1.5 1.5 0 019 9z"/></svg>
            </div>
            <h3>Fun</h3>
            <p class="mb-0">Gamified modules keep minds motivated during exploration.</p>
          </div>

          <div class="glow-card medium-card">
            <div class="icon-wrapper mb-2">
              <svg viewBox="0 0 24 24"><path d="M3 3v18h18M18.7 8l-5.1 5.2-2.8-2.7L7 14.3"/></svg>
            </div>
            <h3>Progress Tracking</h3>
            <p class="mb-0">Robust dynamic diagnostic charts map active learning trends.</p>
          </div>

          <div class="glow-card medium-card">
            <div class="icon-wrapper mb-2">
              <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
            </div>
            <h3>Screen Time</h3>
            <p class="mb-0">Built-in scheduling configurations lock parameters safely.</p>
          </div>

          <div class="glow-card medium-card">
            <div class="icon-wrapper mb-2">
              <svg viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0zM12 9v4M12 17h.01"/></svg>
            </div>
            <h3>No Ads</h3>
            <p class="mb-0">Distraction-free views completely void of ad trackers.</p>
          </div>

          <div class="glow-card medium-card">
            <div class="icon-wrapper mb-2">
              <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
            </div>
            <h3>Parents Support</h3>
            <p class="mb-0">24/7 queues allow parents to resolve issues instantly.</p>
          </div>
        </div>
      </section>

      <section class="explore-section my-5">
        <div class="explore-grid">
          <div class="explore-left-cards">
            <div class="glow-card explorer-card">
              <div class="image-avatar-container mb-3">
                <img src="images/sp girl.PNG" alt="Nimra Zia" class="explorer-img">
              </div>
              <h3>Nimra Zia</h3>
              <span class="explorer-tag mb-2">Frontend Designer</span>
              <p class="mb-0">Mastering logic paths, She has solved over 400 deep-space algorithm matrices this quarter alone.</p>
            </div>

            <div class="glow-card explorer-card">
              <div class="image-avatar-container mb-3">
                <img src="images/Capture.PNG" alt="Zoha Rafi" class="explorer-img">
              </div>
              <h3>Zoha Rafi</h3>
              <span class="explorer-tag mb-2">UI Designer</span>
              <p class="mb-0">Mapping canvas elements, structures cosmic layout viewports using responsive typography trees.</p>
            </div>
          </div>

          <div class="explore-right-content">
            <span class="pill-badge mb-3">Active Discoveries</span>
            <h2 class="explore-title">Meet Our Explorers</h2>
            <p class="explore-description mb-0">
              Step into the workspaces of our top minds. These young pioneers navigate complex interface components, build workflows, and test our architecture across infinite galactic horizons every single day.
            </p>
          </div>
        </div>
      </section>

      <section class="testimonial-section my-5">
        <div class="testimonial-header text-center mb-5">
          <span class="pill-badge mb-2">Verified Reviews</span>
          <h2 class="testimonial-title mb-0">What Our Explorers Say</h2>
        </div>

        <div class="testimonial-grid">
          <div class="glow-card testimonial-card">
            <div class="quote-icon">“</div>
            <p class="testimonial-text">"Finding an environment that balances sophisticated logic development with absolute data privacy used to be a challenge. This platform exceeded our tech expectations."</p>
            <div class="user-profile">
              <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=150&auto=format&fit=crop" alt="Sarah J." class="user-avatar">
              <div class="user-info">
                <h4 class="mb-0">Sarah Jenkins</h4>
                <span>Parent & Data Architect</span>
              </div>
            </div>
          </div>

          <div class="glow-card testimonial-card">
            <div class="quote-icon">“</div>
            <p class="testimonial-text">"The dashboard analytics are incredibly detailed. I can track my daughter’s milestone solutions in real-time. Zero trackers, zero distractions— just pure engineering."</p>
            <div class="user-profile">
              <img src="images/boy ai.jfif" alt="Marcus V." class="user-avatar">
              <div class="user-info">
                <h4 class="mb-0">Marcus Vance</h4>
                <span>Lead Cryptographer</span>
              </div>
            </div>
          </div>

          <div class="glow-card testimonial-card">
            <div class="quote-icon">“</div>
            <p class="testimonial-text">"My kids treat this like a space adventure game, but the skills they are mastering are completely legitimate logic structures. Highly recommend to tech families!"</p>
            <div class="user-profile">
              <img src="images/blonde-young-woman-smiling-portrait-wearing-blue-gentle-shirt-building_158595-6612.avif" alt="Elena R." class="user-avatar">
              <div class="user-info">
                <h4 class="mb-0">Elena Rostova</h4>
                <span>Systems Engineer</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="impact-section my-5">
        <div class="impact-header text-center mb-5">
          <span class="pill-badge mb-2">Global Scale Performance</span>
          <h2 class="impact-main-title mb-0">Our System Impact</h2>
        </div>

        <div class="impact-grid">
          <div class="glow-card impact-metric-card">
            <div class="impact-number" data-target="99.9">0%</div>
            <div class="impact-label">Uptime Metric</div>
            <div class="impact-desc mb-0">Consistently live distributed networks processing global user queries.</div>
          </div>

          <div class="glow-card impact-metric-card">
            <div class="impact-number" data-target="42">0M+</div>
            <div class="impact-label">Blocks Solved</div>
            <div class="impact-desc mb-0">Algorithmic structures engineered and solved by active accounts.</div>
          </div>

          <div class="glow-card impact-metric-card">
            <div class="impact-number" data-target="180">0+</div>
            <div class="impact-label">Countries reached</div>
            <div class="impact-desc mb-0">International communities safely connected under our cosmic ecosystem.</div>
          </div>

          <div class="glow-card impact-metric-card">
            <div class="impact-number" data-target="0.0">0.0ms</div>
            <div class="impact-label">Ad Trackers</div>
            <div class="impact-desc mb-0">Absolute zero data monitoring interference across all accounts.</div>
          </div>
        </div>
      </section>
      
      <section class="join-section my-4">
        <div class="join-container-scanner">
          <div class="join-content">
            <div class="text-start">
              <h2 class="mission-title" style="font-size: 1.5rem; margin: 0; color: #fff;">Join Safe Kids Space</h2>
              <p class="mission-text" style="margin: 5px 0 0 0; color: #94a3b8; font-size: 0.9rem;">
                Secure your access today and start mapping the future.
              </p>
            </div>
            <a href="account/register.php" class="pill-badge join-started-btn" style="background: var(--lilac-glow); color: #fff; border: none; padding: 10px 30px; cursor: pointer; font-weight: 700; white-space: nowrap; transition: 0.4s; text-decoration: none; display: inline-block;">
    GET STARTED
</a>
          </div>
        </div>
      </section>

    </main>
  </div> <?php include __DIR__ . '/includes/footer.php'; ?>

  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const metrics = document.querySelectorAll(".impact-number");
      
      const runCounters = () => {
        metrics.forEach(metric => {
          const target = parseFloat(metric.getAttribute("data-target"));
          const suffix = metric.textContent.replace(/[0-9.]/g, '');
          let current = 0;
          const duration = 2000;
          const stepTime = 30;
          const steps = duration / stepTime;
          const increment = target / steps;

          if (target === 0) return;

          const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
              clearInterval(timer);
              metric.textContent = (target % 1 === 0 ? Math.floor(target) : target.toFixed(1)) + suffix;
            } else {
              metric.textContent = (target % 1 === 0 ? Math.floor(current) : current.toFixed(1)) + suffix;
            }
          }, stepTime);
        });
      };

      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if(entry.isIntersecting) {
            runCounters();
            observer.disconnect();
          }
        });
      }, { threshold: 0.2 });

      const impactSec = document.querySelector(".impact-section");
      if(impactSec) {
        observer.observe(impactSec);
      }
    });
  </script>
</body>
</html>