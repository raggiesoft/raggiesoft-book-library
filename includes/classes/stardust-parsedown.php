<?php
/**
 * @file stardust-parsedown.php
 * @brief Custom extension of the Parsedown Markdown parser.
 * 
 * ARCHITECTURE & MAINTENANCE NOTES:
 * - Domain: Stardust Engine -> Markdown Parsing
 * - Context: Extends the base `Parsedown` engine to inject Stardust-specific accessibility and structural enhancements (e.g., `scope="col"` on table headers).
 * - Inheritance: Subclasses `Parsedown` and overrides specific DOM rendering methods like `blockTableComplete()`.
 * - Integration: Requires the base Parsedown library located in `../components/3rdparty/parsedown/parsedown.php`.
 * - DO NOT modify the core array manipulation logic for `Block` elements without fully understanding the Parsedown internal abstract syntax tree (AST).
 */
// Go up one level from /classes/ to /includes/, then navigate down into the 3rdparty directory
require_once __DIR__ . '/../components/3rdparty/parsedown/parsedown.php';

class StardustParsedown extends Parsedown {


    
    protected function blockTableComplete(array $Block) {
        $Block = parent::blockTableComplete($Block);

        if (!$Block) {
            return $Block;
        }

        // 1. Target the array of <th> elements in the <thead>
        if (isset($Block['element']['text'][0]['text'][0]['text'])) {
            $headers = &$Block['element']['text'][0]['text'][0]['text'];
            
            // Apply scope="col" to all standard headers
            foreach ($headers as &$th) {
                $th['attributes']['scope'] = 'col';
            }
            
            // 2. Check our trigger: Is the top-left header cell empty?
            $isRowHeaderTable = empty(trim($headers[0]['text']));
            
            // 3. If triggered, convert the first <td> in every <tbody> row to a <th scope="row">
            if ($isRowHeaderTable && isset($Block['element']['text'][1]['text'])) {
                $rows = &$Block['element']['text'][1]['text']; // The <tbody> <tr>s
                
                foreach ($rows as &$tr) {
                    if (isset($tr['text'][0])) {
                        // Change 'td' to 'th'
                        $tr['text'][0]['name'] = 'th';
                        // Add the row scope attribute
                        $tr['text'][0]['attributes']['scope'] = 'row';
                    }
                }
            }
        }

        // 4. Add your Web Awesome Pro classes
        $Block['element']['attributes']['class'] = 'table table-striped table-hover align-middle';

        // 5. Wrap the entire table in the responsive div
        $ResponsiveWrapper = array(
            'name' => 'div',
            'attributes' => array(
                'class' => 'table-responsive my-4'
            ),
            'handler' => 'element',
            'text' => $Block['element']
        );

        $Block['element'] = $ResponsiveWrapper;

        return $Block;
    }

    // NEW EXTENSION: Handle Checkbox Lists for DSP Forms
    protected function blockLi($Line, array $Block = null) {
        // Call the parent to do the heavy lifting of parsing the list item
        $Block = parent::blockLi($Line, $Block);

        if (!$Block) {
            return $Block;
        }

        // Target the text content of the list item
        if (isset($Block['element']['handler']) && $Block['element']['handler'] === 'line') {
            $text = $Block['element']['text'];

            // Check if the text starts with a Markdown checkbox pattern
            $isChecked = false;
            $isCheckbox = false;

            if (preg_match('/^\[[xX]\]\s/', $text)) {
                $isChecked = true;
                $isCheckbox = true;
                $text = preg_replace('/^\[[xX]\]\s/', '', $text); // Remove the [x]
            } elseif (preg_match('/^\[ \]\s/', $text)) {
                $isCheckbox = true;
                $text = preg_replace('/^\[ \]\s/', '', $text); // Remove the [ ]
            }

            // If a checkbox pattern was found, inject the HTML
            if ($isCheckbox) {
                // Generate a unique ID for accessibility mapping
                $checkboxId = 'chk-' . uniqid();

                $checkboxHtml = '<input class="form-check-input me-2" type="checkbox" id="' . $checkboxId . '" disabled';
                if ($isChecked) {
                    $checkboxHtml .= ' checked';
                }
                $checkboxHtml .= ' aria-label="Read-only checkbox for DSP Metadata status">';

                // Update the text property, turning off the 'line' handler so HTML renders correctly
                $Block['element']['handler'] = 'elements';
                $Block['element']['text'] = array(
                    array(
                        'name' => 'div',
                        'attributes' => array('class' => 'form-check d-flex align-items-center mb-1'),
                        'text' => array(
                            array('rawHtml' => $checkboxHtml),
                            array(
                                'name' => 'label',
                                'attributes' => array(
                                    'class' => 'form-check-label text-dark', 
                                    'for' => $checkboxId
                                ),
                                'handler' => 'line',
                                'text' => $text // The remaining text after the [x] is stripped
                            )
                        )
                    )
                );
                
                // Add a class to the parent <li> to remove standard bullet styling
                $Block['element']['attributes']['class'] = 'list-unstyled';
            }
        }

        return $Block;
    }

    

    // NEW EXTENSION: Handle Corporate Emails
    protected function blockQuoteComplete($Block) {
        if (!isset($Block['element']['handler']['argument'][0])) return $Block;
        
        $firstLine = $Block['element']['handler']['argument'][0];
        
        // Check if the blockquote starts with **From:** to format it as a Corporate Email
        if (strpos($firstLine, '**From:**') === 0) {
            $Block['element']['name'] = 'wa-card';
            $Block['element']['attributes'] = [
                'class' => 'my-4 w-100'
            ];
            
            // Add a little envelope icon header using the Web Awesome card header slot
            array_unshift($Block['element']['handler']['argument'], '<div slot="header" style="font-size: 0.85em; text-transform: uppercase; font-weight: bold; opacity: 0.7;"><i class="fa-duotone fa-envelope" style="margin-right: 8px;"></i> Secure Corporate Transmission</div>');
            
            return $Block;
        }
        
        $alerts = [
            '[!NOTE]' => ['variant' => 'neutral', 'icon' => 'fa-circle-info'],
            '[!TIP]' => ['variant' => 'success', 'icon' => 'fa-lightbulb'],
            '[!WARNING]' => ['variant' => 'warning', 'icon' => 'fa-triangle-exclamation'],
            '[!CAUTION]' => ['variant' => 'danger', 'icon' => 'fa-radiation'],
            '[!IMPORTANT]' => ['variant' => 'primary', 'icon' => 'fa-star'],
        ];

        foreach ($alerts as $trigger => $config) {
            if (strpos($firstLine, $trigger) === 0) {
                // Remove the trigger
                $Block['element']['handler']['argument'][0] = trim(str_replace($trigger, '', $firstLine));
                
                // Add icon raw HTML
                $Block['element']['handler']['argument'][0] = '<i slot="icon" class="fa-duotone ' . $config['icon'] . '"></i> ' . $Block['element']['handler']['argument'][0];
                
                // Convert to Web Awesome Alert
                $Block['element']['name'] = 'wa-alert';
                $Block['element']['attributes'] = [
                    'variant' => $config['variant'],
                    'open' => 'true',
                    'class' => 'my-4 shadow-sm'
                ];
                
                break;
            }
        }
        
        return $Block;
    }

    
    // NEW EXTENSION: Handle Image Formatting via URL Hashes (#fullwidth, #inline)
    protected function inlineImage($Excerpt) {
        $Image = parent::inlineImage($Excerpt);
        if (!$Image) return $Image;

        $src = $Image['element']['attributes']['src'];
        
        // Default styling for standard images
        $classes = 'img-fluid rounded shadow border border-secondary my-4';
        $style = 'max-width: 80%; display: block; margin: 0 auto;';
        
        if (strpos($src, '#fullwidth') !== false) {
            $classes = 'img-fluid rounded shadow border border-secondary my-4 w-100';
            $style = 'display: block;';
            $Image['element']['attributes']['src'] = str_replace('#fullwidth', '', $src);
        } elseif (strpos($src, '#inline') !== false) {
            $classes = 'img-fluid rounded shadow ms-4 mb-3 float-md-end';
            $style = 'max-width: 350px;';
            $Image['element']['attributes']['src'] = str_replace('#inline', '', $src);
        }
        
        $Image['element']['attributes']['class'] = $classes;
        if ($style) {
            $Image['element']['attributes']['style'] = $style;
        }

        // We wrap it in a figure so the float doesn't break everything, or just return the image
        return $Image;
    }

    // Pre-process shortcodes before Parsedown gets confused by raw HTML blocks
    public function text($text) {
        // Strip out AI Meta notes so they don't render for human readers
        $text = preg_replace('/\\\\?\[Note to the AI:.*?\\\\?\]/is', '', $text);
        
        // Replace {{cms}} with the actual assets CDN
        $text = str_replace('{{cms}}', 'https://assets.raggiesoft.com', $text);
        
        // Pre-process [credential ...] shortcodes anywhere in the text
        $text = preg_replace_callback('/\[credential\s+(.+?)\]/', function($matches) {
            $attributesString = $matches[1];
            
            $attrs = [];
            preg_match_all('/([a-zA-Z]+)="([^"]*)"/', $attributesString, $attrMatches, PREG_SET_ORDER);
            foreach ($attrMatches as $match) {
                $attrs[$match[1]] = $match[2];
            }
            
            $title = $attrs['title'] ?? 'Credential';
            $desc = $attrs['desc'] ?? '';
            $lore = $attrs['lore'] ?? '';
            $color = $attrs['color'] ?? 'primary';
            $icon = $attrs['icon'] ?? 'fa-id-card';
            
            $id = 'cred-' . uniqid();
            
            return '<wa-button variant="' . $color . '" size="small" class="me-2 mb-2" outline onclick="document.getElementById(\'' . $id . '\').show()">
<i class="fa-duotone ' . $icon . ' me-2"></i> ' . htmlspecialchars($title) . '
</wa-button>
<wa-dialog id="' . $id . '" label="' . htmlspecialchars($title) . '">
<i slot="label-icon" class="fa-duotone ' . $icon . '"></i>
<div class="mb-4">
<h6 class="text-uppercase small opacity-75 mb-2">Technical Definition</h6>
<p class="mb-0 text-body-secondary">' . htmlspecialchars($desc) . '</p>
</div>
<wa-alert variant="' . $color . '" open class="shadow-sm">
<i slot="icon" class="fa-duotone fa-shield-check"></i>
<strong class="d-block mb-1">Application to the Universe</strong>
<div class="small">' . htmlspecialchars($lore) . '</div>
</wa-alert>
<wa-button slot="footer" variant="neutral" onclick="document.getElementById(\'' . $id . '\').hide()">Close</wa-button>
</wa-dialog>';
        }, $text);
        $text = preg_replace('/<aac>(.*?)<\/aac>/is', '<span class="visually-hidden">AAC Device: </span><em class="aac-device">$1</em>', $text);
        
        $text = preg_replace('/<asl>(.*?)<\/asl>/is', '<span class="visually-hidden">American Sign Language: </span><em class="asl-sign">$1</em>', $text);
        $text = preg_replace('/<sgn>(.*?)<\/sgn>/is', '<span class="visually-hidden">Signed: </span><em class="generic-sign">$1</em>', $text);
        $text = preg_replace('/<sms>(.*?)<\/sms>/is', '<span class="visually-hidden">Text Message: </span><em class="sms-message">$1</em>', $text);
        return parent::text($text);
    }
}
?>



