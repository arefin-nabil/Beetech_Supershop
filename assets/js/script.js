// assets/js/script.js

/**
 * Global Helper Functions
 */

$(document).ready(function () {
  // Enable Bootstrap tooltips/popovers everywhere
  var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
  var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
    return new bootstrap.Popover(popoverTriggerEl)
  });

  // Auto-dismiss alerts after 5 seconds
  setTimeout(function () {
    $('.alert-dismissible').fadeOut('slow');
  }, 5000);
});

function copyToClipboard(text) {
  if (!text) return;
  navigator.clipboard.writeText(text).then(function () {
    // Optional: Tooltip or toast? For now just a simple visual feedback could be nice but let's stick to system clipboard
    // Maybe change icon temporarily?
    alert('Copied: ' + text); // Simple feedback
  }, function (err) {
    console.error('Async: Could not copy text: ', err);
  });
}
