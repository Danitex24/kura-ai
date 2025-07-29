/**
 * KuraAI Public-Facing JavaScript
 * Handles security notifications and user interactions
 *
 * @package    Kura_AI
 * @author     Daniel Abughdyer <daniel@danovatesolutions.org>
 * @version    1.0.0
 */

jQuery(document).ready(function ($) {
  "use strict";

  // Security notice dismissal
  $(document).on("click", ".kura-ai-notice .notice-dismiss", function () {
    const $notice = $(this).closest(".kura-ai-notice");
    const noticeId = $notice.data("notice-id");

    if (noticeId) {
      $.ajax({
        url: kura_ai_public.ajax_url,
        type: "POST",
        data: {
          action: "kura_ai_dismiss_notice",
          notice_id: noticeId,
          nonce: kura_ai_public.nonce,
        },
        error: function (xhr) {
          console.error("Error dismissing notice:", xhr.responseText);
        },
      });
    }
  });

  // Handle security warnings for forms
  $(document).on("submit", "form", function (e) {
    if ($(this).hasClass("kura-ai-warning")) {
      const warningMessage =
        $(this).data("warning-message") ||
        "This action has been flagged as potentially insecure. Are you sure you want to proceed?";

      if (!confirm(warningMessage)) {
        e.preventDefault();
        return false;
      }
    }
  });

  // Display security alerts from localStorage
  function displayStoredAlerts() {
    const alerts = JSON.parse(localStorage.getItem("kura_ai_alerts") || "[]");

    alerts.forEach((alert) => {
      if (!document.getElementById(alert.id)) {
        const alertHtml = `
                    <div id="${alert.id}" class="kura-ai-alert alert-${
          alert.type
        }">
                        <div class="kura-ai-alert-content">
                            <span class="kura-ai-alert-close">&times;</span>
                            <h4>${alert.title}</h4>
                            <p>${alert.message}</p>
                            ${
                              alert.link
                                ? `<a href="${alert.link.url}" class="kura-ai-alert-link">${alert.link.text}</a>`
                                : ""
                            }
                        </div>
                    </div>
                `;
        $("body").append(alertHtml);
      }
    });
  }

  // Close alert handler
  $(document).on("click", ".kura-ai-alert-close", function () {
    const alertId = $(this).closest(".kura-ai-alert").attr("id");
    let alerts = JSON.parse(localStorage.getItem("kura_ai_alerts") || "[]");
    alerts = alerts.filter((a) => a.id !== alertId);
    localStorage.setItem("kura_ai_alerts", JSON.stringify(alerts));
    $(this).closest(".kura-ai-alert").remove();
  });

  // Initialize
  displayStoredAlerts();

  // Listen for security events from other tabs
  window.addEventListener("storage", function (e) {
    if (e.key === "kura_ai_alerts") {
      displayStoredAlerts();
    }
  });

  // Security heartbeat check
  let securityHeartbeat = setInterval(function () {
    $.get(
      kura_ai_public.ajax_url,
      {
        action: "kura_ai_security_heartbeat",
        nonce: kura_ai_public.nonce,
      },
      function (response) {
        if (response.success && response.data.alerts) {
          localStorage.setItem(
            "kura_ai_alerts",
            JSON.stringify(response.data.alerts)
          );
          displayStoredAlerts();
        }
      }
    ).fail(function (xhr) {
      console.error("Security heartbeat failed:", xhr.responseText);
    });
  }, 300000); // Check every 5 minutes

  // Cleanup on page unload
  $(window).on("beforeunload", function () {
    clearInterval(securityHeartbeat);
  });
});
// End of kura-ai-public.js 