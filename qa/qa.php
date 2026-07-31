<?php
$base = '../'; // used by includes/navbar.php, footer.php etc for depth-correct links

session_start();

// ========================================================
// 1. DATABASE CONNECTION & LOGIC (Top of File)
// ========================================================
include __DIR__ . '/../includes/db.php';
?>


<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../includes/favicon.php'; ?>

  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SafeKidsSpace - Cosmic FAQs</title>
  <link rel="stylesheet" href="../assets/layout.css">
</head>
<body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="page-wrapper">
  <div class="background-glow glow1"></div>
  <div class="background-glow glow2"></div>

  <div class="faq-container">

  <!-- new banner -->
  <section class="cosmic-hero-banner mt-4">
    <div class="hero-bg-overlay"></div>
    
    <div class="qa-hero-content">
        <div class="status-badge">
            <i class="fa-solid fa-satellite-dish"></i> System Status: Online
        </div>
        <h1>FAQ</h1>
        <p>Welcome, Explorer! Need help navigating the SafeKidsSpace galaxy? Type your questions below or contact our Mission Control pilots.</p>
        
        <div class="hero-search">
            <input type="text" id="searchFAQ" placeholder="What are you searching for, traveler?">
            <button class="search-btn"><i class="fa-solid fa-magnifying-glass"></i></button>
        </div>
    </div>
</section>
<div class="faq-grid">

<?php
$categories = $pdo->query("
    SELECT *
    FROM faq_categories
    ORDER BY display_order ASC
")->fetchAll();

$catIndex = 0;
foreach ($categories as $cat) {

    $stmt = $pdo->prepare("
        SELECT *
        FROM faqs
        WHERE category_id = ?
        AND status = 1
    ");
    $stmt->execute([$cat['id']]);
    $catFaqs = $stmt->fetchAll();

    if (empty($catFaqs)) continue; // skip empty categories entirely

    $isFirst = ($catIndex === 0);
    $catIndex++;
?>

<div class="faq-section" data-cat-id="<?= (int) $cat['id'] ?>">

    <button type="button" class="faq-category-toggle">
        <span class="faq-title-left">
            <span class="faq-icon-wrap">
                <i class="fa-solid <?= htmlspecialchars($cat['icon']) ?>"></i>
            </span>
            <span class="faq-cat-name"><?= htmlspecialchars($cat['category_name']) ?></span>
        </span>
        <span class="faq-toggle-control">
            <span class="faq-count-badge"><?= count($catFaqs) ?></span>
            <i class="fa-solid fa-chevron-down category-chevron"></i>
        </span>
    </button>

    <div class="faq-category-body">
        <?php foreach ($catFaqs as $faq) { ?>

        <div class="faq-item">

            <button class="faq-question">

                <span>
                    <i class="fa-solid fa-paper-plane paper-arrow"></i>
                    <?= htmlspecialchars($faq['question']) ?>
                </span>

                <i class="fa-solid fa-chevron-right chevron"></i>

            </button>

            <div class="faq-answer">
                <i class="fa-solid fa-paper-plane paper-arrow"></i>
                <?= htmlspecialchars($faq['answer']) ?>
            </div>

        </div>

        <?php } ?>
    </div>

</div>

<?php } ?>

</div>
    <div class="support-box mt-4">
      <h2>Need a Human Pilot?</h2>
      <p>If CosmoBot can't help, our mission control team is ready.</p>
      <button class="support-btn" id="mainSupportBtn">Contact Mission Control</button>
    </div>
  </div>
</div>
  <!-- CHATBOT INTERFACE -->
  <div class="chat-widget" id="chatWidget">
    <div class="chat-window">
      <div class="chat-header">
        <div style="background: #a855f7; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
            <i class="fa-solid fa-robot"></i>
        </div>
        <div>
          <h4 style="font-size: 0.9rem;">CosmoBot</h4>
          <span style="color: #10b981; font-size: 0.7rem;"><i class="fa-solid fa-circle" style="font-size: 6px;"></i> Online</span>
        </div>
      </div>
      
      <div class="chat-body" id="chatBody">
        <div class="msg bot"><i class="fa-solid fa-paper-plane paper-arrow"></i>Greetings! 🚀 I'm CosmoBot. How can I help you today?</div>
        
        <div class="quick-chips" id="quickChips">
            <div class="chip" onclick="askQuick('Why log in?')">Why log in?</div>
            <div class="chip" onclick="askQuick('Personal info safe')">Personal info safe?</div>
            <div class="chip" onclick="askQuick('Speacial rewards?')">Special rewards?</div>
        </div>
      </div>

      <div class="chat-footer">
        <input type="text" id="chatInput" placeholder="Type a message...">
        <button id="sendChatBtn"><i class="fa-solid fa-paper-plane"></i></button>
      </div>
    </div>
    <div class="chat-toggle" id="chatToggle">
      <i class="fa-solid fa-comments" id="toggleIcon"></i>
    </div>
  </div>

  <script>

    // FAQ Toggle
    document.querySelectorAll('.faq-question').forEach(btn => {
      btn.addEventListener('click', () => {
        const item = btn.parentElement;
        const isActive = item.classList.contains('active');
        document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('active'));
        if (!isActive) item.classList.add('active');
      });
    });

    // Category Accordion Toggle (only one category open at a time)
    // Delegated to the grid container (one listener total) so there is
    // no chance of duplicate/mismatched handlers on individual buttons.
    const faqGridEl = document.querySelector('.faq-grid');
    if (faqGridEl) {
      faqGridEl.addEventListener('click', (e) => {
        const toggle = e.target.closest('.faq-category-toggle');
        if (!toggle || !faqGridEl.contains(toggle)) return;

        const section = toggle.closest('.faq-section');
        if (!section) return;

        const wasOpen = section.classList.contains('open');

        // Close every category section first...
        faqGridEl.querySelectorAll('.faq-section.open').forEach(s => {
          s.classList.remove('open');
          s.querySelectorAll('.faq-item.active').forEach(i => i.classList.remove('active'));
        });

        // ...then reopen only the one that was clicked (unless it was
        // already the open one, in which case leave everything closed).
        if (!wasOpen) {
          section.classList.add('open');
        }
      });
    }

    // Chat Toggle
    const chatWidget = document.getElementById('chatWidget');
    const toggleIcon = document.getElementById('toggleIcon');
    document.getElementById('chatToggle').addEventListener('click', () => {
      chatWidget.classList.toggle('open');
      toggleIcon.className = chatWidget.classList.contains('open') ? 'fa-solid fa-xmark' : 'fa-solid fa-comments';
    });

    // Support Button Connector
    document.getElementById('mainSupportBtn').addEventListener('click', () => {
      chatWidget.classList.add('open');
      askQuick('How to contact support?');
    });

    // Chat Logic
    const chatInput = document.getElementById('chatInput');
    const chatBody = document.getElementById('chatBody');


    function askQuick(question) {
        handleResponse(question);
    }


function handleResponse(userText) {

    appendMsg(userText, 'user');

    fetch('chatbot.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'message=' + encodeURIComponent(userText)
    })
    .then(response => response.json())
    .then(data => {
        setTimeout(() => {
            appendMsg(data.reply, 'bot');
        }, 600);
    })
    .catch(error => {
        appendMsg('Sorry, something went wrong.', 'bot');
        console.error(error);
    });
}

    function appendMsg(text, side) {
      const div = document.createElement('div');
      div.className = `msg ${side}`;
      if(side === 'bot') {
        div.innerHTML = `<i class="fa-solid fa-paper-plane paper-arrow"></i>${text}`;
      } else {
        div.innerText = text;
      }
      chatBody.appendChild(div);
      chatBody.scrollTop = chatBody.scrollHeight;
    }

    document.getElementById('sendChatBtn').addEventListener('click', () => {
      if(chatInput.value) {
          handleResponse(chatInput.value);
          chatInput.value = '';
      }
    });

    chatInput.addEventListener('keypress', (e) => {
        if(e.key === 'Enter' && chatInput.value) {
            handleResponse(chatInput.value);
            chatInput.value = '';
        }
    });

    // FAQ Search Functionality

document.getElementById('searchFAQ').addEventListener('input', function() {
    const filter = this.value.toLowerCase().trim();
    const sections = document.querySelectorAll('.faq-section');

    sections.forEach(section => {
        let sectionHasMatch = false;
        const items = section.querySelectorAll('.faq-item');

        items.forEach(item => {
            const text = item.textContent.toLowerCase();
            if (filter === "" || text.includes(filter)) {
                item.style.display = "";
                if (filter !== "") sectionHasMatch = true;
            } else {
                item.style.display = "none";
            }
        });

        // Hide the whole category if no items match; otherwise show + auto-expand it
        if (filter === "") {
            section.style.display = "";
            section.classList.remove('open');
        } else if (sectionHasMatch) {
            section.style.display = "";
            section.classList.add('open');
        } else {
            section.style.display = "none";
            section.classList.remove('open');
        }
    });

    // When search is cleared, restore the default state (first category open)
    if (filter === "") {
        sections.forEach((s, i) => s.classList.toggle('open', i === 0));
    }
});
  </script>

  <?php include __DIR__ . '/../includes/footer.php' ?>
</body>
</html>