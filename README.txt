=== KuraAI - AI-Powered WordPress Security ===
Contributors: danovatesolutions
Tags: security, ai, vulnerability, scanner, monitoring, wordpress security
Requires at least: 5.6
Tested up to: 6.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

An AI-powered WordPress security plugin that monitors for vulnerabilities and provides intelligent fixes.

== Description ==

KuraAI is a comprehensive WordPress security plugin that combines automated scanning with AI-powered recommendations to keep your site secure. 

Key features:

* Automated security scanning for vulnerabilities
* AI-powered fix suggestions (OpenAI integration)
* Email notifications for critical issues
* Detailed security reports
* Activity logging
* Quick fix buttons for common issues
* Multilingual support

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/kura-ai` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Configure your settings under Settings > KuraAI Security
4. Run your first security scan

== Frequently Asked Questions ==

= What AI services are supported? =

Currently supports OpenAI. Claude and Gemini support coming soon.

= Do I need an API key? =

For AI suggestions, yes. You can get an OpenAI API key from their website. Basic security scanning works without an API key.

= How often should I run scans? =

We recommend daily scans for most sites. High-traffic or e-commerce sites may want to scan more frequently.

== Screenshots ==

1. Dashboard overview
2. Vulnerability report
3. AI suggestions
4. Activity logs
5. Settings page

== Changelog ==

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release of KuraAI Security Plugin.

== Plugin Code Structure ==

├── kura-ai/
│   ├── admin/
│   │   ├── css/
│   │   │   └── kura-ai-admin.css
│   │   ├── js/
│   │   │   └── kura-ai-admin.js
│   │   ├── partials/
│   │   │   ├── kura-ai-admin-display.php
│   │   │   ├── kura-ai-logs-display.php
│   │   │   ├── kura-ai-reports-display.php
│   │   │   ├── kura-ai-settings-display.php
│   │   │   └── kura-ai-suggestions-display.php
│   │   └── class-kura-ai-admin.php
│   ├── includes/
│   │   ├── ai-integrations/
│   │   ├── class-kura-ai-activator.php
│   │   ├── class-kura-ai-deactivator.php
│   │   ├── class-kura-ai-i18n.php
│   │   ├── class-kura-ai-loader.php
│   │   ├── class-kura-ai-security-scanner.php
│   │   ├── class-kura-ai-ai-handler.php
│   │   ├── class-kura-ai-logger.php
│   │   ├── class-kura-ai-notifier.php
│   │   └── class-kura-ai.php
│   ├── languages/
│   │   └── kura-ai.pot
│   ├── public/
│   │   └── class-kura-ai-public.php
│   ├── assets/
│   │   ├── js/
│   │   ├── css/
│   │   └── images/
│   ├── vendor/
│   ├── index.php
│   ├── kura-ai.php
│   ├── LICENSE.txt
│   ├── README.txt
│   └── uninstall.php
│
└── documentation/
    ├── Installation-Guide.md
    ├── Admin-Walkthrough.md
    └── API-Integration-Guide.md

== Troubleshooting API Issues==


* Common Errors

* Error	Solution
* Invalid API key	Verify key is correct and hasn't expired
* Rate limit exceeded	Wait before making more requests or upgrade plan
* API timeout	Check server internet connection
* Unexpected responses	Review prompt formatting

== Debugging Steps ==

* Check Activity Logs for API errors
* Verify API key is active in your OpenAI account
* Test API key directly with OpenAI's playground
* Check WordPress debug log for errors

== Advanced Integration ==

* For developers wanting deeper integration:

* Custom AI Endpoints

* Override the default handler by adding to your theme's functions.php:

add_filter('kura_ai_custom_ai_handler', function($default, $issue) {
    // Implement custom API call
    $response = my_custom_ai_call($issue);
    return $response;
}, 10, 2);

== Response Processing ==

* Filter AI responses before display:

add_filter('kura_ai_ai_response', function($response) {
    // Add disclaimer to all responses
    return $response . "\n\nNote: Always backup before making changes.";
});

== Security Considerations ==

* Never expose API keys in client-side code
* Regularly rotate API keys
* Monitor usage for unexpected spikes
* Restrict API keys to only necessary permissions

== Support ==

* For API integration help:

* OpenAI Documentation : https://platform.openai.com/docs 
* KuraAI Support Portal : https://www.kuraai.org/support
* Community Forum : https://community.kuraai.org/