/**
 * Mobile Menu Toggle Handler
 * Hamburger (.lms-sidebar-toggler) opens the left sidebar only.
 * Top-nav account/class panel uses Bootstrap collapse separately.
 */

document.addEventListener('DOMContentLoaded', () => {
  const navbarToggler = document.querySelector('.lms-sidebar-toggler');
  const sidebar = document.querySelector('.lms-sidebar');
  const sidebarLinks = document.querySelectorAll('.lms-sidebar a');
  const mobileMore = document.getElementById('lmsMobileNavMore');

  if (!navbarToggler || !sidebar) return;

  function hideMobileMoreCollapse() {
    if (!mobileMore || typeof bootstrap === 'undefined') return;
    const inst = bootstrap.Collapse.getInstance(mobileMore);
    if (inst) inst.hide();
  }

  if (mobileMore) {
    mobileMore.addEventListener('show.bs.collapse', () => {
      sidebar.classList.remove('show');
    });
  }

  /**
   * Toggle sidebar visibility on hamburger click
   */
  navbarToggler.addEventListener('click', (e) => {
    e.preventDefault();
    e.stopPropagation();
    hideMobileMoreCollapse();
    sidebar.classList.toggle('show');
  });

  /**
   * Close sidebar when clicking on a navigation link
   */
  sidebarLinks.forEach(link => {
    link.addEventListener('click', () => {
      sidebar.classList.remove('show');
    });
  });

  /**
   * Close sidebar when clicking outside of it
   */
  document.addEventListener('click', (e) => {
    const isClickInsideSidebar = sidebar.contains(e.target);
    const isClickOnToggler = navbarToggler.contains(e.target);
    const isClickOnMobileMore =
      mobileMore &&
      (mobileMore.contains(e.target) ||
        e.target.closest('[data-bs-toggle="collapse"][data-bs-target="#lmsMobileNavMore"]'));

    if (!isClickInsideSidebar && !isClickOnToggler && !isClickOnMobileMore && sidebar.classList.contains('show')) {
      sidebar.classList.remove('show');
    }
  });

  /**
   * Close sidebar on window resize to desktop view
   */
  window.addEventListener('resize', () => {
    if (window.innerWidth > 991.98) {
      sidebar.classList.remove('show');
    }
  });
});
