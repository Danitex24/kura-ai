jQuery(document).ready(function ($) {
  // Dashboard Scan Progress Modal
  function showScanModal() {
    $('.scan-progress-modal').css('display', 'flex');
    $('.progress-fill').css('width', '0%');
    $('.progress-text').text('Initializing scan...');
  }

  function hideScanModal() {
    $('.scan-progress-modal').hide();
  }

  function updateScanProgress(percent, message) {
    $('.progress-fill').css('width', percent + '%');
    $('.progress-text').text(message);
  }

  function showScanResults(data) {
    $('#scan-results-section').show();
    
    // Display the actual scan results
    displayScanResults(data);
    
    // Update summary items with actual data
    if (data && data.summary) {
      $('.summary-item:nth-child(1) .number').text(data.summary.files_scanned || '0');
      $('.summary-item:nth-child(2) .number').text(data.summary.threats_found || '0');
      $('.summary-item:nth-child(3) .number').text(data.summary.issues_fixed || '0');
      $('.summary-item:nth-child(4) .number').text(data.summary.scan_time || '0s');
    }
  }

  // Close scan results
  $('.close-results').on('click', function() {
    $('#scan-results-section').hide();
  });

  // Run Security Scan
  $("#kura-ai-run-scan").on("click", function (e) {
    e.preventDefault();
    var $button = $(this);

    $button.prop("disabled", true);
    showScanModal();

    // Simulate progress (will be replaced with actual progress from AJAX)
    var progress = 0;
    var progressInterval = setInterval(function () {
      progress += 5;
      if (progress <= 20) {
        updateScanProgress(progress, 'Scanning files...');
      } else if (progress <= 40) {
        updateScanProgress(progress, 'Checking for malware...');
      } else if (progress <= 60) {
        updateScanProgress(progress, 'Analyzing vulnerabilities...');
      } else if (progress <= 80) {
        updateScanProgress(progress, 'Checking file permissions...');
      } else if (progress <= 90) {
        updateScanProgress(progress, 'Finalizing scan...');
      }
      
      if (progress > 90) {
        clearInterval(progressInterval);
      }
    }, 300);

    $.ajax({
      url: kura_ai_ajax.ajax_url,
      type: "POST",
      data: {
        action: "kura_ai_run_scan",
        _wpnonce: kura_ai_ajax.nonce
      },
      beforeSend: function () {
        updateScanProgress(0, 'Initializing scan...');
      },
      success: function (response) {
        clearInterval(progressInterval);
        updateScanProgress(100, 'Scan completed successfully!');

        if (response.success) {
          // Show results after a brief delay
          setTimeout(function() {
            hideScanModal();
            
            // Check if no security issues found
            if (response.data && response.data.issues_found === 0) {
              Swal.fire({
                title: 'Scan Summary',
                text: 'No security issues found. Your site appears to be secure.',
                icon: 'success',
                confirmButtonText: 'OK',
                confirmButtonColor: '#10b981'
              });
            } else {
              showScanResults(response.data);
            }
            
            // Update dashboard stats if available
            if (response.data && response.data.stats) {
              $('.stat-card:nth-child(2) .stat-content h3').text(response.data.stats.issues || '0');
              $('.last-scan-info').text('Last scan: Just now');
            }
          }, 1500);

          // Reload page if on reports page
          if (window.location.href.indexOf("kura-ai-reports") !== -1) {
            setTimeout(function () {
              window.location.reload();
            }, 3000);
          }
        } else {
          updateScanProgress(100, "Scan failed: " + response.data);
          setTimeout(function() {
            hideScanModal();
          }, 2000);
        }

        $button.prop("disabled", false);
      },
      error: function (xhr, status, error) {
        clearInterval(progressInterval);
        console.log('AJAX Error Details:', {
          status: xhr.status,
          statusText: xhr.statusText,
          responseText: xhr.responseText,
          error: error
        });
        updateScanProgress(100, "Scan failed: " + xhr.status + " - " + (xhr.responseText || error));
        setTimeout(function() {
          hideScanModal();
        }, 2000);
        $button.prop("disabled", false);
      },
    });
  });

  // Display scan results
  function displayScanResults(data) {
    var $container = $('#kura-ai-results-container');
    $container.empty();

    if (!data || !data.results) {
      $container.append('<p class="notice notice-error">No scan results available.</p>');
      return;
    }

    var results = data.results;

    // Count issues by severity
    var issueCounts = {
      critical: 0,
      high: 0,
      medium: 0,
      low: 0
    };

    for (var category in results) {
      if (Array.isArray(results[category])) {
        results[category].forEach(function(issue) {
          if (issue.severity) {
            issueCounts[issue.severity]++;
          }
        });
      }
    }

    var totalIssues = issueCounts.critical + issueCounts.high + issueCounts.medium + issueCounts.low;

    // Create summary
    var summaryHtml = '<div class="kura-ai-results-summary">';
    summaryHtml += '<h3>Scan Summary</h3>';

    if (totalIssues === 0) {
      summaryHtml += '<p class="notice notice-success">No security issues found. Your site appears to be secure.</p>';
    } else {
      summaryHtml += '<div class="kura-ai-issue-counts">';
      summaryHtml += '<div class="kura-ai-issue-count critical"><span class="count">' + issueCounts.critical + '</span><span class="label">Critical</span></div>';
      summaryHtml += '<div class="kura-ai-issue-count high"><span class="count">' + issueCounts.high + '</span><span class="label">High</span></div>';
      summaryHtml += '<div class="kura-ai-issue-count medium"><span class="count">' + issueCounts.medium + '</span><span class="label">Medium</span></div>';
      summaryHtml += '<div class="kura-ai-issue-count low"><span class="count">' + issueCounts.low + '</span><span class="label">Low</span></div>';
      summaryHtml += '</div>';

      summaryHtml += '<p>Found ' + totalIssues + ' security ' + (totalIssues === 1 ? 'issue' : 'issues') + ' across different areas of your site.</p>';
    }

    summaryHtml += '</div>';
    $container.append(summaryHtml);

    // Create detailed results
    if (totalIssues > 0) {
      var detailsHtml = '<div class="kura-ai-results-details"><h3>Detailed Results</h3>';

      for (var category in results) {
        if (Array.isArray(results[category]) && results[category].length > 0) {
          var categoryName = category.replace(/_/g, ' ');
          categoryName = categoryName.charAt(0).toUpperCase() + categoryName.slice(1);

          detailsHtml += '<div class="kura-ai-result-category">';
          detailsHtml += '<h4>' + categoryName + ' <span class="count">(' + results[category].length + ')</span></h4>';
          detailsHtml += '<table class="wp-list-table widefat fixed striped">';
          detailsHtml += '<thead><tr><th>Issue</th><th>Severity</th><th>Suggested Fix</th><th>Actions</th></tr></thead>';
          detailsHtml += '<tbody>';

          results[category].forEach(function(issue) {
            detailsHtml += '<tr>';
            detailsHtml += '<td>' + issue.message + '</td>';
            detailsHtml += '<td><span class="kura-ai-severity-badge ' + issue.severity + '">' + issue.severity.charAt(0).toUpperCase() + issue.severity.slice(1) + '</span></td>';
            detailsHtml += '<td>' + (issue.fix || 'No automatic fix available') + '</td>';
            detailsHtml += '<td>';

            if (issue.fix && issue.type) {
              detailsHtml += '<button class="button kura-ai-apply-fix" data-issue-type="' + issue.type + '"';
              detailsHtml += ' data-issue-id="' + (issue.id || '') + '"';
              detailsHtml += ' data-fix="' + (issue.fix || '') + '"';
              if (issue.plugin) detailsHtml += ' data-plugin="' + issue.plugin + '"';
              if (issue.theme) detailsHtml += ' data-theme="' + issue.theme + '"';
              if (issue.file) detailsHtml += ' data-file="' + issue.file + '"';
              detailsHtml += '>Apply Fix</button> ';
            }

            detailsHtml += '<button class="button kura-ai-get-suggestion" data-issue=\'' + JSON.stringify(issue) + '">AI Suggestion</button>';
            detailsHtml += '</td>';
            detailsHtml += '</tr>';
          });

          detailsHtml += '</tbody></table></div>';
        }
      }

      detailsHtml += '</div>';
      $container.append(detailsHtml);
    }
  }

  // Apply Fix
  $(document).on("click", ".kura-ai-apply-fix", function () {
    var $button = $(this);
    var issueType = $button.data("issue-type");
    var data = {
      action: "kura_ai_apply_fix",
      _wpnonce: kura_ai_ajax.nonce,
      issue_type: issueType,
      issue_id: $button.data("issue-id"),
      fix: $button.data("fix")
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
          alert(response.data.message || kura_ai_ajax.fix_applied);
          if (response.data.result) {
            // Refresh the page to show updated scan results
            window.location.reload();
          }
        } else {
          alert(response.data.message || kura_ai_ajax.fix_failed);
        }
      },
      error: function (xhr, status, error) {
        alert(kura_ai_ajax.fix_failed + ": " + error);
      },
      complete: function () {
        $button.prop("disabled", false).text(kura_ai_ajax.apply_fix);
      }
    });
  });

  // Get AI Suggestion
  $(document).on("click", ".kura-ai-get-suggestion", function () {
    var $button = $(this);
    var issue = $button.data("issue");



    // No need to parse issue as jQuery.data() automatically parses JSON
    if (!issue || typeof issue !== "object") {
      console.error("Invalid issue data");
      return;
    }

    $button.prop("disabled", true).text(kura_ai_ajax.getting_suggestions);

    var ajaxData = {
      action: "kura_ai_get_suggestions",
      _wpnonce: kura_ai_ajax.nonce,
      issue: JSON.stringify(issue)
    };
    


    $.ajax({
      url: kura_ai_ajax.ajax_url,
      type: "POST",
      data: ajaxData,
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
          $modal.show().addClass('show');
        } else {
          alert("Error: " + (response.data.message || response.data || 'Unknown error'));
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
      
      // Validate form data
      if (!issueType || issueType.trim() === "") {
          alert("Please select an issue type.");
          return;
      }
      
      if (!issueDescription.trim()) {
          alert("Please enter an issue description.");
          return;
      }
  
      $("#kura-ai-suggestion-loading").show();
      $form.hide();
      
      // Create issue object matching the expected format
      var issue = {
          type: issueType,
          message: issueDescription,
          severity: "medium"
      };
  
      $.ajax({
          url: kura_ai_ajax.ajax_url,
          type: "POST",
          data: {
              action: "kura_ai_get_suggestions",
              _wpnonce: kura_ai_ajax.nonce,
              issue: JSON.stringify(issue)
          },
          success: function (response) {
              if (response.success) {
                  var $results = $(".kura-ai-response-card");
                  $("#kura-ai-suggestion-result").html(
                      response.data.suggestion.replace(/\n/g, "<br>")
                  );
                  $results.show();
              } else {
                  alert("Error: " + response.data.message);
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
    $(".kura-ai-response-card").hide();
    $("#kura-ai-suggestion-request").show();
    $("#kura-ai-suggestion-request")[0].reset();
  });

  // Export Logs
  $("#kura-ai-export-logs").on("click", function () {
    var $button = $(this);
    var type = $("#kura-ai-log-type").val();
    var severity = $("#kura-ai-log-severity").val();
    var search = $("#kura-ai-log-search").val();

    $button.prop("disabled", true).text(kura_ai_ajax.exporting_logs);

    var data = {
      action: "kura_ai_export_logs",
      _wpnonce: kura_ai_ajax.nonce, // Use _wpnonce for WordPress compatibility
    };

    if (type) data.type = type;
    if (severity) data.severity = severity;
    if (search) data.search = search;

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
    var $modal = $("#kura-ai-confirm-clear-modal");
            $modal.css('display', 'block');
            setTimeout(function() {
                $modal.addClass('show');
            }, 10);
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
        var $modal = $("#kura-ai-confirm-clear-modal");
                $modal.removeClass('show');
                setTimeout(function() {
                    $modal.hide();
                }, 300);
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
        var $modal = $("#kura-ai-confirm-clear-modal");
            $modal.removeClass('show');
            setTimeout(function() {
                $modal.hide();
            }, 300);
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
  (function($) {
      'use strict';
  
      $(document).ready(function() {
          // Run Security Scan
          $('#kura-ai-run-scan').on('click', function(e) {
              e.preventDefault();
              var $button = $(this);
              var $progress = $('.kura-ai-scan-progress');
              var $results = $('.kura-ai-scan-results');
              var $progressBar = $('.kura-ai-progress-bar-fill');
              var $progressMessage = $('.kura-ai-progress-message');
  
              $button.prop('disabled', true);
              $progress.show();
              $results.hide();
  
              var progress = 0;
              var progressInterval = setInterval(function() {
                  progress += 5;
                  if (progress > 90) {
                      clearInterval(progressInterval);
                  }
                  $progressBar.css('width', progress + '%');
              }, 300);
  
              $.ajax({
                  url: kura_ai_ajax.ajax_url,
                  type: 'POST',
                  data: {
                      action: 'kura_ai_run_scan',
                      _wpnonce: kura_ai_ajax.nonce
                  },
                  beforeSend: function() {
                      $progressMessage.text(kura_ai_ajax.scan_in_progress);
                  },
                  success: function(response) {
                      clearInterval(progressInterval);
                      $progressBar.css('width', '100%');
  
                      if (response.success) {
                          $progressMessage.text('Scan completed successfully!');
                          displayScanResults(response.data);
                          $results.show();
  
                          if (window.location.href.indexOf('kura-ai-reports') !== -1) {
                              setTimeout(function() {
                                  window.location.reload();
                              }, 1500);
                          }
                      } else {
                          $progressMessage.text('Scan failed: ' + response.data);
                      }
  
                      setTimeout(function() {
                          $progress.hide();
                          $button.prop('disabled', false);
                      }, 2000);
                  },
                  error: function(xhr, status, error) {
                      clearInterval(progressInterval);
                      $progressMessage.text('Scan failed: ' + error);
                      $button.prop('disabled', false);
                  }
              });
          });
  
          // Handle OAuth reconnect
          $(document).on('click', '.kura-ai-oauth-reconnect', function(e) {
              e.preventDefault();
              const provider = $(this).data('provider');
              const redirectUrl = $(this).data('redirect-url');
  
              if (confirm('Are you sure you want to reconnect your ' + provider + ' account?')) {
                  $.post(kura_ai_ajax.ajax_url, {
                      action: 'kura_ai_oauth_reconnect',
                      provider: provider,
                      redirect_url: redirectUrl,
                      _wpnonce: kura_ai_ajax.nonce
                  }, function(response) {
                      if (response.success) {
                          window.location.href = response.data.redirect_url;
                      } else {
                          alert('Error reconnecting to ' + provider + ': ' + response.data);
                      }
                  });
              }
          });
  
          // View Log Details
        $(document).on('click', '.kura-ai-view-details', function() {
            var logData = $(this).data('log-data');
            var $modal = $('#kura-ai-log-details-modal');
            var $content = $('#kura-ai-log-details-content');

            $content.empty();

            if (typeof logData === 'object') {
                var html = '<table class="wp-list-table widefat fixed striped">';
                for (var key in logData) {
                    html += '<tr><th style="width: 200px; font-weight: 600; color: #1d2327; text-transform: capitalize;">' + key.replace(/_/g, ' ') + '</th><td style="word-break: break-word;">';
                    if (typeof logData[key] === 'object') {
                        html += '<pre style="background: #f8f9fa; padding: 12px; border-radius: 6px; font-size: 13px; line-height: 1.4; margin: 0; overflow-x: auto;">' + JSON.stringify(logData[key], null, 2) + '</pre>';
                    } else {
                        html += '<span style="color: #50575e;">' + logData[key] + '</span>';
                    }
                    html += '</td></tr>';
                }
                html += '</table>';
                $content.html(html);
            } else {
                $content.html('<pre style="background: #f8f9fa; padding: 20px; border-radius: 8px; font-size: 14px; line-height: 1.5; margin: 0; overflow-x: auto; color: #1d2327;">' + logData + '</pre>');
            }

            // Smooth modal animation
            $modal.css('display', 'block');
            setTimeout(function() {
                $modal.addClass('show');
            }, 10);
        });
  
          // Reset Settings functionality is handled in the main document ready block below
  
          // Modal close handlers
          $('.kura-ai-modal-close, .kura-ai-modal-close-btn').on('click', function(e) {
            e.preventDefault();
            var $modal = $(this).closest('.kura-ai-modal');
            $modal.removeClass('show');
            setTimeout(function() {
                $modal.hide();
            }, 300);
        });
  
          // Close modal when clicking outside
          $('.kura-ai-modal-overlay').on('click', function(e) {
              e.preventDefault();
              $('#kura-ai-confirm-reset-modal').hide();
          });
  
          // Debug Info functionality is handled in the main document ready block below
  
          // Handle API key visibility
          $('#enable_ai').on('change', function() {
              const isEnabled = $(this).is(':checked');
              $('.kura-ai-api-key-section').toggle(isEnabled);
          });
  
          // Handle save API key
          $('#save_api_key').on('click', function(e) {
              e.preventDefault();
              const apiKey = $('#api_key').val();
              const provider = $('#ai_service').val();
  
              $.post(ajaxurl, {
                  action: 'save_api_key',
                  api_key: apiKey,
                  provider: provider,
                  _wpnonce: kura_ai_ajax.nonce
              }, function(response) {
                  alert(response.message || 'API Key saved successfully!');
              });
          });
  
          // Handle connect provider
          $('#connect_provider').on('click', function(e) {
              e.preventDefault();
              const provider = $('#ai_service').val();
  
              $.post(ajaxurl, {
                  action: 'connect_to_ai_provider',
                  provider: provider,
                  _wpnonce: kura_ai_ajax.nonce
              }, function(response) {
                  alert(response.message || 'Connected successfully!');
              });
          });
  
          // Handle apply fix button
          $(document).on('click', '.kura-ai-apply-fix', function() {
              var fixAction = $(this).data('fix');
              var $button = $(this);
  
              $button.prop('disabled', true).text('Applying...');
  
              $.post(ajaxurl, {
                  action: 'kura_ai_apply_fix',
                  fix_action: fixAction,
                  _wpnonce: kura_ai_ajax.nonce
              }, function(response) {
                  if (response.success) {
                      $button.text('Applied').addClass('button-disabled');
                  } else {
                      alert('Error applying fix: ' + response.data);
                      $button.prop('disabled', false).text('Apply Fix');
                  }
              });
          });
      });
  
      // Display scan results function
      function displayScanResults(results) {
          var $container = $('#kura-ai-results-container');
          $container.empty();
  
          var issueCounts = {
              critical: 0,
              high: 0,
              medium: 0,
              low: 0
          };
  
          for (var category in results) {
              results[category].forEach(function(issue) {
                  issueCounts[issue.severity]++;
              });
          }
  
          var totalIssues = issueCounts.critical + issueCounts.high + issueCounts.medium + issueCounts.low;
  
          var summaryHtml = '<div class="kura-ai-results-summary">';
          summaryHtml += '<h3>Scan Summary</h3>';
  
          if (totalIssues === 0) {
              summaryHtml += '<p class="notice notice-success">No security issues found. Your site appears to be secure.</p>';
          } else {
              summaryHtml += '<div class="kura-ai-issue-counts">';
              summaryHtml += '<div class="kura-ai-issue-count critical"><span class="count">' + issueCounts.critical + '</span><span class="label">Critical</span></div>';
              summaryHtml += '<div class="kura-ai-issue-count high"><span class="count">' + issueCounts.high + '</span><span class="label">High</span></div>';
              summaryHtml += '<div class="kura-ai-issue-count medium"><span class="count">' + issueCounts.medium + '</span><span class="label">Medium</span></div>';
              summaryHtml += '<div class="kura-ai-issue-count low"><span class="count">' + issueCounts.low + '</span><span class="label">Low</span></div>';
              summaryHtml += '</div>';
              summaryHtml += '<p>Found ' + totalIssues + ' security ' + (totalIssues === 1 ? 'issue' : 'issues') + ' across different areas of your site.</p>';
          }
  
          summaryHtml += '</div>';
          $container.append(summaryHtml);
  
          if (totalIssues > 0) {
              var detailsHtml = '<div class="kura-ai-results-details"><h3>Detailed Results</h3>';
              for (var category in results) {
                  if (results[category].length > 0) {
                      var categoryName = category.replace(/_/g, ' ');
                      categoryName = categoryName.charAt(0).toUpperCase() + categoryName.slice(1);
  
                      detailsHtml += '<div class="kura-ai-result-category">';
                      detailsHtml += '<h4>' + categoryName + ' <span class="count">(' + results[category].length + ')</span></h4>';
                      detailsHtml += '<table class="wp-list-table widefat fixed striped">';
                      detailsHtml += '<thead><tr><th>Issue</th><th>Severity</th><th>Suggested Fix</th><th>Actions</th></tr></thead>';
                      detailsHtml += '<tbody>';
  
                      results[category].forEach(function(issue) {
                          detailsHtml += '<tr>';
                          detailsHtml += '<td>' + issue.message + '</td>';
                          detailsHtml += '<td><span class="kura-ai-severity-badge ' + issue.severity + '">' + issue.severity + '</span></td>';
                          detailsHtml += '<td>' + issue.fix + '</td>';
                          detailsHtml += '<td><button class="button kura-ai-apply-fix" data-fix="' + issue.fix_action + '">Apply Fix</button></td>';
                          detailsHtml += '</tr>';
                      });
  
                      detailsHtml += '</tbody></table></div>';
                  }
              }
              detailsHtml += '</div>';
              $container.append(detailsHtml);
          }
      }
  })(jQuery);
  // View Log Details
  $(document).on("click", ".kura-ai-view-details", function () {
    var logData = $(this).data("log-data");
    var $modal = $("#kura-ai-log-details-modal");
    var $content = $("#kura-ai-log-details-content");

    $content.empty();

    if (typeof logData === "object") {
      var html = '<table class="wp-list-table widefat fixed striped">';

      for (var key in logData) {
        html += "<tr><th style='width: 200px; font-weight: 600; color: #1d2327; text-transform: capitalize;'>" + key.replace(/_/g, ' ') + "</th><td style='word-break: break-word;'>";

        if (typeof logData[key] === "object") {
          html += "<pre style='background: #f8f9fa; padding: 12px; border-radius: 6px; font-size: 13px; line-height: 1.4; margin: 0; overflow-x: auto;'>" + JSON.stringify(logData[key], null, 2) + "</pre>";
        } else {
          html += "<span style='color: #50575e;'>" + logData[key] + "</span>";
        }

        html += "</td></tr>";
      }

      html += "</table>";
      $content.html(html);
    } else {
      $content.html("<pre style='background: #f8f9fa; padding: 20px; border-radius: 8px; font-size: 14px; line-height: 1.5; margin: 0; overflow-x: auto; color: #1d2327;'>" + logData + "</pre>");
    }

    // Smooth modal animation
    $modal.css('display', 'block');
    setTimeout(function() {
      $modal.addClass('show');
    }, 10);
  });

  // Reset Settings and Debug Info functionality
  jQuery(document).ready(function ($) {
    // Show reset confirmation using SweetAlert
    $("#kura-ai-reset-settings").on("click", function (e) {
      e.preventDefault();
      
      Swal.fire({
        title: 'Confirm Reset',
        text: 'Are you sure you want to reset all settings to default? This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d63638',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Reset Settings',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        customClass: {
          confirmButton: 'button button-danger',
          cancelButton: 'button button-secondary'
        }
      }).then((result) => {
        if (result.isConfirmed) {
          // Show loading state
          Swal.fire({
            title: 'Resetting Settings...',
            text: 'Please wait while we reset your settings.',
            icon: 'info',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
              Swal.showLoading();
            }
          });
          
          // Perform the reset
          $.ajax({
            url: kura_ai_ajax.ajax_url,
            type: "POST",
            data: {
              action: "kura_ai_reset_settings",
              _wpnonce: kura_ai_ajax.nonce
            },
            success: function (response) {
              if (response.success) {
                Swal.fire({
                  title: 'Success!',
                  text: 'Settings have been reset successfully!',
                  icon: 'success',
                  confirmButtonText: 'OK',
                  customClass: {
                    confirmButton: 'button button-primary'
                  }
                }).then(() => {
                  window.location.reload();
                });
              } else {
                Swal.fire({
                  title: 'Error!',
                  text: response.data.message || response.data || 'Failed to reset settings.',
                  icon: 'error',
                  confirmButtonText: 'OK',
                  customClass: {
                    confirmButton: 'button button-primary'
                  }
                });
              }
            },
            error: function (xhr, status, error) {
              Swal.fire({
                title: 'Error!',
                text: 'Network error: ' + error,
                icon: 'error',
                confirmButtonText: 'OK',
                customClass: {
                  confirmButton: 'button button-primary'
                }
              });
            }
          });
        }
      });
    });

    // View Debug Info - toggle visibility
    $("#kura-ai-view-debug").on("click", function (e) {
      e.preventDefault();
      var $debugPanel = $("#kura-ai-debug-info");
      var $button = $(this);
      
      if ($debugPanel.is(':visible')) {
        $debugPanel.slideUp();
        $button.text('View Debug Info');
      } else {
        $debugPanel.slideDown();
        $button.text('Hide Debug Info');
      }
    });

    // Copy Debug Info to clipboard
    $("#kura-ai-copy-debug").on("click", function (e) {
      e.preventDefault();
      var $debugInfo = $("#kura-ai-debug-info textarea");
      var $button = $(this);
      var originalText = $button.text();
      
      try {
        $debugInfo.select();
        document.execCommand("copy");
        $button.text("Copied!").addClass('button-primary');
        
        setTimeout(function () {
          $button.text(originalText).removeClass('button-primary');
        }, 2000);
      } catch (err) {
        alert('Failed to copy debug info. Please select and copy manually.');
      }
    });

    // Note: Modal handlers removed as we're now using SweetAlert for reset confirmation
  });

  // Modal close handlers are already defined above, removing duplicates

  // Close modal with Escape key
  $(document).on('keydown', function(e) {
    if (e.key === 'Escape') {
      var $modal = $('.kura-ai-modal:visible');
      if ($modal.length) {
        $modal.removeClass('show');
        setTimeout(function() {
          $modal.hide();
        }, 300);
      }
    }
  });

  // Close modal when clicking outside
  $(window).on("click", function (e) {
    if ($(e.target).hasClass("kura-ai-modal")) {
      var $modal = $(e.target);
      $modal.removeClass('show');
      setTimeout(function() {
        $modal.hide();
      }, 300);
    }
  });
});

// Toggle visibility of API key fields and buttons
(function($) {
    'use strict';
    
    $(document).ready(function() {
        $('#enable_ai').on('change', function() {
            const isEnabled = $(this).is(':checked');
            if (isEnabled) {
                $('.kura-ai-api-key-section').show();
            } else {
                $('.kura-ai-api-key-section').hide();
            }
        });

        // Handle save API key button click
        $('#save_api_key').on('click', function(e) {
            e.preventDefault();
            const apiKey = $('#api_key').val();
            const provider = $('#ai_service').val();

            $.post(kura_ai_ajax.ajax_url, {
                action: 'save_api_key',
                api_key: apiKey,
                provider: provider,
                _wpnonce: kura_ai_ajax.nonce
            }, function(response) {
                alert(response.message || 'API Key saved successfully!');
            });
        });

        // Handle connect provider button click
        $('#connect_provider').on('click', function(e) {
            e.preventDefault();
            const provider = $('#ai_service').val();

            $.post(kura_ai_ajax.ajax_url, {
                action: 'connect_to_ai_provider',
                provider: provider,
                _wpnonce: kura_ai_ajax.nonce
            }, function(response) {
                alert(response.message || 'Connected successfully!');
            });
        });
    });
})(jQuery);


// Malware Detection
(function($) {
    let activeScanId = null;
    let scanProgressInterval = null;

    // Initialize malware scan controls
    function initMalwareScan() {
    const $startScanBtn = $('#start-malware-scan');
    const $cancelScanBtn = $('#cancel-malware-scan');
    
    // Only initialize if elements exist (avoid conflicts with malware-detection.js)
    if ($startScanBtn.length === 0 || $cancelScanBtn.length === 0) {
        return;
    }
    const $progressBar = $('#scan-progress-bar');
    const $progressText = $('#scan-progress-text');
    const $resultsContainer = $('#scan-results');
    const $confidenceSlider = $('#ai-confidence-threshold');
    const $confidenceValue = $('#confidence-value');

    // Update confidence threshold display
    $confidenceSlider.on('input', function() {
        $confidenceValue.text($(this).val() + '%');
    });

    // Start malware scan
    $startScanBtn.on('click', function() {
        $.ajax({
            url: kura_ai_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'kura_ai_start_malware_scan',
                nonce: kura_ai_ajax.nonce,
                confidence_threshold: $confidenceSlider.val()
            },
            beforeSend: function() {
                $startScanBtn.prop('disabled', true);
                $cancelScanBtn.prop('disabled', false);
                $progressBar.show();
                $progressText.text('Starting scan...');
                $resultsContainer.empty();
            },
            success: function(response) {
                if (response.success) {
                    activeScanId = response.data.scan_id;
                    startProgressPolling();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.data.message,
                        allowOutsideClick: true,
                        allowEscapeKey: true
                    });
                    resetScanControls();
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to start malware scan.',
                    allowOutsideClick: true,
                    allowEscapeKey: true
                });
                resetScanControls();
            }
        });
    });

    // Cancel malware scan
    $cancelScanBtn.on('click', function() {
        if (!activeScanId) return;

        $.ajax({
            url: kura_ai_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'kura_ai_cancel_scan',
                nonce: kura_ai_ajax.nonce,
                scan_id: activeScanId
            },
            success: function(response) {
                if (response.success) {
                    stopProgressPolling();
                    resetScanControls();
                    $progressText.text('Scan cancelled.');
                }
            }
        });
    });

    // Handle quarantine file action
    $(document).on('click', '.quarantine-file', function(e) {
        e.preventDefault();
        const filePath = $(this).data('file-path');

        Swal.fire({
            title: 'Confirm Quarantine',
            text: 'Are you sure you want to quarantine this file?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, quarantine it',
            cancelButtonText: 'No, cancel',
            allowOutsideClick: true,
            allowEscapeKey: true
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: kura_ai_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'kura_ai_quarantine_file',
                        nonce: kura_ai_ajax.nonce,
                        file_path: filePath
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Success',
                                text: response.data.message,
                                icon: 'success',
                                allowOutsideClick: true,
                                allowEscapeKey: true
                            });
                            // Remove the quarantined file from the results
                            $(e.target).closest('.threat-item').fadeOut();
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: response.data.message,
                                icon: 'error',
                                allowOutsideClick: true,
                                allowEscapeKey: true
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: 'Error',
                            text: 'Failed to quarantine file.',
                            icon: 'error',
                            allowOutsideClick: true,
                            allowEscapeKey: true
                        });
                    }
                });
            }
        });
    });
}

// Poll for scan progress
function startProgressPolling() {
    scanProgressInterval = setInterval(function() {
        if (!activeScanId) return;

        $.ajax({
            url: kura_ai_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'kura_ai_get_scan_progress',
                nonce: kura_ai_ajax.nonce,
                scan_id: activeScanId
            },
            success: function(response) {
                if (response.success) {
                    updateProgress(response.data);
                }
            }
        });
    }, 2000);
}

// Update progress UI
function updateProgress(data) {
    const $progressBar = $('#scan-progress-bar');
    const $progressText = $('#scan-progress-text');
    const $resultsContainer = $('#scan-results');

    $progressBar.css('width', data.progress + '%');
    $progressText.text(data.status);

    if (data.threats && data.threats.length > 0) {
        displayThreats(data.threats);
    }

    if (data.completed) {
        scanCompleted(data);
    }
}

// Display detected threats
function displayThreats(threats) {
    const $resultsContainer = $('#scan-results');
    let threatHtml = '';

    threats.forEach(function(threat) {
        threatHtml += `
            <div class="threat-item card mb-3">
                <div class="card-body">
                    <h5 class="card-title">${threat.file_path}</h5>
                    <p class="card-text">
                        <strong>Type:</strong> ${threat.type}<br>
                        <strong>Confidence:</strong> ${threat.confidence}%<br>
                        <strong>Description:</strong> ${threat.description}
                    </p>
                    <div class="threat-actions">
                        <button class="button button-secondary view-code" data-file-path="${threat.file_path}">View Code</button>
                        <button class="button button-primary quarantine-file" data-file-path="${threat.file_path}">Quarantine File</button>
                    </div>
                </div>
            </div>
        `;
    });

    $resultsContainer.html(threatHtml);
}

// Handle scan completion
function scanCompleted(data) {
    stopProgressPolling();
    resetScanControls();

    const message = data.threats.length > 0
        ? `Scan completed. Found ${data.threats.length} potential threats.`
        : 'Scan completed. No threats detected.';

    Swal.fire({
        icon: data.threats.length > 0 ? 'warning' : 'success',
        title: 'Scan Complete',
        text: message,
        allowOutsideClick: true,
        allowEscapeKey: true
    });
}

// Stop progress polling
function stopProgressPolling() {
    if (scanProgressInterval) {
        clearInterval(scanProgressInterval);
        scanProgressInterval = null;
    }
    activeScanId = null;
}

// Reset scan controls
function resetScanControls() {
    $('#start-malware-scan').prop('disabled', false);
    $('#cancel-malware-scan').prop('disabled', true);
}

    // Initialize malware scan when on malware detection page
    jQuery(document).ready(function() {
        if ($('#malware-detection-page').length) {
            initMalwareScan();
        }
    });

})(jQuery);