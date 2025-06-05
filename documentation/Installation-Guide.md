# KuraAI Installation Guide

## Requirements
- WordPress 5.6 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher
- (For AI features) OpenAI API key

## Installation Methods

### Method 1: WordPress Admin Dashboard
1. Navigate to **Plugins > Add New** in your WordPress admin
2. Search for "KuraAI"
3. Click **Install Now** on the KuraAI plugin
4. After installation completes, click **Activate**

### Method 2: Manual Upload
1. Download the plugin ZIP file
2. Navigate to **Plugins > Add New** in your WordPress admin
3. Click **Upload Plugin**
4. Select the downloaded ZIP file
5. Click **Install Now**
6. After installation completes, click **Activate**

### Method 3: FTP Upload
1. Unzip the plugin package
2. Upload the `kura-ai` folder to your `/wp-content/plugins/` directory
3. Navigate to **Plugins** in your WordPress admin
4. Locate "KuraAI - AI-Powered WordPress Security" in the plugin list
5. Click **Activate**

## Initial Setup
1. After activation, navigate to **KuraAI Security > Settings**
2. Configure the basic settings:
   - Set your preferred scan frequency
   - Enable/disable email notifications
   - Set notification email address
3. For AI features:
   - Enable AI suggestions
   - Select your preferred AI service (OpenAI)
   - Enter your API key
4. Click **Save Changes**

## First Run
1. Navigate to **KuraAI Security > Dashboard**
2. Click **Run Scan Now** to perform your first security scan
3. Review the scan results
4. Apply fixes or request AI suggestions as needed

## Troubleshooting
- **Plugin not appearing**: Ensure you uploaded to the correct plugins directory
- **Activation errors**: Check your PHP version meets requirements
- **Scan failures**: Verify file permissions allow WordPress to read all files
- **AI not working**: Double-check your API key and internet connection

For additional support, visit our [support site](https://www.danovatesolutions.org/kura-ai-support).