(function() {
  'use strict';

  function initFaqAccordion() {
    const accordion = document.getElementById('faq-accordion');
    if (!accordion) {
      return;
    }

    const faqItems = accordion.querySelectorAll('.p-services-faq__item');

    faqItems.forEach(item => {
      const questionButton = item.querySelector('.p-services-faq__q, [data-faq-toggle]');
      const answer = item.querySelector('.p-services-faq__a');

      if (!questionButton || !answer) {
        return;
      }

      questionButton.addEventListener('click', () => {
        const isExpanded = questionButton.getAttribute('aria-expanded') === 'true';

        faqItems.forEach(otherItem => {
          if (otherItem !== item) {
            const otherButton = otherItem.querySelector('.p-services-faq__q, [data-faq-toggle]');
            const otherAnswer = otherItem.querySelector('.p-services-faq__a');

            if (otherButton && otherAnswer) {
              otherItem.classList.remove('is-active', 'is-open');
              otherButton.setAttribute('aria-expanded', 'false');
              otherAnswer.hidden = true;
            }
          }
        });

        if (isExpanded) {
          item.classList.remove('is-active', 'is-open');
          questionButton.setAttribute('aria-expanded', 'false');
          answer.hidden = true;
        } else {
          item.classList.add('is-active', 'is-open');
          questionButton.setAttribute('aria-expanded', 'true');
          answer.hidden = false;
        }
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initFaqAccordion);
  } else {
    initFaqAccordion();
  }
})();

