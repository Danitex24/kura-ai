# KuraAI API Integration Guide

## Supported AI Services
Currently supports:
- OpenAI (GPT-3.5-turbo and above)
- Coming soon: Claude, Gemini

## Obtaining API Keys

### OpenAI
1. Go to [OpenAI Platform](https://platform.openai.com/)
2. Sign up or log in
3. Navigate to **API Keys**
4. Click **Create new secret key**
5. Copy the generated key
6. Store securely (key won't be shown again)

![OpenAI Key Screenshot](assets/images/openai-key.png)

## Configuring API in KuraAI
1. Navigate to **KuraAI Security > Settings**
2. In the AI Integration section:
   - Toggle "Enable AI-powered security suggestions" ON
   - Select "OpenAI" as AI service
   - Paste your API key
3. Click **Save Changes**

## API Usage Notes
- **Rate limits**: Follow OpenAI's rate limits (typically 3,500 RPM)
- **Costs**: Standard OpenAI API pricing applies
- **Privacy**: Your API key is stored encrypted in database
- **Data sent**: Only issue descriptions and metadata are sent

## Customizing AI Behavior
Modify the AI prompt template in `includes/class-kura-ai-ai-handler.php`:

```php
private function build_openai_prompt($issue) {
    $prompt = "As a WordPress security expert, provide:";
    $prompt .= "\n1. Risk analysis for: " . $issue['message'];
    $prompt .= "\n2. Step-by-step fix instructions";
    $prompt .= "\n3. Prevention recommendations";
    $prompt .= "\n\nFormat: Clear headings, bullet points, and code blocks where needed";
    return $prompt;
}