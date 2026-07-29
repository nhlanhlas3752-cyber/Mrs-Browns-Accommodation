/* ================================================================
   NestEd Living — Main JavaScript
   No external libraries — vanilla JS only.

   Contents:
   1.  HEADER HTML template
   2.  FOOTER HTML template
   3.  initLayout()          — inject header/footer, set active nav
   4.  setActiveNav()        — highlight current-page link
   5.  initMobileNav()       — hamburger toggle
   6.  initCarousel()        — prev/next scroll for residence carousel
   7.  initFAQAccordion()    — expand / collapse FAQ items
   8.  initResidenceFilter() — filter pill buttons on residences.html
   9.  Boot (DOMContentLoaded)
   ================================================================ */


/* ----------------------------------------------------------------
   0. RESIDENCES DATA
   Add or remove entries here to update the "Find Your Room" cards.
   ---------------------------------------------------------------- */
const RESIDENCES = [
  {
    name:      'House 25',
    region:    'Botanic Grove',
    roomsFrom: 'R 3 500',
    email:     'harbour@hilltrust.co.za',
    phone:     '035 000 9012',
    image:     '750210963_28478943978375600_9013287673118508894_n.jpg',
  },
  {
    name:      'House 29',
    region:    'Botanic Grove',
    roomsFrom: 'R 3 500',
    email:     'westview@hilltrust.co.za',
    phone:     '031 000 1234',
    image:     'hause 29.jpg',
  },
  {
    name:      'House 35',
    region:    'Botanic Grove',
    roomsFrom: 'R 3 500',
    email:     'meridian@hilltrust.co.za',
    phone:     '035 000 5678',
    image:     'exterior view.jpg',
  },
  {
    name:      'House 39',
    region:    'Botanic Grove',
    roomsFrom: 'R 3 500',
    email:     'meridian@hilltrust.co.za',
    phone:     '035 000 5678',
    image:     'hause39.jpg',
  },
  { name: 'Hilret Trust', region: 'Botanic Grove', roomsFrom: '', email: '', phone: '', image: 'DSC_0001.jpg' },
  { name: 'Hilret Trust', region: 'Botanic Grove', roomsFrom: '', email: '', phone: '', image: 'DSC_0012.jpg' },
  { name: 'Hilret Trust', region: 'Botanic Grove', roomsFrom: '', email: '', phone: '', image: 'IMG-20251001-WA0060.jpg' },
];

const _PIN_SVG = `<svg width="19" height="19" viewBox="0 0 24 24" fill="#DDA362" aria-hidden="true"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z"/></svg>`;

function _buildResCard(r) {
  return `
    <div class="res-card">
      <div class="res-card__img-wrap">
        <img class="res-card__img" src="${encodeURI(r.image)}" alt="${r.name}" loading="lazy">
      </div>
    </div>`;
}


/* ----------------------------------------------------------------
   1. HEADER HTML TEMPLATE
   Swap: update logo text, nav hrefs, social hrefs.
   ---------------------------------------------------------------- */
const HEADER_HTML = `
  <div class="site-header__inner">

    <!-- Logos group: Hilret Trust + Durban Municipality -->
    <div class="logos-group">
      <a href="index.html" class="site-logo" aria-label="Hilret Trust — Home">
        <div class="logo-mark">
          <span class="logo-mark__adowa">Hilret</span>
          <span class="logo-mark__living">Trust</span>
          <svg class="logo-mark__swoosh" viewBox="0 0 110 10" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M3 7.5 Q27.5 2 55 5.5 Q82.5 9 107 4" stroke="#FE6600" stroke-width="2" fill="none" stroke-linecap="round"/>
          </svg>
        </div>
      </a>
      <div class="logo-divider" aria-hidden="true"></div>
      <a href="https://www.durban.gov.za" class="partner-logo" aria-label="eThekwini Municipality" target="_blank" rel="noopener noreferrer">
        <img src="durban-municipality-logo.png" alt="eThekwini Municipality" class="partner-logo__img">
      </a>
    </div>

    <!-- Desktop nav row (links + CTA buttons) — hidden below 992 px -->
    <div class="nav-row">
      <nav class="site-nav" aria-label="Main navigation">
        <a href="about.html"      data-page="about.html">About</a>
        <a href="residences.html" data-page="residences.html">Residences</a>
        <a href="faqs.html"       data-page="faqs.html">FAQs</a>
        <a href="#footer-contact">Contact</a>
      </nav>
      <div class="header-ctas">
        <a href="terms.html" class="btn btn--ghost">T's &amp; C's</a>
        <a href="apply.html" class="btn btn--primary">Apply</a>
      </div>
    </div>

    <!-- Mobile hamburger -->
    <button
      class="nav-toggle"
      aria-label="Toggle navigation menu"
      aria-expanded="false"
      aria-controls="mobile-nav"
    >
      <span></span>
      <span></span>
      <span></span>
    </button>
  </div>

  <!-- Mobile navigation panel (visible on small screens) -->
  <nav class="mobile-nav" id="mobile-nav" aria-label="Mobile navigation">
    <a href="about.html"      data-page="about.html">About</a>
    <a href="residences.html" data-page="residences.html">Residences</a>
    <a href="faqs.html"       data-page="faqs.html">FAQs</a>
    <a href="#footer-contact">Contact</a>
    <div class="mobile-ctas">
      <a href="terms.html" class="btn btn--ghost">T's &amp; C's</a>
      <a href="apply.html" class="btn btn--primary">Apply</a>
    </div>
  </nav>
`;


/* ----------------------------------------------------------------
   2. FOOTER HTML TEMPLATE
   Swap: update social hrefs, privacy policy href,
         copyright entity name, and agency credit link.
   ---------------------------------------------------------------- */
const FOOTER_HTML = `
  <div class="site-footer__inner" id="footer-contact">

    <!-- Left column: eyebrow + heading + social icons + meta -->
    <div class="footer-left">
      <span class="footer-eyebrow">CONTACT US TODAY</span>
      <h2 class="footer-heading">Get in touch</h2>

      <div class="footer-social" aria-label="Social media links">
        <a href="#" class="footer-social-btn" aria-label="Instagram">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
          </svg>
        </a>
        <a href="#" class="footer-social-btn" aria-label="TikTok">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
          </svg>
        </a>
        <a href="#" class="footer-social-btn" aria-label="Facebook">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
          </svg>
        </a>
        <a href="#" class="footer-social-btn" aria-label="WhatsApp">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
          </svg>
        </a>
      </div>

      <div class="footer-meta">
        <a href="/privacy.html" class="footer-meta__link">Privacy Policy</a>
        <a href="/terms.html" class="footer-meta__link">Terms &amp; Conditions</a>
        <span class="footer-meta__text">&copy; <span id="footer-year"></span> Hilret Trust (Pty) Ltd.</span>
        <span class="footer-meta__text">Website by <a href="https://www.alphacoretech.co.za" target="_blank" rel="noopener" class="footer-meta__link">Alphacoretech</a></span>
        <!-- Structured NAP for local SEO — visible to search engines -->
        <address class="footer-meta__text" itemscope itemtype="https://schema.org/LodgingBusiness" style="font-style:normal">
          <span itemprop="name" style="display:none">Hilret Trust</span>
          <span itemprop="address" itemscope itemtype="https://schema.org/PostalAddress" style="display:none">
            <span itemprop="streetAddress">20 Botanic Grove</span>,
            <span itemprop="addressLocality">Berea</span>,
            <span itemprop="addressRegion">KwaZulu-Natal</span>
            <span itemprop="postalCode">4001</span>,
            <span itemprop="addressCountry">ZA</span>
          </span>
          <span itemprop="telephone" style="display:none">+27310001234</span>
          <span itemprop="email" style="display:none">info@hilrettrust.co.za</span>
          20 Botanic Grove, Berea, Durban &mdash; KwaZulu-Natal
        </address>
      </div>
    </div>

    <!-- Google Map -->
    <div class="footer-map" style="margin-bottom:24px">
      <iframe
        title="Hilret Trust location — 20 Botanic Grove, Berea, Durban"
        src="https://maps.google.com/maps?q=-29.8535491,31.0019499&output=embed&z=18"
        width="100%" height="220"
        style="border:0;border-radius:8px;display:block"
        allowfullscreen="" loading="lazy"
        referrerpolicy="no-referrer-when-downgrade">
      </iframe>
    </div>

    <!-- Right column: contact form -->
    <div class="footer-right">
      <form class="footer-form" id="footer-contact-form" novalidate>
        <label class="footer-form__label">
          Full Name
          <input type="text" name="name" placeholder="Your full name" autocomplete="name" required>
        </label>
        <label class="footer-form__label">
          Email Address
          <input type="email" name="email" placeholder="you@example.com" autocomplete="email" required>
        </label>
        <label class="footer-form__label">
          Message
          <textarea name="message" rows="6" placeholder="Type your message here…" required></textarea>
        </label>
        <button type="submit" class="footer-submit" id="footer-submit-btn">Submit</button>
        <div id="footer-form-msg" style="display:none;margin-top:10px;padding:10px 14px;border-radius:6px;font-size:.85rem"></div>
      </form>
    </div>

  </div>
`;


/* ----------------------------------------------------------------
   3. initLayout() — inject header + footer, then wire up sub-systems
   ---------------------------------------------------------------- */
function initLayout() {
  const headerEl = document.getElementById('site-header');
  const footerEl = document.getElementById('site-footer');

  // Load Montserrat (Gotham substitute) + Dancing Script (logo cursive) once
  if (!document.querySelector('link[data-nested-font="montserrat"]')) {
    const fontLink = document.createElement('link');
    fontLink.rel        = 'stylesheet';
    fontLink.href       = 'https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;900&family=Dancing+Script:wght@600;700&display=swap';
    fontLink.dataset.nestedFont = 'montserrat';
    document.head.appendChild(fontLink);
  }

  if (headerEl) {
    headerEl.className = 'site-header';
    headerEl.innerHTML = HEADER_HTML;
    setActiveNav();
    initMobileNav();
  }

  if (footerEl) {
    footerEl.className = 'site-footer';
    footerEl.innerHTML = FOOTER_HTML;
    const yearEl = document.getElementById('footer-year');
    if (yearEl) yearEl.textContent = new Date().getFullYear();
  }
}


/* ----------------------------------------------------------------
   4. setActiveNav() — add .active to the link matching the current page
   ---------------------------------------------------------------- */
function setActiveNav() {
  // Works for both file:// and http:// origins
  const currentFile = window.location.pathname.split('/').pop() || 'index.html';
  document.querySelectorAll('[data-page]').forEach(link => {
    if (link.dataset.page === currentFile) {
      link.classList.add('active');
      link.setAttribute('aria-current', 'page');
    }
  });
}


/* ----------------------------------------------------------------
   5. initMobileNav() — hamburger open / close
   ---------------------------------------------------------------- */
function initMobileNav() {
  const toggle    = document.querySelector('.nav-toggle');
  const mobileNav = document.getElementById('mobile-nav');
  const header    = document.querySelector('.site-header');
  if (!toggle || !mobileNav || !header) return;

  toggle.addEventListener('click', () => {
    const isOpen = mobileNav.classList.toggle('open');
    header.classList.toggle('nav-open', isOpen);
    toggle.setAttribute('aria-expanded', String(isOpen));
    // Prevent body scroll while menu is open
    document.body.style.overflow = isOpen ? 'hidden' : '';
  });

  // Close on any link click inside the panel
  mobileNav.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', () => {
      mobileNav.classList.remove('open');
      header.classList.remove('nav-open');
      toggle.setAttribute('aria-expanded', 'false');
      document.body.style.overflow = '';
    });
  });

  // Close on Escape key
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && mobileNav.classList.contains('open')) {
      mobileNav.classList.remove('open');
      header.classList.remove('nav-open');
      toggle.setAttribute('aria-expanded', 'false');
      document.body.style.overflow = '';
      toggle.focus();
    }
  });
}


/* ----------------------------------------------------------------
   6. initCarousel(carouselId)
   Wires up prev/next buttons to horizontally scroll a carousel.
   The carousel element must be inside a .carousel-wrapper that
   contains .carousel-btn--prev and .carousel-btn--next siblings.
   ---------------------------------------------------------------- */
function initCarousel(carouselId) {
  const carousel = document.getElementById(carouselId);
  if (!carousel) return;

  const wrapper = carousel.closest('.carousel-wrapper');
  if (!wrapper) return;

  const prevBtn = wrapper.querySelector('.carousel-btn--prev');
  const nextBtn = wrapper.querySelector('.carousel-btn--next');
  if (!prevBtn || !nextBtn) return;

  // Scroll by one card width + gap (gap is 24 px / 1.5 rem)
  const scrollStep = () => {
    const firstChild = carousel.firstElementChild;
    return firstChild ? firstChild.offsetWidth + 24 : 340;
  };

  prevBtn.addEventListener('click', () => {
    carousel.scrollBy({ left: -scrollStep(), behavior: 'smooth' });
  });
  nextBtn.addEventListener('click', () => {
    carousel.scrollBy({ left:  scrollStep(), behavior: 'smooth' });
  });

  // Grey out buttons at scroll boundaries
  const updateButtons = () => {
    prevBtn.disabled = carousel.scrollLeft <= 0;
    nextBtn.disabled =
      carousel.scrollLeft >= carousel.scrollWidth - carousel.clientWidth - 1;
  };

  carousel.addEventListener('scroll', updateButtons, { passive: true });
  updateButtons();
}


/* ----------------------------------------------------------------
   7. initFAQAccordion()
   Expand / collapse .faq-item rows.
   Only one item open at a time (accordion behaviour).
   ---------------------------------------------------------------- */
function initFAQAccordion() {
  const items = document.querySelectorAll('.faq-item');
  if (!items.length) return;

  items.forEach(item => {
    const trigger = item.querySelector('.faq-item__trigger');
    if (!trigger) return;

    trigger.addEventListener('click', () => {
      const isOpen = item.classList.contains('open');

      // Close all items
      items.forEach(other => {
        other.classList.remove('open');
        const t = other.querySelector('.faq-item__trigger');
        if (t) t.setAttribute('aria-expanded', 'false');
      });

      // Open this one if it was previously closed
      if (!isOpen) {
        item.classList.add('open');
        trigger.setAttribute('aria-expanded', 'true');
      }
    });
  });
}


/* ----------------------------------------------------------------
   8. initResidenceFilter()
   Filter pill buttons on residences.html.
   Each .property-card must have a data-region attribute.
   ---------------------------------------------------------------- */
function initResidenceFilter() {
  const filterBtns = document.querySelectorAll('.filter-btn');
  const cards      = document.querySelectorAll('.property-card');
  if (!filterBtns.length || !cards.length) return;

  filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      // Toggle active pill
      filterBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      const region = btn.dataset.region;

      cards.forEach(card => {
        const matches = region === 'all' || card.dataset.region === region;
        card.style.display = matches ? '' : 'none';
      });
    });
  });
}


/* ----------------------------------------------------------------
   9a. initFindYourRoom() — render residence cards from RESIDENCES array
   ---------------------------------------------------------------- */
function initFindYourRoom() {
  const scroll = document.getElementById('find-room-scroll');
  if (!scroll) return;
  scroll.innerHTML = RESIDENCES.map(_buildResCard).join('');
}


/* ----------------------------------------------------------------
   9. initFAQSearch()
   Live search filtering on faqs.html.
   Hides .faq-item rows whose question + answer text doesn't match
   the query, then hides any .faq-group whose items are all hidden.
   Shows a "no results" message when nothing matches.
   ---------------------------------------------------------------- */
function initFAQSearch() {
  const form      = document.querySelector('.faq-search-form');
  const input     = document.getElementById('faq-search-input');
  const noResults = document.querySelector('.faq-no-results');
  if (!form || !input) return;

  const run = () => {
    const query = input.value.trim().toLowerCase();

    // Show / hide individual items
    document.querySelectorAll('.faq-item').forEach(item => {
      if (!query) {
        item.hidden = false;
        return;
      }
      const trigger = item.querySelector('.faq-item__trigger');
      const body    = item.querySelector('.faq-item__body');
      const text    = [trigger, body].map(el => el ? el.textContent : '').join(' ').toLowerCase();
      item.hidden = !text.includes(query);
    });

    // Hide groups where every item is hidden
    document.querySelectorAll('.faq-group').forEach(group => {
      const anyVisible = [...group.querySelectorAll('.faq-item')].some(i => !i.hidden);
      group.hidden = !anyVisible;
    });

    // "No results" message
    if (noResults) {
      const anyGroup = [...document.querySelectorAll('.faq-group')].some(g => !g.hidden);
      noResults.classList.toggle('visible', !!query && !anyGroup);
    }
  };

  // Run on submit (button click or Enter key)
  form.addEventListener('submit', e => { e.preventDefault(); run(); });

  // Also run on each keystroke so results update in real time
  input.addEventListener('input', run);

  // Clear search when input is emptied via the browser's × button
  input.addEventListener('search', run);
}


/* ----------------------------------------------------------------
   10. initFooterForm() — submit enquiry to backend via fetch
   ---------------------------------------------------------------- */
function initFooterForm() {
  const form    = document.getElementById('footer-contact-form');
  const btn     = document.getElementById('footer-submit-btn');
  const msgBox  = document.getElementById('footer-form-msg');
  if (!form) return;

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    if (btn) { btn.disabled = true; btn.textContent = 'Sending…'; }

    fetch('/backend/submit-enquiry.php', {
      method: 'POST',
      body: new FormData(form),
    })
      .then(r => r.json())
      .then(data => {
        msgBox.style.display = 'block';
        if (data.success) {
          msgBox.style.background = '#D1FAE5';
          msgBox.style.color = '#065F46';
          msgBox.style.border = '1px solid #A7F3D0';
          msgBox.textContent = 'Message sent! We\'ll be in touch soon.';
          form.reset();
          if (btn) { btn.disabled = false; btn.textContent = 'Submit'; }
        } else {
          msgBox.style.background = '#FEE2E2';
          msgBox.style.color = '#991B1B';
          msgBox.style.border = '1px solid #FECACA';
          msgBox.textContent = (data.errors || [data.error || 'Something went wrong.']).join(' ');
          if (btn) { btn.disabled = false; btn.textContent = 'Submit'; }
        }
      })
      .catch(() => {
        if (btn) { btn.disabled = false; btn.textContent = 'Submit'; }
        msgBox.style.display = 'block';
        msgBox.style.background = '#FEE2E2';
        msgBox.style.color = '#991B1B';
        msgBox.textContent = 'Network error. Please try again.';
      });
  });
}


/* ----------------------------------------------------------------
   11. initAvailability()
   Fetches live room availability from the backend API and updates
   the .hero__avail-item strips on the homepage.
   Each item's <span class="hero__avail-name"> must match the
   residence name in the database (e.g. "House 29").
   ---------------------------------------------------------------- */
function initAvailability() {
  const strip = document.querySelector('.hero__availability');
  if (!strip) return;                           // not on homepage, skip

  fetch('/backend/api/residences.php')
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (!data.success || !data.residences) return;

      var items = strip.querySelectorAll('.hero__avail-item');
      items.forEach(function (item) {
        var nameEl   = item.querySelector('.hero__avail-name');
        var statusEl = item.querySelector('.hero__avail-status');
        var dotEl    = item.querySelector('.hero__avail-dot');
        if (!nameEl || !statusEl || !dotEl) return;

        var houseName = nameEl.textContent.trim();           // e.g. "House 29"

        // Find the matching residence from the API
        var res = data.residences.find(function (r) {
          return r.name.trim().toLowerCase() === houseName.toLowerCase();
        });
        if (!res) return;

        var avail = parseInt(res.available_rooms, 10) || 0;

        // Update status text
        if (avail === 0) {
          statusEl.textContent = 'No rooms available';
        } else if (avail === 1) {
          statusEl.textContent = '(1) room available';
        } else {
          statusEl.textContent = '(' + avail + ') rooms available';
        }

        // Update dot colour
        dotEl.classList.remove('hero__avail-dot--available', 'hero__avail-dot--full');
        dotEl.classList.add(avail > 0 ? 'hero__avail-dot--available' : 'hero__avail-dot--full');
      });
    })
    .catch(function () {
      // API unreachable — leave the hardcoded HTML as fallback
    });
}


/* ----------------------------------------------------------------
   12. BOOT — run on DOM ready
   ---------------------------------------------------------------- */
document.addEventListener('DOMContentLoaded', () => {
  initLayout();
  initFindYourRoom();
  initCarousel('residence-carousel');
  initFAQAccordion();
  initFAQSearch();
  initResidenceFilter();
  initFooterForm();
  initAvailability();
});
