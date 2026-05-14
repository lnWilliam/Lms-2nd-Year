/**
 * Mobile drawer + “⋯” menu (Bootstrap collapse + nested dropdowns).
 * - Hamburger: left rail + backdrop (lms-nav-backdrop).
 * - ⋯ panel: body.lms-more-open for z-index; outside click / Esc closes.
 * - Opening ⋯ closes the sidebar; opening sidebar closes ⋯ collapse.
 */

document.addEventListener('DOMContentLoaded', () => {
  const navbarToggler = document.querySelector('.lms-sidebar-toggler');
  const sidebar = document.querySelector('.lms-sidebar');
  const sidebarLinks = document.querySelectorAll('.lms-sidebar a');
  const mobileMore = document.getElementById('lmsMobileNavMore');
  const moreToggleBtn = document.querySelector('[data-bs-target="#lmsMobileNavMore"]');

  if (!navbarToggler || !sidebar) return;

  const backdrop = document.createElement('div');
  backdrop.className = 'lms-nav-backdrop';
  backdrop.setAttribute('aria-hidden', 'true');
  document.body.appendChild(backdrop);

  function hideMobileMoreCollapse() {
    if (!mobileMore || typeof bootstrap === 'undefined') return;
    const inst = bootstrap.Collapse.getInstance(mobileMore);
    if (inst) inst.hide();
  }

  function isDrawerOpen() {
    return sidebar.classList.contains('show');
  }

  function setDrawerOpen(open) {
    sidebar.classList.toggle('show', open);
    document.body.classList.toggle('lms-nav-open', open);
    backdrop.classList.toggle('is-active', open);
    navbarToggler.setAttribute('aria-expanded', open ? 'true' : 'false');
  }

  if (mobileMore) {
    mobileMore.addEventListener('show.bs.collapse', () => {
      setDrawerOpen(false);
      document.body.classList.add('lms-more-open');
    });
    mobileMore.addEventListener('hidden.bs.collapse', () => {
      document.body.classList.remove('lms-more-open');
    });
  }

  navbarToggler.addEventListener('click', (e) => {
    e.preventDefault();
    e.stopPropagation();
    hideMobileMoreCollapse();
    setDrawerOpen(!isDrawerOpen());
  });

  backdrop.addEventListener('click', () => {
    setDrawerOpen(false);
  });

  sidebarLinks.forEach((link) => {
    link.addEventListener('click', () => {
      setDrawerOpen(false);
    });
  });

  document.addEventListener('click', (e) => {
    const isClickInsideSidebar = sidebar.contains(e.target);
    const isClickOnToggler = navbarToggler.contains(e.target);
    const isClickOnBackdrop = backdrop.contains(e.target);
    const isMoreToggle =
      moreToggleBtn && (moreToggleBtn === e.target || moreToggleBtn.contains(e.target));
    const isClickOnMobileMore =
      mobileMore &&
      (mobileMore.contains(e.target) || isMoreToggle);

    if (
      isDrawerOpen() &&
      !isClickInsideSidebar &&
      !isClickOnToggler &&
      !isClickOnBackdrop &&
      !isClickOnMobileMore
    ) {
      setDrawerOpen(false);
    }

    if (
      document.body.classList.contains('lms-more-open') &&
      mobileMore &&
      !mobileMore.contains(e.target) &&
      !isMoreToggle
    ) {
      hideMobileMoreCollapse();
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    if (isDrawerOpen()) setDrawerOpen(false);
    if (document.body.classList.contains('lms-more-open')) hideMobileMoreCollapse();
  });

  window.addEventListener('resize', () => {
    if (window.innerWidth > 991.98) {
      setDrawerOpen(false);
      hideMobileMoreCollapse();
    }
  });
});
