/**
 * Products Page JS - Tulima Solar
 * - Product details modal (View Details)
 */

(function () {
  'use strict';

  var modal = null;
  var overlay = null;
  var modalContent = null;
  var lastFocused = null;

  function ensureModal() {
    if (modal && overlay && modalContent) return;
    overlay = document.createElement('div');
    overlay.className = 'c-product-modal__overlay';
    overlay.setAttribute('data-product-modal-overlay', '1');

    modal = document.createElement('div');
    modal.className = 'c-product-modal';
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.setAttribute('aria-label', 'Product details');

    var closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'c-product-modal__close';
    closeBtn.innerHTML = '<span aria-hidden="true">&times;</span><span class="sr-only">Close</span>';
    closeBtn.addEventListener('click', closeModal);

    modalContent = document.createElement('div');
    modalContent.className = 'c-product-modal__content';

    modal.appendChild(closeBtn);
    modal.appendChild(modalContent);

    document.body.appendChild(overlay);
    document.body.appendChild(modal);

    overlay.addEventListener('click', closeModal);

    document.addEventListener('keydown', function (e) {
      if (!modal || modal.getAttribute('data-open') !== '1') return;
      if (e.key === 'Escape') {
        e.preventDefault();
        closeModal();
      }
    });
  }

  function openModal(detailHtml, trigger) {
    ensureModal();
    lastFocused = trigger || document.activeElement;
    modalContent.innerHTML = detailHtml;
    modal.setAttribute('data-open', '1');
    overlay.setAttribute('data-open', '1');
    document.body.classList.add('has-product-modal');
    modal.focus();
  }

  function closeModal() {
    if (!modal || modal.getAttribute('data-open') !== '1') return;
    modal.setAttribute('data-open', '0');
    overlay.setAttribute('data-open', '0');
    document.body.classList.remove('has-product-modal');
    if (lastFocused && typeof lastFocused.focus === 'function') {
      lastFocused.focus();
    }
  }

  function initProductDetails() {
    var triggers = document.querySelectorAll('[data-product-detail-id]');
    if (!triggers.length) return;
    triggers.forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        var id = btn.getAttribute('data-product-detail-id');
        if (!id) return;
        var detail = document.getElementById('product-detail-' + id);
        if (!detail) return;
        openModal(detail.innerHTML, btn);
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initProductDetails);
  } else {
    initProductDetails();
  }
})();

