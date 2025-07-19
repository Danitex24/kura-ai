jQuery(document).ready(function ($) {
  // Run Security Scan
  $("#kura-ai-run-scan").on("click", function (e) {
    e.preventDefault();
    var $button = $(this);
    var $progress = $(".kura-ai-scan-progress");
    var $results = $(".kura-ai-scan-results");
    var $progressBar = $(".kura-ai-progress-bar-fill");
    var $progressMessage = $(".kura-ai-progress-message");

    $button.prop("disabled", true);
    $progress.show();
    $results.hide();

    // Simulate progress (will be replaced with actual progress from AJAX)
    var progress = 0;
    var progressInterval = setInterval(function () {
      progress += 5;
      if (progress > 90) {
        clearInterval(progressInterval);
      }
      $progressBar.css("width", progress + "%");
    }, 300);

    $.ajax({
      url: kura_ai_ajax.ajax_url,
      type: "POST",
      data: {
        action: "kura_ai_run_scan",
        _wpnonce: kura_ai_ajax.nonce, // Use _wpnonce for WordPress compatibility
      },
      beforeSend: function () {
        $progressMessage.text(kura_ai_ajax.scan_in_progress);
      },
      success: function (response) {
        clearInterval(progressInterval);
        $progressBar.css("width", "100%");

        if (response.success) {
          $progressMessage.text("Scan completed successfully!");

          // Display results
          displayScanResults(response.data);
          $results.show();

          // Reload page if on reports page
          if (window.location.href.indexOf("kura-ai-reports") !== -1) {
            setTimeout(function () {
              window.location.reload();
            }, 1500);
          }
        } else {
          $progressMessage.text("Scan failed: " + response.data);
        }

        setTimeout(function () {
          $progress.hide();
          $button.prop("disabled", false);
        }, 2000);
      },
      error: function (xhr, status, error) {
        clearInterval(progressInterval);
        $progressMessage.text("Scan failed: " + error);
        $button.prop("disabled", false);
      },
    });
  });

  // Display scan results
  function displayScanResults(results) {
    var $container = $("#kura-ai-results-container");
    $container.empty();

    // Count issues by severity
    var issueCounts = {
      critical: 0,
      high: 0,
      medium: 0,
      low: 0,
    };

    for (var category in results) {
      results[category].forEach(function (issue) {
        issueCounts[issue.severity]++;
      });
    }

    var totalIssues =
      issueCounts.critical +
      issueCounts.high +
      issueCounts.medium +
      issueCounts.low;

    // Create summary
    var summaryHtml = '<div class="kura-ai-results-summary">';
    summaryHtml += "<h3>Scan Summary</h3>";

    if (totalIssues === 0) {
      summaryHtml +=
        '<p class="notice notice-success">No security issues found. Your site appears to be secure.</p>';
    } else {
      summaryHtml += '<div class="kura-ai-issue-counts">';
      summaryHtml +=
        '<div class="kura-ai-issue-count critical"><span class="count">' +
        issueCounts.critical +
        '</span><span class="label">Critical</span></div>';
      summaryHtml +=
        '<div class="kura-ai-issue-count high"><span class="count">' +
        issueCounts.high +
        '</span><span class="label">High</span></div>';
      summaryHtml +=
        '<div class="kura-ai-issue-count medium"><span class="count">' +
        issueCounts.medium +
        '</span><span class="label">Medium</span></div>';
      summaryHtml +=
        '<div class="kura-ai-issue-count low"><span class="count">' +
        issueCounts.low +
        '</span><span class="label">Low</span></div>';
      summaryHtml += "</div>";

      summaryHtml +=
        "<p>Found " +
        totalIssues +
        " security " +
        (totalIssues === 1 ? "issue" : "issues") +
        " across different areas of your site.</p>";
    }

    summaryHtml += "</div>";
    $container.append(summaryHtml);

    // Create detailed results
    if (totalIssues > 0) {
      var detailsHtml =
        '<div class="kura-ai-results-details"><h3>Detailed Results</h3>';

      for (var category in results) {
        if (results[category].length > 0) {
          var categoryName = category.replace(/_/g, " ");
          categoryName =
            categoryName.charAt(0).toUpperCase() + categoryName.slice(1);

          detailsHtml += '<div class="kura-ai-result-category">';
          detailsHtml +=
            "<h4>" +
            categoryName +
            ' <span class="count">(' +
            results[category].length +
            ")</span></h4>";
          detailsHtml += '<table class="wp-list-table widefat fixed striped">';
          detailsHtml +=
            "<thead><tr><th>Issue</th><th>Severity</th><th>Suggested Fix</th><th>Actions</th></tr></thead>";
          detailsHtml += "<tbody>";

          results[category].forEach(function (issue) {
            detailsHtml += "<tr>";
            detailsHtml += "<td>" + issue.message + "</td>";
            detailsHtml +=
              '<td><span class="kura-ai-severity-badge ' +
              issue.severity +
              '">' +
              issue.severity.charAt(0).toUpperCase() +
              issue.severity.slice(1) +
              "</span></td>";
            detailsHtml +=
              "<td>" + (issue.fix || "No automatic fix available") + "</td>";
            detailsHtml += "<td>";

            if (issue.fix && issue.type) {
              detailsHtml +=
                '<button class="button kura-ai-apply-fix" data-issue-type="' +
                issue.type +
                '"';
              if (issue.plugin)
                detailsHtml += ' data-plugin="' + issue.plugin + '"';
              if (issue.theme)
                detailsHtml += ' data-theme="' + issue.theme + '"';
              if (issue.file) detailsHtml += ' data-file="' + issue.file + '"';
              detailsHtml += ">Apply Fix</button> ";
            }

            detailsHtml +=
              '<button class="button kura-ai-get-suggestion" data-issue=\'' +
              JSON.stringify(issue) +
              "'>AI Suggestion</button>";
            detailsHtml += "</td>";
            detailsHtml += "</tr>";
          });

          detailsHtml += "</tbody></table></div>";
        }
      }

      detailsHtml += "</div>";
      $container.append(detailsHtml);
    }
  }

  // Apply Fix
  $(document).on("click", ".kura-ai-apply-fix", function () {
    var $button = $(this);
    var issueType = $button.data("issue-type");
    var data = {
      action: "kura_ai_apply_fix",
      _wpnonce: kura_ai_ajax.nonce, // Use _wpnonce for WordPress compatibility
      issue_type: issueType,
    };

    if ($button.data("plugin")) {
      data.plugin = $button.data("plugin");
    }

    if ($button.data("theme")) {
      data.theme = $button.data("theme");
    }

    if ($button.data("file")) {
      data.file = $button.data("file");
    }

    $button.prop("disabled", true).text(kura_ai_ajax.applying_fix);

    $.ajax({
      url: kura_ai_ajax.ajax_url,
      type: "POST",
      data: data,
      success: function (response) {
        if (response.success) {
          alert(response.data.message);
          if (window.location.href.indexOf("kura-ai-reports") !== -1) {
            window.location.reload();
          }
        } else {
          alert("Error: " + response.data.message);
        }
      },
      error: function (xhr, status, error) {
        alert("Error: " + error);
      },
      complete: function () {
        $button.prop("disabled", false).text("Apply Fix");
      },
    });
  });

  // Get AI Suggestion
  $(document).on("click", ".kura-ai-get-suggestion", function () {
    var $button = $(this);
    var issue = $button.data("issue");

    if (typeof issue === "string") {
      try {
        issue = JSON.parse(issue);
      } catch (e) {
        console.error("Error parsing issue data:", e);
        return;
      }
    }

    $button.prop("disabled", true).text(kura_ai_ajax.getting_suggestions);

    $.ajax({
      url: kura_ai_ajax.ajax_url,
      type: "POST",
      data: {
        action: "kura_ai_get_suggestions",
        _wpnonce: kura_ai_ajax.nonce, // Use _wpnonce for WordPress compatibility
        issue: issue,
      },
      success: function (response) {
        if (response.success) {
          var $modal = $("#kura-ai-suggestion-modal");
          $("#kura-ai-suggestion-content").html(
            "<h4>" +
              issue.message +
              '</h4><div class="kura-ai-suggestion-text">' +
              response.data.suggestion.replace(/\n/g, "<br>") +
              "</div>"
          );
          $modal.show();
        } else {
          alert("Error: " + response.data);
        }
      },
      error: function (xhr, status, error) {
        alert("Error: " + error);
      },
      complete: function () {
        $button.prop("disabled", false).text("AI Suggestion");
      },
    });
  });

  // Request AI Suggestion from form
  $("#kura-ai-suggestion-request").on("submit", function (e) {
    e.preventDefault();
    var $form = $(this);
    var issueType = $("#kura-ai-issue-type").val();
    var issueDescription = $("#kura-ai-issue-description").val();

    var issue = {
      type: issueType,
      message: issueDescription,
      severity: "medium",
      fix: "",
    };

    $("#kura-ai-suggestion-loading").show();
    $form.hide();

    $.ajax({
      url: kura_ai_ajax.ajax_url,
      type: "POST",
      data: {
        action: "kura_ai_get_suggestions",
        _wpnonce: kura_ai_ajax.nonce, // Use _wpnonce for WordPress compatibility
        issue: issue,
      },
      success: function (response) {
        if (response.success) {
          var $results = $(".kura-ai-suggestions-results");
          $("#kura-ai-suggestion-result").html(
            response.data.suggestion.replace(/\n/g, "<br>")
          );
          $results.show();
        } else {
          alert("Error: " + response.data);
          $form.show();
        }
      },
      error: function (xhr, status, error) {
        alert("Error: " + error);
        $form.show();
      },
      complete: function () {
        $("#kura-ai-suggestion-loading").hide();
      },
    });
  });

  // New AI Request
  $("#kura-ai-new-request").on("click", function () {
    $(".kura-ai-suggestions-results").hide();
    $("#kura-ai-suggestion-request").show().trigger("reset");
  });

  // Export Logs
  $("#kura-ai-export-logs").on("click", function () {
    var $button = $(this);
    var type = $("#kura-ai-log-type").val();
    var severity = $("#kura-ai-log-severity").val();

    $button.prop("disabled", true).text(kura_ai_ajax.exporting_logs);

    var data = {
      action: "kura_ai_export_logs",
      _wpnonce: kura_ai_ajax.nonce, // Use _wpnonce for WordPress compatibility
    };

    if (type) data.type = type;
    if (severity) data.severity = severity;

    // Create a form and submit it to trigger download
    var $form = $("<form>", {
      method: "POST",
      action: kura_ai_ajax.ajax_url,
      style: "display: none;",
    });

    for (var key in data) {
      $form.append(
        $("<input>", {
          type: "hidden",
          name: key,
          value: data[key],
        })
      );
    }

    $("body").append($form);
    $form.submit();
    $form.remove();

    setTimeout(function () {
      $button.prop("disabled", false).text("Export to CSV");
    }, 1000);
  });

  // Clear Logs
  // Clear Logs button handler
  $("#kura-ai-clear-logs").on("click", function (e) {
    e.preventDefault();
    e.stopPropagation(); // Prevent event bubbling
    $("#kura-ai-confirm-clear-modal").show();
  });

  // Confirm Clear handler
  $("#kura-ai-confirm-clear").on("click", function (e) {
    e.preventDefault();
    e.stopPropagation();

    var $button = $(this);
    var type = $("#kura-ai-log-type").val();
    var severity = $("#kura-ai-log-severity").val();

    $button.prop("disabled", true).text("Clearing..."); // Hardcoded or use existing string
    $(".kura-ai-clear-loading").show();

    $.ajax({
      url: kura_ai_ajax.ajax_url,
      type: "POST",
      dataType: "json",
      data: {
        action: "kura_ai_clear_logs",
        _wpnonce: kura_ai_ajax.nonce, // Using the existing oauth nonce
        type: type,
        severity: severity,
      },
      success: function (response) {
        if (response && response.success) {
          alert("Logs cleared successfully"); // Simple alert or use your existing UI
          window.location.reload();
        } else {
          alert("Error: " + (response.data || "Failed to clear logs"));
        }
      },
      error: function (xhr, status, error) {
        alert("Error: " + error);
      },
      complete: function () {
        $button.prop("disabled", false).text("Clear Logs");
        $("#kura-ai-confirm-clear-modal").hide();
        $(".kura-ai-clear-loading").hide();
      },
    });
  });

  // reset plugin settings
  $("#kura-ai-confirm-clear").on("click", function (e) {
    e.preventDefault();
    e.stopPropagation();

    var $button = $(this);
    var type = $("#kura-ai-log-type").val();
    var severity = $("#kura-ai-log-severity").val();

    $button.prop("disabled", true).text(kura_ai_ajax.clearing_logs);
    $(".kura-ai-clear-loading").show();

    $.ajax({
      url: kura_ai_ajax.ajax_url,
      type: "POST",
      dataType: "json",
      data: {
        action: "kura_ai_clear_logs",
        _wpnonce: kura_ai_ajax.nonce, // Using the localized nonce
        type: type,
        severity: severity,
      },
      success: function (response) {
        if (response && response.success) {
          $("#kura-ai-clear-message")
            .removeClass("error")
            .addClass("updated")
            .text(kura_ai_ajax.logs_cleared)
            .show();

          setTimeout(function () {
            window.location.reload();
          }, 1500);
        } else {
          var errorMsg =
            response && response.data
              ? response.data
              : "Unknown error occurred";
          $("#kura-ai-clear-message")
            .removeClass("updated")
            .addClass("error")
            .text("Error: " + errorMsg)
            .show();
        }
      },
      error: function (xhr, status, error) {
        $("#kura-ai-clear-message")
          .removeClass("updated")
          .addClass("error")
          .text("Error: " + error)
          .show();
      },
      complete: function () {
        $button.prop("disabled", false).text("Clear Logs");
        $("#kura-ai-confirm-clear-modal").hide();
        $(".kura-ai-clear-loading").hide();
      },
    });
  });

  // Handle OAuth connection
  // Ensure click handler has proper error handling:
  $(document).on("click", ".kura-ai-oauth-connect", function (e) {
    e.preventDefault();
    const $button = $(this);
    const provider = $button.data("provider");

    $button.prop("disabled", true).text("Connecting...");

    $.ajax({
      url: kura_ai_ajax.ajax_url,
      type: "POST",
      data: {
        action: "kura_ai_oauth_init",
        provider: provider,
        _wpnonce: kura_ai_ajax.nonce,
      },
      success: function (response) {
        if (response.success) {
          window.location.href = response.data.redirect_url;
        } else {
          alert("Error: " + response.data);
          $button.prop("disabled", false).text("Connect Account");
        }
      },
      error: function (xhr) {
        alert("Error: " + xhr.responseText);
        $button.prop("disabled", false).text("Connect Account");
      },
    });
  });

  // Handle OAuth disconnection
  $(document).on("click", ".kura-ai-oauth-disconnect", function (e) {
    e.preventDefault();
    const provider = $(this).data("provider");

    if (
      confirm(
        "Are you sure you want to disconnect your " + provider + " account?"
      )
    ) {
      $.ajax({
        url: kura_ai_ajax.ajax_url,
        type: "POST",
        data: {
          action: "kura_ai_oauth_disconnect",
          provider: provider,
          _wpnonce: kura_ai_ajax.nonce,
        },
        success: function () {
          window.location.reload();
        },
        error: function (xhr) {
          alert("Error: " + xhr.responseText);
        },
      });
    }
  });

  // View Log Details
  $(document).on("click", ".kura-ai-view-details", function () {
    var logData = $(this).data("log-data");
    var $modal = $("#kura-ai-log-details-modal");
    var $content = $("#kura-ai-log-details-content");

    $content.empty();

    if (typeof logData === "object") {
      var html = '<table class="wp-list-table widefat fixed">';

      for (var key in logData) {
        html += "<tr><th>" + key + "</th><td>";

        if (typeof logData[key] === "object") {
          html += "<pre>" + JSON.stringify(logData[key], null, 2) + "</pre>";
        } else {
          html += logData[key];
        }

        html += "</td></tr>";
      }

      html += "</table>";
      $content.html(html);
    } else {
      $content.html("<pre>" + logData + "</pre>");
    }

    $modal.show();
  });

  // Reset Settings
  jQuery(document).ready(function ($) {
    // Show reset confirmation modal
    $("#kura-ai-reset-settings").on("click", function (e) {
      e.preventDefault();
      $("#kura-ai-confirm-reset-modal").show();
    });

    // Handle actual reset confirmation
    $("#kura-ai-confirm-reset").on("click", function (e) {
      e.preventDefault();
      var $button = $(this);

      $button.prop("disabled", true).text("Resetting...");

      $.ajax({
        url: kura_ai_ajax.ajax_url,
        type: "POST",
        data: {
          action: "kura_ai_reset_settings",
          _wpnonce: kura_ai_ajax.nonce, // Use _wpnonce for WordPress compatibility
        },
        success: function (response) {
          if (response.success) {
            window.location.reload();
          } else {
            alert("Error: " + response.data);
          }
        },
        error: function (xhr, status, error) {
          alert("Error: " + error);
        },
        complete: function () {
          $button.prop("disabled", false).text("Reset Settings");
          $("#kura-ai-confirm-reset-modal").hide();
        },
      });
    });

    // Modal close handlers
    $(".kura-ai-modal-close, .kura-ai-modal-close-btn").on(
      "click",
      function (e) {
        e.preventDefault();
        $("#kura-ai-confirm-reset-modal").hide();
      }
    );

    // Close modal when clicking outside
    $(".kura-ai-modal-overlay").on("click", function (e) {
      e.preventDefault();
      $("#kura-ai-confirm-reset-modal").hide();
    });
  });

  // View Debug Info
  $("#kura-ai-view-debug").on("click", function () {
    $("#kura-ai-debug-info").toggle();
  });

  // Copy Debug Info
  $("#kura-ai-copy-debug").on("click", function () {
    var $debugInfo = $("#kura-ai-debug-info textarea");
    $debugInfo.select();
    document.execCommand("copy");

    var $button = $(this);
    var originalText = $button.text();
    $button.text("Copied!");

    setTimeout(function () {
      $button.text(originalText);
    }, 2000);
  });

  // Modal Close Handlers
  $(".kura-ai-modal-close, .kura-ai-modal-close-btn").on("click", function () {
    $(this).closest(".kura-ai-modal").hide();
  });

  // Close modal when clicking outside
  $(window).on("click", function (e) {
    if ($(e.target).hasClass("kura-ai-modal")) {
      $(e.target).hide();
    }
  });
});