(() => {
  'use strict';
  document.querySelectorAll('[data-feedback]').forEach(section => {
    const postId = section.dataset.postId;
    const buttons = [...section.querySelectorAll('[data-rating]')];
    const status = section.querySelector('[data-feedback-status]');
    const key = `knt-feedback-${postId}`;
    function sync(rating) {
      buttons.forEach(button => button.setAttribute('aria-pressed', String(button.dataset.rating === rating)));
    }
    try { sync(localStorage.getItem(key)); } catch { /* Storage may be disabled. */ }
    buttons.forEach(button => button.addEventListener('click', async () => {
      buttons.forEach(item => { item.disabled = true; });
      status.textContent = 'Sending your response…';
      try {
        const response = await fetch(window.kntFeedback.endpoint, {
          method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ post_id: Number(postId), rating: button.dataset.rating })
        });
        const result = await response.json();
        if (!response.ok) throw new Error(result.message || 'Please try again.');
        sync(result.rating);
        status.textContent = result.message;
        try { localStorage.setItem(key, result.rating); } catch { /* Selection still works in this view. */ }
      } catch (error) {
        status.textContent = error.message || 'Feedback could not be sent. Please try again.';
      } finally {
        buttons.forEach(item => { item.disabled = false; });
      }
    }));
  });
})();
