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


    
    protected $frameworkMode;

    public function __construct($frameworkMode = 'web-awesome') {
        $this->frameworkMode = $frameworkMode;
    }
    
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

        // 4. Add your Web Awesome Pro / Bootstrap classes
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

    // Handle Checkbox Lists for DSP Forms
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

    // Handle Corporate Emails
    protected function blockQuoteComplete($Block) {
        if (!isset($Block['element']['handler']['argument'][0])) return $Block;
        
        $firstLine = $Block['element']['handler']['argument'][0];
        
        // Check if the blockquote starts with **From:** to format it as a Corporate Email
        if (strpos($firstLine, '**From:**') === 0) {
            if ($this->frameworkMode === 'web-awesome') {
                $Block['element']['name'] = 'wa-card';
                $Block['element']['attributes'] = [
                    'class' => 'my-4 w-100'
                ];
                array_unshift($Block['element']['handler']['argument'], '<div slot="header" style="font-size: 0.85em; text-transform: uppercase; font-weight: bold; opacity: 0.7;"><i class="fa-duotone fa-envelope" style="margin-right: 8px;"></i> Secure Corporate Transmission</div>');
            } else {
                $Block['element']['name'] = 'div';
                $Block['element']['attributes'] = [
                    'class' => 'card my-4 w-100'
                ];
                array_unshift($Block['element']['handler']['argument'], '<div class="card-header" style="font-size: 0.85em; text-transform: uppercase; font-weight: bold; opacity: 0.7;"><i class="fa-solid fa-envelope" style="margin-right: 8px;"></i> Secure Corporate Transmission</div><div class="card-body">');
                array_push($Block['element']['handler']['argument'], '</div>');
            }
            return $Block;
        }
        
        $alerts = [
            '[!NOTE]' => ['variant' => 'neutral', 'bs_variant' => 'secondary', 'icon' => 'fa-circle-info'],
            '[!TIP]' => ['variant' => 'success', 'bs_variant' => 'success', 'icon' => 'fa-lightbulb'],
            '[!WARNING]' => ['variant' => 'warning', 'bs_variant' => 'warning', 'icon' => 'fa-triangle-exclamation'],
            '[!CAUTION]' => ['variant' => 'danger', 'bs_variant' => 'danger', 'icon' => 'fa-radiation'],
            '[!IMPORTANT]' => ['variant' => 'brand', 'bs_variant' => 'primary', 'icon' => 'fa-star'],
        ];

        foreach ($alerts as $trigger => $config) {
            if (strpos($firstLine, $trigger) === 0) {
                // Remove the trigger
                $Block['element']['handler']['argument'][0] = trim(str_replace($trigger, '', $firstLine));
                
                if ($this->frameworkMode === 'web-awesome') {
                    $Block['element']['handler']['argument'][0] = '<i slot="icon" class="fa-duotone ' . $config['icon'] . '"></i> ' . $Block['element']['handler']['argument'][0];
                    $Block['element']['name'] = 'wa-callout';
                    $Block['element']['attributes'] = [
                        'variant' => $config['variant'],
                        'appearance' => 'accent',
                        'class' => 'my-4 shadow-sm'
                    ];
                } else {
                    $Block['element']['handler']['argument'][0] = '<i class="fa-solid ' . $config['icon'] . ' me-2"></i> ' . $Block['element']['handler']['argument'][0];
                    $Block['element']['name'] = 'div';
                    $Block['element']['attributes'] = [
                        'class' => 'alert alert-' . $config['bs_variant'] . ' my-4 shadow-sm',
                        'role' => 'alert'
                    ];
                }
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
            $color = $attrs['color'] ?? 'brand';
            // Normalize legacy Web Awesome v2 color names to v3.13.0 semantic variants
            $waVariantMap = ['primary' => 'brand', 'info' => 'brand', 'secondary' => 'neutral', 'dark' => 'neutral'];
            $waColor = $waVariantMap[$color] ?? $color;
            $icon = $attrs['icon'] ?? 'fa-id-card';
            
            $id = 'cred-' . uniqid();
            
            if ($this->frameworkMode === 'web-awesome') {
                return '<wa-button variant="' . $waColor . '" appearance="outlined" size="small" class="me-2 mb-2" onclick="document.getElementById(\'' . $id . '\').show()">
<i slot="start" class="fa-duotone ' . $icon . '"></i> ' . htmlspecialchars($title) . '
</wa-button>
<wa-dialog id="' . $id . '" label="' . htmlspecialchars($title) . '">
<i slot="label-icon" class="fa-duotone ' . $icon . '"></i>
<div class="mb-4">
<h6 class="text-uppercase small opacity-75 mb-2">Technical Definition</h6>
<p class="mb-0 text-body-secondary">' . htmlspecialchars($desc) . '</p>
</div>
<wa-callout variant="' . $waColor . '" appearance="accent" class="shadow-sm">
<i slot="icon" class="fa-duotone fa-shield-check"></i>
<strong class="d-block mb-1">Application to the Universe</strong>
<div class="small">' . htmlspecialchars($lore) . '</div>
</wa-callout>
<wa-button slot="footer" variant="neutral" onclick="document.getElementById(\'' . $id . '\').hide()">Close</wa-button>
</wa-dialog>';
            } else {
                $bsColor = str_replace('neutral', 'secondary', $color); // Simple mapping if needed
                return '<button type="button" class="btn btn-outline-' . $bsColor . ' btn-sm me-2 mb-2" data-bs-toggle="modal" data-bs-target="#' . $id . '">
<i class="fa-duotone ' . $icon . ' me-2"></i> ' . htmlspecialchars($title) . '
</button>
<div class="modal fade" id="' . $id . '" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa-duotone ' . $icon . ' me-2"></i> ' . htmlspecialchars($title) . '</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-4">
          <h6 class="text-uppercase small opacity-75 mb-2">Technical Definition</h6>
          <p class="mb-0 text-body-secondary">' . htmlspecialchars($desc) . '</p>
        </div>
        <div class="alert alert-' . $bsColor . ' shadow-sm" role="alert">
          <h6 class="alert-heading"><i class="fa-duotone fa-shield-check me-2"></i> Application to the Universe</h6>
          <div class="small">' . htmlspecialchars($lore) . '</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>';
            }
        }, $text);
        $text = preg_replace_callback('/<lang="([^"]+)" trans="([^"]+)">(.*?)<\/lang>/is', function($matches) {
            $langCode = htmlspecialchars($matches[1]);
            $translated = htmlspecialchars($matches[2]);
            $original = $matches[3];
            
            return '<span class="foreign-language" lang="' . $langCode . '" data-original="' . htmlspecialchars($original) . '" data-translation="' . $translated . '">' . $original . '</span>';
        }, $text);
        
        $text = preg_replace('/<aac>(.*?)<\/aac>/is', '<span class="visually-hidden">AAC Device: </span><em class="aac-device">$1</em>', $text);
        
        $text = preg_replace('/<asl>(.*?)<\/asl>/is', '<span class="visually-hidden">American Sign Language: </span><em class="asl-sign">$1</em>', $text);
        $text = preg_replace('/<sgn>(.*?)<\/sgn>/is', '<span class="visually-hidden">Signed: </span><em class="generic-sign">$1</em>', $text);
        $text = preg_replace('/<sms>(.*?)<\/sms>/is', '<span class="visually-hidden">Text Message: </span><em class="sms-message">$1</em>', $text);

        // =====================================================================
        // ZOË PATEL COGNITIVE KERNEL LOG PARSER & SYNTAX HIGHLIGHTER
        // =====================================================================
        // Narrative Context:
        // Zoë is autistic and processes her internal thoughts, sensory telemetry,
        // social scripts, and thread priorities like a C#/.NET application writing
        // to structured system logs (`ZOË.Systems.Logs.KernelLog`, `EmotionalStateLog`,
        // `SystemSchedulerLog`, `ActionLog`, `InternalStateLog`).
        //
        // Supported Authoring Formats in Markdown:
        // 1. Explicit semantic tags: <zoe-log>...</zoe-log> or <kernellog>...</kernellog>
        // 2. Fenced code blocks: ```zoe-log ... ``` or ```kernellog ... ```
        // 3. Automatic zero-config detection: Consecutive lines/paragraphs starting
        //    with `ZOË.Systems.Logs.` or `ZOE.Systems.Logs.` (including lines wrapped
        //    in Markdown italics `*...*` or `_..._` from Word/.docx migrations).
        // =====================================================================

        // 1. Fenced code blocks: ```zoe-log or ```kernellog
        $text = preg_replace_callback('/```(?:zoe-log|kernellog)\s*\R([\s\S]*?)\R```/iu', function($matches) {
            return "\n\n" . $this->renderZoeKernelLogBlock($matches[1]) . "\n\n";
        }, $text);

        // 2. Explicit custom tags: <zoe-log>...</zoe-log> or <kernellog>...</kernellog>
        $text = preg_replace_callback('/<(?:zoe-log|kernellog)>(.*?)<\/(?:zoe-log|kernellog)>/isu', function($matches) {
            return "\n\n" . $this->renderZoeKernelLogBlock($matches[1]) . "\n\n";
        }, $text);

        // 3. Automatic zero-config detection of standalone or consecutive `ZOË.Systems.Logs.*` paragraphs
        $text = preg_replace_callback(
            '/(?:^[ \t]*[*_]?[ \t]*(?:ZOË|ZOE)\.Systems\.Logs\.[A-Za-z0-9_]+[^\r\n]*[*_]?[ \t]*(?:\R[ \t]*)*)+/imu',
            function($matches) {
                return "\n\n" . $this->renderZoeKernelLogBlock($matches[0]) . "\n\n";
            },
            $text
        );

        return parent::text($text);
    }

    /**
     * Renders a block of one or more Zoë Patel cognitive kernel log entries into
     * an accessible, WCAG 2.1 AAA syntax-highlighted diagnostic console component.
     *
     * @param string $rawBlock Raw multi-line string containing one or more log entries.
     * @return string Complete HTML markup for the `.zoe-kernel-log` region.
     */
    protected function renderZoeKernelLogBlock(string $rawBlock): string {
        $lines = preg_split('/\R+/u', trim($rawBlock));
        $entriesHtml = [];
        $entryCount = 0;

        foreach ($lines as $rawLine) {
            $trimmed = trim($rawLine);
            if ($trimmed === '') {
                continue;
            }
            // Strip optional outer Markdown emphasis markers (* or _) from Word imports
            if (
                (str_starts_with($trimmed, '*') && str_ends_with($trimmed, '*') && !str_starts_with($trimmed, '**')) ||
                (str_starts_with($trimmed, '_') && str_ends_with($trimmed, '_') && !str_starts_with($trimmed, '__'))
            ) {
                $trimmed = trim(substr($trimmed, 1, -1));
            }
            // Strip optional leading blockquote '>' if copied from a quoted block
            $trimmed = ltrim($trimmed, "> \t");
            if ($trimmed === '') {
                continue;
            }

            $entryCount++;
            $entriesHtml[] = $this->highlightZoeLogLine($trimmed, $entryCount);
        }

        if (empty($entriesHtml)) {
            return '';
        }

        $countLabel = $entryCount === 1 ? '1 ENTRY' : ($entryCount . ' ENTRIES');

        return '<div class="zoe-kernel-log my-4" role="region" aria-label="Zoë&#039;s Internal System Log">'
            . '<span class="visually-hidden">Zoë&#039;s Internal System Log: </span>'
            . '<div class="zoe-kernel-log-header" aria-hidden="true">'
            . '<div class="zoe-kernel-log-dots">'
            . '<span class="zkl-dot zkl-dot-red"></span>'
            . '<span class="zkl-dot zkl-dot-amber"></span>'
            . '<span class="zkl-dot zkl-dot-green"></span>'
            . '</div>'
            . '<div class="zoe-kernel-log-title">ZOË.Systems.Logs <span class="zkl-header-sep">//</span> Cognitive Runtime Telemetry</div>'
            . '<div class="zoe-kernel-log-meta">' . htmlspecialchars($countLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</div>'
            . '</div>'
            . '<div class="zoe-kernel-log-body">'
            . implode('', $entriesHtml)
            . '</div>'
            . '</div>';
    }

    /**
     * Tokenizes and syntax-highlights a single line from Zoë's cognitive kernel log.
     * Uses a single-pass offset-captured regular expression lexer so HTML attributes
     * and entities are never double-escaped or corrupted.
     *
     * @param string $rawLine Single log line (e.g. `ZOË.Systems.Logs.KernelLog: ... 10-12-2015 07:05:15`).
     * @param int    $lineNum 1-based line number within the current log block.
     * @return string HTML markup for a single `.zoe-log-entry` row.
     */
    protected function highlightZoeLogLine(string $rawLine, int $lineNum = 1): string {
        // Normalize any pre-escaped HTML entities back to raw UTF-8 before single-pass lexing
        $line = html_entity_decode($rawLine, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $nsPrefixHtml = '';
        $streamName = 'KernelLog';
        $rest = $line;

        // 1. Extract leading `ZOË.Systems.Logs.<StreamName>[:|,]` prefix if present
        if (preg_match('/^((?:ZOË|ZOE)\.Systems\.Logs\.)([A-Za-z0-9_]+)([:,]?)\s*(.*)$/us', $line, $nsMatch)) {
            $nsRoot = htmlspecialchars($nsMatch[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $streamName = $nsMatch[2];
            $streamEsc = htmlspecialchars($streamName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $streamSlug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $streamName));
            $delimEsc = htmlspecialchars($nsMatch[3] !== '' ? $nsMatch[3] : ':', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $rest = $nsMatch[4];

            $nsPrefixHtml = '<span class="zkl-ns">' . $nsRoot . '</span>'
                . '<span class="zkl-stream zkl-stream-' . $streamSlug . '">' . $streamEsc . '</span>'
                . '<span class="zkl-punct">' . $delimEsc . '</span> ';
        }

        // 2. Extract trailing timestamp (`MM-DD-YYYY HH:MM:SS`) if present at end of line
        $timestampHtml = '';
        if (preg_match('/^(.*?)\s+(\d{2}-\d{2}-\d{4}\s+\d{2}:\d{2}:\d{2})\.?$/us', $rest, $tsMatch)) {
            $rest = $tsMatch[1];
            $tsEsc = htmlspecialchars($tsMatch[2], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $timestampHtml = '<time class="zkl-timestamp">' . $tsEsc . '</time>';
        }

        // 3. Classify the entry's semantic severity/stream level for row accent styling
        $rowLevel = 'info';
        if (preg_match('/\b(?:CRITICAL\s+ERROR|CRITICAL\s+ALERT|[A-Za-z0-9_.]+Exception)\b/u', $rest)) {
            $rowLevel = 'critical';
        } elseif (preg_match('/\b(?:WARNING|Error|Alert|Conflict)\s*:/u', $rest)) {
            $rowLevel = 'warning';
        } elseif (strcasecmp($streamName, 'EmotionalStateLog') === 0) {
            $rowLevel = 'emotional';
        } elseif (strcasecmp($streamName, 'ActionLog') === 0 || preg_match('/\b(?:Execute|Required\s+Action|Action)\s*:/u', $rest)) {
            $rowLevel = 'action';
        } elseif (strcasecmp($streamName, 'SystemSchedulerLog') === 0) {
            $rowLevel = 'scheduler';
        }

        // 4. Single-pass token lexer over `$rest`
        $tokenPattern = '/'
            // Severity badges (CRITICAL ERROR:, WARNING:, Error:, Alert:)
            . '(?P<severity>\b(?:CRITICAL\s+ERROR|CRITICAL\s+ALERT|WARNING|Error|Alert)\s*:)'
            // .NET / C# Exceptions (e.g. System.IO.FileNotFoundException(), ComplianceViolationException)
            . '|(?P<exception>\b[A-Za-z0-9_]+(?:\.[A-Za-z0-9_]+)*Exception\b(?:\(\))?)'
            // Multithreaded concurrency identifiers (e.g. Thread 1 (Self), Thread 3 (Jordan))
            . '|(?P<thread>\bThread\s+\d+\s*\([^)]+\))'
            // Bracketed filesystem paths, states, and context blocks ([...])
            . '|(?P<bracket>\[[^\]]+\])'
            // Quoted string/file literals ("..." or '...' when not an English contraction/possessive)
            . '|(?P<string>"(?:[^"\\\\]|\\\\.)*"|(?<![\p{L}\p{N}])\'(?:[^\'\\\\\r\n]|\\\\.)*\'(?![\p{L}\p{N}]))'
            // Cognitive phase & structured key labels (Hypothesis:, Analysis:, Conclusion:, Action:, etc.)
            . '|(?P<label>\b(?:Hypothesis|Analysis|Conclusion|Required\s+Action|Action|Decision|Reason|Clarification\s+received|Input|Output|Conflict|Query|Result|Method|Data|Task|Execute|Status|State|Directive|Priority|Observation|Assessment|Override|Recommendation|Diagnosis|HeartRate|MuscleTension|Temperature|WeightDelta|Current\.Status|Personal\.State|Personal\.Note|Jordan\.State)\s*[:.])'
            // Process diagnostic verbs (Running diagnostics..., Recalculating..., etc.)
            . '|(?P<diagnostic>\b(?:Running\s+diagnostics|Processing\s+new\s+input|Searching\s+for\s+alternate\s+files|Cross-referencing|Updating\s+analysis|Recalculating|Monitoring|Initiating|Executing|Compiling)\.{0,3})'
            // Functional / Node / Entity calls (Agent(Jordan), System.Admin(HallDirector_Kelsey), Observer(Aubrie), etc.)
            . '|(?P<function>\b(?:Agents?|Observers?|Nodes?|Users?|Targets?|Subjects?|Participants?|Process|Event|System(?:\.Admins?)?|Hostile_Agents?|Family_Nodes?|Ally_Nodes?|Sanctuary_Nodes?|SubProtocol|Maintain|Define|Execute|ContinueCuddle|IsCuddling|Search|Love|KernelPanic)\s*\([^)]*\))'
            // Dotted C#/.NET object, method & assembly identifiers (Jordan.OS.State, Physical.Response(Self), Pact.Members)
            . '|(?P<identifier>\b[\p{L}_][\p{L}\p{N}_]*(?:\.[\p{L}_][\p{L}\p{N}_]*)+\b(?:\s*\([^)]*\))?)'
            // Booleans, nulls, and uppercase state constants
            . '|(?P<constant>\b(?:true|false|True|False|TRUE|FALSE|null|NULL|MAX|MIN|HIGH|LOW|CRITICAL|ACTIVE|STANDBY|SAFE|UNSAFE|LOCKED|UNLOCKED|OK|FAILED|SUCCESS)\b)'
            // Numeric & telemetry metrics (100%, -4 lbs, 99.1F, 95bpm)
            . '|(?P<number>(?<![\p{L}\p{N}_])[+-]?\d+(?:\.\d+)?(?:\s*(?:%|lbs|bpm|F|C|ms|s|m|h|px|dB))?(?![\p{L}\p{N}_]))'
            // Comparison, assignment, and state-transition operators (==, !=, ->, =>, =, >, <)
            . '|(?P<operator>==|!=|->|=>|>=|<=|[=<>])'
            . '/u';

        $bodyHtml = '';
        $lastOffset = 0;
        $length = strlen($rest);

        if (preg_match_all($tokenPattern, $rest, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($matches as $match) {
                $fullMatchText = $match[0][0];
                $matchOffset = $match[0][1];

                // Append any plain text preceding this token
                if ($matchOffset > $lastOffset) {
                    $plainChunk = substr($rest, $lastOffset, $matchOffset - $lastOffset);
                    $bodyHtml .= htmlspecialchars($plainChunk, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                }

                // Determine which named token group matched
                $tokenClass = 'zkl-text';
                foreach (['severity', 'exception', 'thread', 'bracket', 'string', 'label', 'diagnostic', 'function', 'identifier', 'constant', 'number', 'operator'] as $groupName) {
                    if (isset($match[$groupName]) && $match[$groupName][1] !== -1 && $match[$groupName][0] !== '') {
                        $tokenClass = 'zkl-' . $groupName;
                        break;
                    }
                }

                $escapedToken = htmlspecialchars($fullMatchText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $bodyHtml .= '<span class="' . $tokenClass . '">' . $escapedToken . '</span>';

                $lastOffset = $matchOffset + strlen($fullMatchText);
            }
        }

        // Append any remaining plain text after the final token
        if ($lastOffset < $length) {
            $bodyHtml .= htmlspecialchars(substr($rest, $lastOffset), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        return '<div class="zoe-log-entry zoe-log-level-' . $rowLevel . '" data-line="' . $lineNum . '">'
            . '<span class="zoe-log-gutter" aria-hidden="true">' . str_pad((string)$lineNum, 2, '0', STR_PAD_LEFT) . '</span>'
            . '<div class="zoe-log-main">' . $nsPrefixHtml . $bodyHtml . '</div>'
            . $timestampHtml
            . '</div>';
    }
}
?>



