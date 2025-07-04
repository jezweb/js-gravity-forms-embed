/**
 * Admin JavaScript for Gravity Forms JS Embed
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // API Key show/hide toggle
        const $apiKeyField = $('#gf-api-key-field');
        const $apiKeyToggle = $('#gf-api-key-toggle');
        const $apiKeyCopy = $('#gf-api-key-copy');
        
        if ($apiKeyField.length && $apiKeyToggle.length) {
            // Initially hide the API key
            const apiKey = $apiKeyField.val();
            $apiKeyField.val('••••••••••••••••••••••••••••••••');
            
            let isShowing = false;
            
            $apiKeyToggle.on('click', function() {
                if (isShowing) {
                    $apiKeyField.val('••••••••••••••••••••••••••••••••');
                    $apiKeyToggle.find('.dashicons').removeClass('dashicons-hidden').addClass('dashicons-visibility');
                    $apiKeyToggle.find('.text').text('Show');
                    isShowing = false;
                } else {
                    $apiKeyField.val(apiKey);
                    $apiKeyToggle.find('.dashicons').removeClass('dashicons-visibility').addClass('dashicons-hidden');
                    $apiKeyToggle.find('.text').text('Hide');
                    isShowing = true;
                }
            });
        }
        
        // API Key copy functionality
        if ($apiKeyCopy.length && $apiKeyField.length) {
            $apiKeyCopy.on('click', function() {
                const originalValue = $apiKeyField.val();
                const wasShowing = originalValue !== '••••••••••••••••••••••••••••••••';
                
                // Temporarily show the actual key for copying
                if (!wasShowing) {
                    $apiKeyField.val(apiKey);
                }
                
                // Select and copy
                $apiKeyField.select();
                document.execCommand('copy');
                
                // Restore hidden state if it was hidden
                if (!wasShowing) {
                    $apiKeyField.val('••••••••••••••••••••••••••••••••');
                }
                
                // Show feedback
                const originalText = $apiKeyCopy.html();
                $apiKeyCopy.html('<span class="dashicons dashicons-yes" style="vertical-align: text-bottom;"></span> Copied!');
                setTimeout(function() {
                    $apiKeyCopy.html(originalText);
                }, 2000);
            });
        }
        
        // Domain whitelist helper
        const $domainTextarea = $('textarea[name="js_embed_domains"]');
        if ($domainTextarea.length) {
            // Add helper text for current domain
            const currentDomain = window.location.origin;
            const helperText = $('<p class="description" style="margin-top: 5px;">Current domain: <code>' + currentDomain + '</code></p>');
            $domainTextarea.after(helperText);
        }
        
        // Security level descriptions
        const $securityLevel = $('#js_embed_security_level');
        if ($securityLevel.length) {
            const descriptions = {
                'low': 'Basic protection with minimal restrictions. Suitable for internal or trusted environments.',
                'medium': 'Balanced security with standard protections. Recommended for most public forms.',
                'high': 'Maximum security with strict validation. May block some legitimate submissions.'
            };
            
            const $description = $('<p class="description" style="margin-top: 5px;"></p>');
            $securityLevel.after($description);
            
            function updateDescription() {
                const level = $securityLevel.val();
                $description.text(descriptions[level] || '');
            }
            
            $securityLevel.on('change', updateDescription);
            updateDescription();
        }
        
        // Rate limit slider
        const $rateLimit = $('#js_embed_rate_limit');
        if ($rateLimit.length) {
            const $display = $('<span style="margin-left: 10px; font-weight: bold;"></span>');
            $rateLimit.after($display);
            
            function updateDisplay() {
                const value = $rateLimit.val();
                $display.text(value + ' requests per hour per IP');
            }
            
            $rateLimit.on('input', updateDisplay);
            updateDisplay();
        }
        
        // Embed code selection
        $('.gf-embed-code-section textarea').on('click', function() {
            $(this).select();
        });
        
        // Theme preview helper
        const $themeSelect = $('#js_embed_theme');
        if ($themeSelect.length) {
            const themeDescriptions = {
                '': 'Default Gravity Forms styling',
                'minimal': 'Clean design with underline inputs and subtle styling',
                'rounded': 'Modern design with rounded corners and soft shadows',
                'material': 'Google Material Design inspired theme',
                'dark': 'Dark mode theme with high contrast',
                'bootstrap': 'Bootstrap 5 style without requiring Bootstrap',
                'tailwind': 'Tailwind CSS style without requiring Tailwind',
                'glass': 'Glassmorphism effect with blur and transparency',
                'flat': 'Bold flat design with vibrant colors',
                'corporate': 'Professional and conservative styling'
            };
            
            const $themeInfo = $('<p class="description" style="margin-top: 5px;"></p>');
            $themeSelect.after($themeInfo);
            
            function updateThemeInfo() {
                const theme = $themeSelect.val();
                $themeInfo.text(themeDescriptions[theme] || '');
            }
            
            $themeSelect.on('change', updateThemeInfo);
            updateThemeInfo();
        }
        
        // Lazy loading placeholder type toggle
        const $placeholderType = $('#js_embed_lazy_placeholder_type');
        const $customContent = $('#custom_placeholder_content');
        
        if ($placeholderType.length && $customContent.length) {
            function toggleCustomContent() {
                if ($placeholderType.val() === 'custom') {
                    $customContent.slideDown(200);
                } else {
                    $customContent.slideUp(200);
                }
            }
            
            $placeholderType.on('change', toggleCustomContent);
            toggleCustomContent(); // Initialize on page load
        }
        
        // Lazy loading threshold helper
        const $lazyThreshold = $('#js_embed_lazy_threshold');
        if ($lazyThreshold.length) {
            const $thresholdDisplay = $('<span style="margin-left: 10px; font-weight: bold;"></span>');
            $lazyThreshold.after($thresholdDisplay);
            
            function updateThresholdDisplay() {
                const value = parseFloat($lazyThreshold.val());
                let description = '';
                
                if (value === 0) {
                    description = 'Load immediately when any part becomes visible';
                } else if (value <= 0.25) {
                    description = 'Load when 25% visible (very early)';
                } else if (value <= 0.5) {
                    description = 'Load when 50% visible (balanced)';
                } else if (value <= 0.75) {
                    description = 'Load when 75% visible (conservative)';
                } else {
                    description = 'Load when fully visible (maximum delay)';
                }
                
                $thresholdDisplay.text(description);
            }
            
            $lazyThreshold.on('input change', updateThresholdDisplay);
            updateThresholdDisplay();
        }
        
        // Lazy loading info tooltips
        const placeholderDescriptions = {
            'minimal': 'Simple spinner with loading message - fastest loading',
            'skeleton': 'Shows form structure outline - gives users preview of content',
            'spinner': 'Large centered spinner - clear loading indication',
            'button': 'Click-to-load button - user-controlled loading',
            'custom': 'Your own HTML content - full customization'
        };
        
        if ($placeholderType.length) {
            const $placeholderInfo = $('<p class="description" style="margin-top: 5px;"></p>');
            $placeholderType.after($placeholderInfo);
            
            function updatePlaceholderInfo() {
                const type = $placeholderType.val();
                $placeholderInfo.text(placeholderDescriptions[type] || '');
            }
            
            $placeholderType.on('change', updatePlaceholderInfo);
            updatePlaceholderInfo();
        }
    });
    
})(jQuery);