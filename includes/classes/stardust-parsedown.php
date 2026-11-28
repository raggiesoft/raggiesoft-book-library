<?php
/**
 * =============================================================================
 * PLAIN-ENGLISH OVERVIEW FOR NEW DEVELOPERS (100-LEVEL JAVASCRIPT / WEB INTRO)
 * =============================================================================
 * File: stardust-parsedown.php
 * Canonical Location: /raggiesoft-servers/components-library/classes/stardust-parsedown.php
 * Copied On Deploy To:
 *   - /raggiesoft.com/subdomains/books/includes/classes/stardust-parsedown.php
 *   - /raggiesoft.com/www/includes/classes/stardust-parsedown.php (and sibling apps)
 *
 * WHAT IS THIS FILE?
 *   Authors write Ocean View Archives novels and lore documents in plain-text
 *   Markdown files (`.md`), where `# Heading` becomes `<h1>Heading</h1>` and
 *   `*italics*` becomes `<em>italics</em>`. By itself, standard Markdown only
 *   knows basic formatting—it does NOT know how to render RaggieSoft's rich
 *   storytelling elements such as:
 *     - Screen-reader-accessible data tables (`<th scope="col">` / `<th scope="row">`)
 *     - Read-only checklist boxes (`- [x]` and `- [ ]`)
 *     - In-universe Corporate Emails (`> **From:** ...`)
 *     - Callout Alert Boxes (`> [!NOTE]`, `> [!WARNING]`, etc.)
 *     - Custom image layout hints (`#fullwidth`, `#inline`)
 *     - Interactive Lore Credential popup buttons (`[credential title="..." ...]`)
 *     - Foreign language hover/screen-reader spans (`<lang="es" trans="...">`)
 *     - Accessible dialogue tags for AAC tablets (`<aac>`), Sign Language
 *       (`<asl>`, `<sgn>`), text messages (`<sms>`), and PA speakers (`<pa>`)
 *     - Authentic amber LED bus destination matrix signs (`<led>913 | ...</led>`)
 *     - Zoë Wren Brooks's C#/.NET cognitive kernel logs (`ZOË.Systems.Logs.*`)
 *
 * WHY DOES IT EXIST?
 *   Instead of writing a Markdown parser from scratch, we use Object-Oriented
 *   Programming (OOP) inheritance (`class StardustParsedown extends Parsedown`)
 *   to extend the popular open-source `Parsedown` library. Just like `class Child
 *   extends Parent` in JavaScript ES6, `StardustParsedown` inherits all standard
 *   Markdown rules from `Parsedown` and overrides specific methods to customize
 *   the HTML output!
 *
 * WHAT FEEDS INTO IT & WHAT DOES IT PRODUCE?
 *   - Input: A raw Markdown string (read from a `.md` file by `sophia/oliver.php`
 *     or a CMS page viewer) passed into `$parsedown->text($markdownString)`.
 *   - Internal Pipeline:
 *       1. `text($text)` runs first as a "pre-processor", using regular expressions
 *          (`preg_replace` / `preg_replace_callback`) to convert custom shortcodes,
 *          dialogue tags (`<sms>`, `<aac>`, `<led>`), and Zoë's kernel logs into
 *          accessible semantic HTML before base `Parsedown` runs.
 *       2. `parent::text($text)` runs the base `Parsedown` line-by-line parser,
 *          which builds a nested PHP associative array (similar to a JavaScript
 *          object tree) called an Abstract Syntax Tree (AST) representing HTML
 *          elements (`['name' => 'div', 'attributes' => [...], ...]` ).
 *       3. While building that tree, Parsedown calls our overridden hook methods
 *          (`blockTableComplete`, `blockLi`, `blockQuoteComplete`, `inlineImage`,
 *          `blockFencedCodeComplete`) so we can upgrade the AST nodes before
 *          Parsedown turns them into the final HTML string.
 *   - Output: Clean, WCAG 2.1 AA/AAA accessible HTML ready to drop straight into
 *     the reader pane (`<article class="story-content">`).
 *
 * IMPORTANT DRY ("DON'T REPEAT YOURSELF") RULE:
 *   Always edit this file inside `/raggiesoft-servers/components-library/classes/`.
 *   `jenna-sync.sh` automatically distributes this master copy to the individual
 *   web apps during deployment.
 * =============================================================================
 */

// `__DIR__` is a built-in PHP constant holding the exact folder where THIS file lives.
// Note: In PHP, the dot `.` operator joins (concatenates) strings together, just like
// the `+` operator joins strings in JavaScript (`__dirname + '/../...'`).
// `require_once` loads the 3rd-party `Parsedown` base class file exactly once so we can extend it.
require_once __DIR__ . '/../components/3rdparty/parsedown/parsedown.php';

/**
 * Custom RaggieSoft subclass of the base `Parsedown` Markdown-to-HTML engine.
 */
class StardustParsedown extends Parsedown {

    /**
     * Stores which UI framework mode is active (defaults to `'web-awesome'`).
     * `protected` means this property can be read by this class and any subclasses,
     * similar to private/protected fields in TypeScript or modern JavaScript classes.
     */
    protected $frameworkMode;

    /**
     * Full CommonMark 0.30 / GitHub Flavored Markdown 32-character ASCII punctuation escape set.
     *
     * WHY WE OVERRIDE BASE PARSEDOWN'S `$specialCharacters`:
     *   Base `Parsedown` (v1.7) only listed the 17 characters from John Gruber's 2004 Markdown 1.0
     *   (`\\`, `` ` ``, `*`, `_`, `{`, `}`, `[`, `]`, `(`, `)`, `>`, `#`, `+`, `-`, `.`, `!`, `|`, `~`),
     *   omitting `$`, `"`, `'`, `%`, `&`, `/`, `:`, `;`, `<`, `=`, `?`, `@`, `^`, and `,`.
     *   As a result, CommonMark / Pandoc GFM escapes like `\$100`, `\"quote\"`, `Don\'t`, `100\%`,
     *   `\<tag\>`, and `A\&B` bled literal backslashes onto public web pages!
     *   Expanding `$specialCharacters` to all 32 CommonMark ASCII punctuation characters—paired
     *   with `htmlspecialchars(..., ENT_NOQUOTES)` in `inlineEscapeSequence()` below so `\<`, `\>`,
     *   and `\&` emit safe HTML entities (`&lt;`, `&gt;`, `&amp;`)—guarantees zero leaked escapes.
     */
    protected $specialCharacters = array(
        '\\', '`', '*', '_', '{', '}', '[', ']', '(', ')', '<', '>', '#', '+', '-', '.', '!', '|', '~',
        '$', '"', '\'', '%', '&', '/', ':', ';', '=', '?', '@', '^', ',',
    );

    /**
     * Class constructor (the PHP equivalent of JavaScript's `constructor(frameworkMode = 'web-awesome')`).
     * Runs automatically whenever code calls `new StardustParsedown()`.
     *
     * @param string $frameworkMode Optional UI styling mode identifier.
     */
    public function __construct($frameworkMode = 'web-awesome') {
        // In PHP, `$this->property` is equivalent to `this.property` in JavaScript.
        $this->frameworkMode = $frameworkMode;
    }

    /**
     * AST HOOK 0: CommonMark-Complete & XSS-Safe Backslash Escape Handler (`inlineEscapeSequence`)
     * -------------------------------------------------------------------------
     * Base `Parsedown::inlineEscapeSequence` returned `'element' => array('rawHtml' => $Excerpt['text'][1])`,
     * which would emit an unescaped `<` if `<` were added to `$specialCharacters`. Returning
     * `'element' => array('text' => $Excerpt['text'][1])` delegates to `Parsedown::escape($text, true)`
     * (`htmlspecialchars(..., ENT_NOQUOTES, 'UTF-8')`), turning `\<` into `&lt;`, `\>` into `&gt;`,
     * `\&` into `&amp;`, and `\$` / `\"` / `\'` into clean `$` / `"` / `'`.
     */
    protected function inlineEscapeSequence($Excerpt) {
        if (isset($Excerpt['text'][1]) && in_array($Excerpt['text'][1], $this->specialCharacters, true)) {
            return array(
                'element' => array('text' => $Excerpt['text'][1]),
                'extent' => 2,
            );
        }
    }
    
    /**
     * AST HOOK 1: Accessible & Responsive Markdown Tables (`blockTableComplete`)
     * -------------------------------------------------------------------------
     * Parsedown calls `blockTableComplete()` right after it finishes reading all
     * rows of a Markdown pipe table (`| Col A | Col B |`).
     *
     * What is `$Block`?
     *   In PHP, `array(...)` or `[...]` is an "associative array"—the PHP equivalent
     *   of a plain JavaScript Object (`{ element: { name: 'table', text: [...] } }`).
     *   Parsedown represents HTML elements as nested associative arrays before
     *   converting them into `<table ...>` strings at the very end.
     *
     * Why do we override this?
     *   Screen readers (VoiceOver, NVDA, JAWS) need `scope="col"` on column headers
     *   and `scope="row"` on row headers to announce which column and row a table
     *   cell belongs to. We also wrap the `<table>` in `<div class="table-responsive">`
     *   so wide tables scroll horizontally on phones instead of breaking the page width.
     */
    protected function blockTableComplete(array $Block) {
        // `parent::blockTableComplete($Block)` is like `super.blockTableComplete(Block)` in JS:
        // let the base Parsedown class build the standard table structure first.
        $Block = parent::blockTableComplete($Block);

        // Guard clause: if Parsedown decided this wasn't a valid table, return early.
        if (!$Block) {
            return $Block;
        }

        // 1. Navigate into Parsedown's nested table AST to reach the `<th>` cells inside `<thead><tr>`:
        //    `$Block['element']['text'][0]`       -> `<thead>`
        //    `$Block['element']['text'][0]['text'][0]` -> first `<tr>` inside `<thead>`
        //    `...['text']`                        -> list of `<th>` header cell arrays.
        if (isset($Block['element']['text'][0]['text'][0]['text'])) {
            // Notice the `&` symbol (`&$Block[...]` and `&$th`):
            // Unlike JavaScript (where objects/arrays are always passed by reference),
            // PHP copies arrays by value unless you use `&` ("reference"). Using `&` lets
            // us mutate the original `$Block` tree directly in place!
            $headers = &$Block['element']['text'][0]['text'][0]['text'];
            
            // Add `scope="col"` to every column header `<th scope="col">` for screen readers.
            foreach ($headers as &$th) {
                $th['attributes']['scope'] = 'col';
            }
            
            // 2. Convention check: If the author left the top-left corner cell blank
            //    (`|   | Col 1 | Col 2 |`), that signals the first column contains ROW labels!
            $isRowHeaderTable = empty(trim($headers[0]['text']));
            
            // 3. When row headers are active, walk every `<tr>` in `<tbody>` (`text[1]`)
            //    and upgrade its first cell (`text[0]`) from `<td>` to `<th scope="row">`.
            if ($isRowHeaderTable && isset($Block['element']['text'][1]['text'])) {
                $rows = &$Block['element']['text'][1]['text']; // The <tbody> <tr> rows
                
                foreach ($rows as &$tr) {
                    if (isset($tr['text'][0])) {
                        // Change the HTML tag name from 'td' to 'th'
                        $tr['text'][0]['name'] = 'th';
                        // Tell assistive technology this header labels the entire horizontal row
                        $tr['text'][0]['attributes']['scope'] = 'row';
                    }
                }
            }
        }

        // 4. Attach styling classes for striped rows, hover highlighting, and vertical centering.
        $Block['element']['attributes']['class'] = 'table table-striped table-hover align-middle';

        // 5. Wrap the `<table>` AST node inside `<div class="table-responsive my-4">`
        //    so narrow mobile screens gain a clean horizontal scrollbar if needed.
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

    /**
     * AST HOOK 2: GitHub-Style Task Checkboxes (`- [x]` and `- [ ]`) in Lists (`blockLi`)
     * -------------------------------------------------------------------------
     * Parsedown calls `blockLi()` whenever it parses a bulleted or numbered list item (`<li>`).
     * Standard Parsedown does not turn `- [x] Mastered WAV` into an HTML `<input type="checkbox">`.
     * Here we detect leading `[x]`, `[X]`, or `[ ]` markers and render accessible,
     * read-only checkboxes paired with a `<label for="...">`.
     */
    protected function blockLi($Line, ?array $Block = null) {
        // Let base Parsedown parse the list item first.
        $Block = parent::blockLi($Line, $Block);

        if (!$Block) {
            return $Block;
        }

        // Only inspect simple single-line list items whose `handler` is `'line'`.
        if (isset($Block['element']['handler']) && $Block['element']['handler'] === 'line') {
            $text = $Block['element']['text'];

            // Track whether this bullet starts with `[x]` (checked) or `[ ]` (unchecked).
            $isChecked = false;
            $isCheckbox = false;

            // Regex `/^\[[xX]\]\s/`:
            //   `^`      = start of string
            //   `\[`     = literal `[`
            //   `[xX]`   = lowercase `x` or uppercase `X`
            //   `\]`     = literal `]`
            //   `\s`     = trailing space character
            if (preg_match('/^\[[xX]\]\s/', $text)) {
                $isChecked = true;
                $isCheckbox = true;
                $text = preg_replace('/^\[[xX]\]\s/', '', $text); // Strip the `[x] ` prefix
            } elseif (preg_match('/^\[ \]\s/', $text)) {
                $isCheckbox = true;
                $text = preg_replace('/^\[ \]\s/', '', $text); // Strip the `[ ] ` prefix
            }

            // If we matched a task-list item, replace the plain bullet with a `<div class="form-check">`
            // containing a disabled `<input type="checkbox">` linked to a `<label>` via a unique `id`.
            if ($isCheckbox) {
                // `uniqid()` generates a unique string based on the current microsecond clock
                // so every `<label for="...">` points to its exact matching `<input id="...">`.
                $checkboxId = 'chk-' . uniqid();

                $checkboxHtml = '<input class="form-check-input me-2" type="checkbox" id="' . $checkboxId . '" disabled';
                if ($isChecked) {
                    $checkboxHtml .= ' checked';
                }
                $checkboxHtml .= ' aria-label="Read-only checkbox for DSP Metadata status">';

                // Switch Parsedown's handler from `'line'` (single string) to `'elements'` (nested child tags)
                // while keeping `'handler' => 'line'` on the inner `<label>` so bold/italic/links inside
                // the checklist item still get parsed as Markdown!
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
                                'text' => $text // Remaining Markdown text after `[x] ` was stripped
                            )
                        )
                    )
                );
                
                // Suppress the default round bullet dot (`list-unstyled`) since the checkbox replaces it.
                $Block['element']['attributes']['class'] = 'list-unstyled';
            }
        }

        return $Block;
    }

    /**
     * AST HOOK 3: In-Universe Corporate Emails & GitHub-Style Callout Alerts (`blockQuoteComplete`)
     * -------------------------------------------------------------------------
     * In Markdown, lines starting with `>` normally become a `<blockquote>`.
     * In RaggieSoft narratives and lore pages, `>` blockquotes have two special upgrades:
     *   1. Corporate Emails: If line 1 begins with `**From:**` or `**To:**`, we transform
     *      the blockquote into a styled "Secure Corporate Transmission" card!
     *   2. Callout Alerts: If line 1 begins with `[!NOTE]`, `[!TIP]`, `[!WARNING]`,
     *      `[!CAUTION]`, or `[!IMPORTANT]`, we transform it into a `<div role="alert">`
     *      banner with a matching Phosphor duotone icon.
     */
    protected function blockQuoteComplete($Block) {
        // Ensure the blockquote actually has at least one inner text line (`argument[0]`).
        if (!isset($Block['element']['handler']['argument'][0])) return $Block;
        
        $firstLine = $Block['element']['handler']['argument'][0];
        
        // 1. Check if the blockquote starts with `**From:**` or `**To:**` (an in-universe email).
        //    `strpos($str, $prefix) === 0` is PHP's classic `str.startsWith(prefix)` check
        //    (note `=== 0` rather than truthy check, because index `0` is falsy in loose `==`!).
        //
        // IMPORTANT AST NOTE: Never prepend raw `<div>` HTML strings into `$Block['element']['handler']['argument']`,
        // because Parsedown's `blockMarkup` will treat the `<div>` line and all contiguous following lines (`**From:**`,
        // `**To:**`, etc.) as raw HTML without parsing inline Markdown (`**bold**`, `*italics*`, `` `code` ``).
        // Instead, construct the `.card-header` and `.card-body` as structured Parsedown AST child `elements`.
        if (strpos($firstLine, '**From:**') === 0 || strpos($firstLine, '**To:**') === 0) {
            $innerLines = $Block['element']['handler']['argument'];
            $Block['element'] = [
                'name' => 'div',
                'attributes' => [
                    'class' => 'card bg-base-200 border border-base-300 shadow-sm my-4 w-100'
                ],
                'elements' => [
                    [
                        'name' => 'div',
                        'attributes' => [
                            'class' => 'card-header px-4 py-2 border-b border-base-300',
                            'style' => 'font-size: 0.85em; text-transform: uppercase; font-weight: bold; opacity: 0.7;'
                        ],
                        'rawHtml' => '<i class="ph-duotone ph-envelope" style="margin-right: 8px;"></i> Secure Corporate Transmission'
                    ],
                    [
                        'name' => 'div',
                        'attributes' => [
                            'class' => 'card-body p-4'
                        ],
                        // Tell Parsedown to run `linesElements` on `$innerLines` so `**From:**`,
                        // paragraphs, and `*italics*` inside the email body are fully parsed!
                        'handler' => [
                            'function' => 'linesElements',
                            'argument' => $innerLines,
                            'destination' => 'elements'
                        ]
                    ]
                ]
            ];
            return $Block;
        }
        
        // 2. Lookup table mapping GitHub-Flavored Markdown alert markers (`[!NOTE]`, etc.)
        //    to their CSS utility classes and Phosphor icon names.
        $alerts = [
            '[!NOTE]' => ['daisy_variant' => 'alert-info', 'bs_variant' => 'secondary', 'icon' => 'ph-info'],
            '[!TIP]' => ['daisy_variant' => 'alert-success', 'bs_variant' => 'success', 'icon' => 'ph-lightbulb'],
            '[!WARNING]' => ['daisy_variant' => 'alert-warning', 'bs_variant' => 'warning', 'icon' => 'ph-warning'],
            '[!CAUTION]' => ['daisy_variant' => 'alert-error', 'bs_variant' => 'danger', 'icon' => 'ph-radioactive'],
            '[!IMPORTANT]' => ['daisy_variant' => 'alert-info', 'bs_variant' => 'primary', 'icon' => 'ph-star'],
        ];

        // Loop over each supported callout marker; if `$firstLine` begins with it,
        // strip the marker text, prepend the icon HTML, and convert `<blockquote>` -> `<div role="alert">`.
        foreach ($alerts as $trigger => $config) {
            if (strpos($firstLine, $trigger) === 0) {
                // Remove the literal `[!NOTE]` token from the visible text
                $Block['element']['handler']['argument'][0] = trim(str_replace($trigger, '', $firstLine));
                // Prepend the decorative Phosphor icon
                $Block['element']['handler']['argument'][0] = '<i class="ph-duotone ' . $config['icon'] . ' me-2"></i> ' . $Block['element']['handler']['argument'][0];
                $Block['element']['name'] = 'div';
                $Block['element']['attributes'] = [
                    'class' => 'alert ' . $config['daisy_variant'] . ' alert-' . $config['bs_variant'] . ' my-4 shadow-sm',
                    'role' => 'alert'
                ];
                break;
            }
        }
        
        return $Block;
    }

    /**
     * AST HOOK 4: Markdown Image Sizing & Float Controls via URL Hashes (`inlineImage`)
     * -------------------------------------------------------------------------
     * Standard Markdown `![Alt text](image.jpg)` has no syntax for width or alignment.
     * Authors can append a URL hash fragment to control presentation without writing raw HTML:
     *   - `![Scene]({{CDN}}/path/photo.jpg#fullwidth)` -> spans 100% of the reading column (`w-100`)
     *   - `![Portrait]({{CDN}}/path/photo.jpg#inline)` -> floats right on desktop (`max-width: 350px`)
     *   - `![Default]({{CDN}}/path/photo.jpg)`         -> centered block (`max-width: 80%`)
     * We strip `#fullwidth` / `#inline` from the final `<img src="...">` after reading it.
     */
    protected function inlineImage($Excerpt) {
        $Image = parent::inlineImage($Excerpt);
        if (!$Image) return $Image;

        $src = $Image['element']['attributes']['src'];
        
        // Default styling for standard centered narrative illustrations
        $classes = 'img-fluid rounded shadow border border-secondary my-4';
        $style = 'max-width: 80%; display: block; margin: 0 auto;';
        
        if (strpos($src, '#fullwidth') !== false) {
            // Full-bleed column width illustration
            $classes = 'img-fluid rounded shadow border border-secondary my-4 w-100';
            $style = 'display: block;';
            $Image['element']['attributes']['src'] = str_replace('#fullwidth', '', $src);
        } elseif (strpos($src, '#inline') !== false) {
            // Right-floated portrait/inset image (`float-md-end` floats right on >=768px screens)
            $classes = 'img-fluid rounded shadow ms-4 mb-3 float-md-end';
            $style = 'max-width: 350px;';
            $Image['element']['attributes']['src'] = str_replace('#inline', '', $src);
        }
        
        $Image['element']['attributes']['class'] = $classes;
        if ($style) {
            $Image['element']['attributes']['style'] = $style;
        }

        // Return the enriched `<img>` AST element to Parsedown
        return $Image;
    }

    /**
     * MASTER ENTRY POINT & PRE-PROCESSOR (`text`)
     * -------------------------------------------------------------------------
     * Whenever `oliver.php` (or any CMS page) calls `$parsedown->text($markdown)`,
     * execution starts right here BEFORE standard `Parsedown` sees the text!
     *
     * Why pre-process here?
     *   Custom inline/block tags like `[credential ...]`, `<sms>...</sms>`,
     *   `<aac>...</aac>`, `<led>...</led>`, and multi-line `ZOË.Systems.Logs.*`
     *   blocks aren't standard HTML5 tags—if we let Parsedown run first, it might
     *   misinterpret them or escape parts of them. Transforming them into clean,
     *   accessible HTML5 elements first lets `parent::text($text)` handle the
     *   surrounding Markdown paragraphs seamlessly.
     */
    public function text($text) {
        // 1. Strip hidden authorial `[Note to the AI: ...]` annotations (escaped or unescaped)
        //    so internal continuity reminders never appear to human readers.
        //    Regex flags: `i` = case-insensitive, `s` = dot `.` matches across newlines.
        $text = preg_replace('/\\\\?\[Note to the AI:.*?\\\\?\]/is', '', $text);
        
        // 2. Expand legacy `{{cms}}` placeholders into the live DigitalOcean Spaces CDN origin.
        $text = str_replace('{{cms}}', 'https://assets.raggiesoft.com', $text);
        
        // 3. Pre-process `[credential title="..." desc="..." lore="..." color="..." icon="..."]` shortcodes.
        //    `preg_replace_callback` works just like JavaScript's `str.replace(/regex/g, (match, group1) => ...)`:
        //    for each shortcode found, it extracts `key="value"` attributes and emits a trigger `<button>`
        //    paired with a native HTML5 `<dialog>` modal opened via `.showModal()`.
        $text = preg_replace_callback('/\[credential\s+(.+?)\]/', function($matches) {
            $attributesString = $matches[1];
            
            // Extract every `key="value"` attribute pair into a `$attrs` associative array
            $attrs = [];
            preg_match_all('/([a-zA-Z]+)="([^"]*)"/', $attributesString, $attrMatches, PREG_SET_ORDER);
            foreach ($attrMatches as $match) {
                $attrs[$match[1]] = $match[2];
            }
            
            // Read attributes with safe defaults using PHP's `??` null-coalescing operator
            // (identical to JavaScript's `attrs.title ?? 'Credential'`).
            $title = $attrs['title'] ?? 'Credential';
            $desc = $attrs['desc'] ?? '';
            $lore = $attrs['lore'] ?? '';
            $color = $attrs['color'] ?? 'brand';
            $daisyMap = ['primary' => 'btn-primary', 'brand' => 'btn-primary', 'info' => 'btn-info', 'secondary' => 'btn-neutral', 'neutral' => 'btn-neutral', 'success' => 'btn-success', 'warning' => 'btn-warning', 'danger' => 'btn-error'];
            $daisyBtn = $daisyMap[$color] ?? 'btn-primary';
            $icon = $attrs['icon'] ?? 'ph-identification-card';
            
            // Unique DOM ID linking the `<button onclick="...showModal()">` to its `<dialog id="...">`.
            // Note: `<form method="dialog">` inside a native `<dialog>` automatically closes the modal
            // on button click with zero custom JavaScript needed! `htmlspecialchars()` escapes `<`, `>`, `&`, `"`
            // to prevent Cross-Site Scripting (XSS) or broken HTML attributes.
            $id = 'cred-' . uniqid();
            
            return '<button type="button" class="btn btn-outline ' . $daisyBtn . ' btn-sm me-2 mb-2" onclick="document.getElementById(\'' . $id . '\').showModal()">
<i class="ph-duotone ' . $icon . ' me-1"></i> ' . htmlspecialchars($title) . '
</button>
<dialog id="' . $id . '" class="modal">
  <div class="modal-box">
    <h3 class="font-bold text-lg mb-3"><i class="ph-duotone ' . $icon . ' me-2"></i>' . htmlspecialchars($title) . '</h3>
    <div class="mb-4">
      <h6 class="text-uppercase small opacity-75 mb-2">Technical Definition</h6>
      <p class="mb-0 opacity-80">' . htmlspecialchars($desc) . '</p>
    </div>
    <div role="alert" class="alert alert-info shadow-sm">
      <i class="ph-duotone ph-shield-check"></i>
      <div>
        <strong class="d-block mb-1">Application to the Universe</strong>
        <div class="small">' . htmlspecialchars($lore) . '</div>
      </div>
    </div>
    <div class="modal-action">
      <form method="dialog"><button class="btn">Close</button></form>
    </div>
  </div>
  <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>';
        }, $text);

        // 4. Foreign-language dialogue spans: `<lang="fr" trans="Good evening">Bonsoir</lang>`
        //    Adds the standard HTML `lang="..."` attribute (so screen readers switch pronunciation
        //    voices accurately!) plus `data-original` and `data-translation` attributes so
        //    `reader.js` can let readers toggle or inspect the English translation.
        $text = preg_replace_callback('/<lang="([^"]+)" trans="([^"]+)">(.*?)<\/lang>/is', function($matches) {
            $langCode = htmlspecialchars($matches[1]);
            $translated = htmlspecialchars($matches[2]);
            $original = $matches[3];
            
            return '<span class="foreign-language" lang="' . $langCode . '" data-original="' . htmlspecialchars($original) . '" data-translation="' . $translated . '">' . $original . '</span>';
        }, $text);
        
        // 5. Multimodal & Accessible Dialogue Semantic Tags (`<aac>`, `<asl>`, `<sgn>`, `<sms>`, `<pa>`):
        //    Sighted readers see distinct typography/colors (styled in `03-typography.css`),
        //    while screen-reader users hear a `.visually-hidden` prefix (e.g., "Text Message: ")
        //    so non-spoken communication channels are never ambiguous!
        //    In `preg_replace`, `$1` inserts whatever text was captured inside `(.*?)`.
        $text = preg_replace('/<aac>(.*?)<\/aac>/is', '<span class="visually-hidden">AAC Device: </span><em class="aac-device">$1</em>', $text);
        
        $text = preg_replace('/<asl>(.*?)<\/asl>/is', '<span class="visually-hidden">American Sign Language: </span><em class="asl-sign">$1</em>', $text);
        $text = preg_replace('/<sgn>(.*?)<\/sgn>/is', '<span class="visually-hidden">Signed: </span><em class="generic-sign">$1</em>', $text);
        $text = preg_replace('/<sms>(.*?)<\/sms>/is', '<span class="visually-hidden">Text Message: </span><em class="sms-message">$1</em>', $text);
        $text = preg_replace('/<pa>(.*?)<\/pa>/is', '<span class="visually-hidden">Public Address System: </span><em class="pa-announcement">$1</em>', $text);

        // 6. Transit Destination LED Matrix Signs (`<led>...</led>` & `<led route="..." .../>`):
        //    Supports single-line headers (`<led>112 NNTC</led>`), two-line stacked headers
        //    (`<led>OUT OF SERVICE | GARAGE</led>`), and full-height route numbers paired with
        //    stacked destination lines (`<led>913 | VA BEACH | SHORE & PLEAS HSE</led>` or
        //    `<led route="913">VA BEACH | SHORE & PLEAS HSE</led>`).
        $text = preg_replace_callback(
            '/<led\b([^>]*?)(?:\/>|>(.*?)<\/led>)/isu',
            function ($matches) {
                return $this->renderLedSignSpan($matches[1] ?? '', $matches[2] ?? '');
            },
            $text
        );

        // =====================================================================
        // 7. ZOË WREN BROOKS COGNITIVE KERNEL LOG PARSER & SYNTAX HIGHLIGHTER
        // =====================================================================
        // Narrative Context:
        // Zoë is autistic and processes her internal thoughts, sensory telemetry,
        // social scripts, and thread priorities like a C#/.NET application writing
        // to structured system logs (`ZOË.Systems.Logs.KernelLog`, `EmotionalStateLog`,
        // `SystemSchedulerLog`, `ActionLog`, `InternalStateLog`).
        //
        // Supported Authoring Formats in Markdown:
        //   Pass 7A. Fenced code blocks: ```zoe-log ... ``` or ```kernellog ... ``` (or bare ``` starting with ZOË.Systems.Logs.)
        //   Pass 7B. Explicit semantic tags: <zoe-log>...</zoe-log> or <kernellog>...</kernellog>
        //   Pass 7C. Automatic zero-config detection: Consecutive lines/paragraphs starting
        //            with `ZOË.Systems.Logs.` or `ZOE.Systems.Logs.` (including lines wrapped
        //            in Markdown italics `*...*` or `_..._` from Word/.docx migrations).
        // Note: Surrounding the returned `<div class="zoe-kernel-log">` with `"\n\n"`
        // ensures Parsedown recognizes it as a standalone block-level HTML element
        // rather than wrapping it inside a `<p>` paragraph tag.
        // =====================================================================

        // Pass 7A. Fenced code blocks: ```zoe-log, ```kernellog, or bare ``` blocks starting with ZOË.Systems.Logs.
        $text = preg_replace_callback(
            '/```(?:(?:zoe-log|kernellog)[ \t]*\R|[A-Za-z0-9_-]*[ \t]*\R(?=[ \t]*[*_]?[ \t]*(?:ZOË|ZOE)\.Systems\.Logs\.))([\s\S]*?)\R```/iu',
            function($matches) {
                return "\n\n" . $this->renderZoeKernelLogBlock($matches[1]) . "\n\n";
            },
            $text
        );

        // Pass 7B. Explicit custom tags: <zoe-log>...</zoe-log> or <kernellog>...</kernellog>
        $text = preg_replace_callback('/<(?:zoe-log|kernellog)>(.*?)<\/(?:zoe-log|kernellog)>/isu', function($matches) {
            return "\n\n" . $this->renderZoeKernelLogBlock($matches[1]) . "\n\n";
        }, $text);

        // Pass 7C. Automatic zero-config detection of standalone or consecutive `ZOË.Systems.Logs.*` paragraphs
        //          (including multi-line blocks where a `ZOË.Systems.Logs.<Stream>:` line is followed by
        //          `[Thread ...]` lines and/or a trailing `MM-DD-YYYY HH:MM:SS` timestamp line).
        //          Flags: `i` = case-insensitive, `m` = `^` matches start of any line, `u` = UTF-8 Unicode (for `Ë`).
        $text = preg_replace_callback(
            '/(?:^[ \t]*[*_]?[ \t]*(?:ZOË|ZOE)\.Systems\.Logs\.[A-Za-z0-9_]+[^\r\n]*[*_]?[ \t]*(?:\R[ \t]*[*_]?[ \t]*(?:\[Thread\b[^\r\n]*|\d{2}-\d{2}-\d{4}\s+\d{2}:\d{2}:\d{2}\.?)[*_]?[ \t]*)*(?:\R[ \t]*)*)+/imu',
            function($matches) {
                return "\n\n" . $this->renderZoeKernelLogBlock($matches[0]) . "\n\n";
            },
            $text
        );

        // Finally, hand the pre-processed Markdown string to base `Parsedown::text()`
        // to parse standard headings, paragraphs, emphasis, links, tables, and blockquotes!
        return parent::text($text);
    }

    /**
     * AST HOOK 5: Fenced Code Block Safety Net (`blockFencedCodeComplete`)
     * -------------------------------------------------------------------------
     * If a fenced code block (e.g. `~~~zoe-log` or an unusual newline arrangement)
     * bypassed the regex in `text()` and reached Parsedown's AST as `<pre><code>`,
     * inspect its language class and text content; if it's a Zoë cognitive log,
     * swap the `<pre><code>` AST node for our custom `.zoe-kernel-log` widget!
     */
    protected function blockFencedCodeComplete($Block) {
        $Block = parent::blockFencedCodeComplete($Block);
        if (!isset($Block['element']['element']['text'])) {
            return $Block;
        }

        $codeText = trim($Block['element']['element']['text']);
        $langClass = $Block['element']['element']['attributes']['class'] ?? '';

        if (
            in_array($langClass, ['language-zoe-log', 'language-kernellog'], true) ||
            preg_match('/^[*_]?[ \t]*(?:ZOË|ZOE)\.Systems\.Logs\./iu', $codeText)
        ) {
            $Block['element'] = [
                'rawHtml' => $this->renderZoeKernelLogBlock($codeText),
            ];
        }

        return $Block;
    }

    /**
     * Renders a block of one or more Zoë Wren Brooks cognitive kernel log entries into
     * an accessible, WCAG 2.1 AAA syntax-highlighted diagnostic console component.
     *
     * Supports both single-line canonical entries (`ZOË.Systems.Logs.KernelLog: ... 10-12-2015 07:05:15`)
     * and multi-line grouped entries where a standalone `ZOË.Systems.Logs.<Stream>:` header line
     * precedes one or more `[Thread ...]` lines and a trailing `MM-DD-YYYY HH:MM:SS` timestamp line.
     *
     * Notice how the window chrome adapts to the reader's OS via CSS (`03-typography.css`):
     *   - macOS sees red/amber/green traffic-light dots (`.zoe-kernel-log-dots`)
     *   - Windows sees `>_` + Fluent minimize/maximize/close buttons (`.zoe-kernel-log-win-controls`)
     *   - Linux, iOS, and Android each get native terminal headers—all marked `aria-hidden="true"`
     *     so screen readers skip the decorative chrome and read `<div role="region" aria-label="Zoë's Internal System Log">`.
     *
     * @param string $rawBlock Raw multi-line string containing one or more log entries.
     * @return string Complete HTML markup for the `.zoe-kernel-log` region.
     */
    protected function renderZoeKernelLogBlock(string $rawBlock): string {
        // Split on one or more Unicode line breaks (`\r\n` or `\n`)
        $lines = preg_split('/\R+/u', trim($rawBlock));
        $normalizedLines = [];
        $activeStreamPrefix = 'ZOË.Systems.Logs.KernelLog:';

        foreach ($lines as $rawLine) {
            $trimmed = trim($rawLine);
            if ($trimmed === '') {
                continue;
            }
            // Strip optional outer Markdown emphasis markers (`*...*` or `_..._`) left over from Word imports,
            // taking care NOT to strip bold `**...**` or `__...__`.
            if (
                (str_starts_with($trimmed, '*') && str_ends_with($trimmed, '*') && !str_starts_with($trimmed, '**')) ||
                (str_starts_with($trimmed, '_') && str_ends_with($trimmed, '_') && !str_starts_with($trimmed, '__'))
            ) {
                $trimmed = trim(substr($trimmed, 1, -1));
            }
            // Strip optional leading blockquote `>` if copied from a quoted block
            $trimmed = ltrim($trimmed, "> \t");
            if ($trimmed === '') {
                continue;
            }

            // Case A: Standalone stream header line (e.g. `ZOË.Systems.Logs.SystemSchedulerLog:`)
            //         Updates `$activeStreamPrefix` for subsequent continuation lines without adding an empty row.
            if (preg_match('/^((?:ZOË|ZOE)\.Systems\.Logs\.[A-Za-z0-9_]+)[:,]?$/iu', $trimmed, $hdrMatch)) {
                $activeStreamPrefix = $hdrMatch[1] . ':';
                continue;
            }

            // Case B: Standalone timestamp line (`MM-DD-YYYY HH:MM:SS`) -> backfill onto any preceding
            //         normalized lines in this group that did not yet have a timestamp!
            if (preg_match('/^(\d{2}-\d{2}-\d{4}\s+\d{2}:\d{2}:\d{2})\.?$/u', $trimmed, $tsOnlyMatch)) {
                $tsValue = $tsOnlyMatch[1];
                for ($i = count($normalizedLines) - 1; $i >= 0; $i--) {
                    if (!preg_match('/\d{2}-\d{2}-\d{4}\s+\d{2}:\d{2}:\d{2}\.?$/u', $normalizedLines[$i])) {
                        $normalizedLines[$i] .= ' ' . $tsValue;
                    } else {
                        break;
                    }
                }
                continue;
            }

            // Case C: Complete log line starting with `ZOË.Systems.Logs.<Stream>`
            if (preg_match('/^((?:ZOË|ZOE)\.Systems\.Logs\.[A-Za-z0-9_]+)([:,]?)/iu', $trimmed, $fullMatch)) {
                $activeStreamPrefix = $fullMatch[1] . ($fullMatch[2] !== '' ? $fullMatch[2] : ':');
                $normalizedLines[] = $trimmed;
                continue;
            }

            // Case D: Continuation/thread line inside a multi-line log block -> inherit `$activeStreamPrefix`
            $normalizedLines[] = $activeStreamPrefix . ' ' . $trimmed;
        }

        // Tokenize and syntax-highlight each normalized line (`01`, `02`, ...)
        $entriesHtml = [];
        $entryCount = 0;
        foreach ($normalizedLines as $normLine) {
            $entryCount++;
            $entriesHtml[] = $this->highlightZoeLogLine($normLine, $entryCount);
        }

        if (empty($entriesHtml)) {
            return '';
        }

        $countLabel = $entryCount === 1 ? '1 ENTRY' : ($entryCount . ' ENTRIES');

        // Assemble the complete diagnostic window widget (`role="region"` for screen-reader landmark navigation)
        return '<div class="zoe-kernel-log my-4" role="region" aria-label="Zoë&#039;s Internal System Log">'
            . '<span class="visually-hidden">Zoë&#039;s Internal System Log: </span>'
            . '<div class="zoe-kernel-log-header" aria-hidden="true">'
            . '<div class="zoe-kernel-log-dots">'
            . '<span class="zkl-dot zkl-dot-red"></span>'
            . '<span class="zkl-dot zkl-dot-amber"></span>'
            . '<span class="zkl-dot zkl-dot-green"></span>'
            . '</div>'
            . '<span class="zoe-kernel-log-os-badge"></span>'
            . '<div class="zoe-kernel-log-title">ZOË.Systems.Logs <span class="zkl-header-sep">//</span> Cognitive Runtime Telemetry</div>'
            . '<div class="zoe-kernel-log-meta">' . htmlspecialchars($countLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</div>'
            . '<div class="zoe-kernel-log-win-controls">'
            . '<span class="zkl-win-btn zkl-win-min">&#x2500;</span>'
            . '<span class="zkl-win-btn zkl-win-max">&#x25A1;</span>'
            . '<span class="zkl-win-btn zkl-win-close">&#x2715;</span>'
            . '</div>'
            . '</div>'
            . '<div class="zoe-kernel-log-body">'
            . implode('', $entriesHtml)
            . '</div>'
            . '</div>';
    }

    /**
     * Tokenizes and syntax-highlights a single line from Zoë's cognitive kernel log.
     *
     * WHY A SINGLE-PASS LEXER? (Important CS / JS Concept!)
     *   If you tried to syntax-highlight text by running 12 separate `.replace()` calls
     *   one after another, later regexes would accidentally match words (`class`, `span`,
     *   `=`, `"..."`) INSIDE the `<span class="...">` HTML tags inserted by earlier regexes!
     *   Instead, we combine all 12 token types into ONE master regex using named capture
     *   groups (`(?P<severity>...)`, `(?P<exception>...)`, etc.) and scan left-to-right
     *   with `PREG_OFFSET_CAPTURE`—escaping each chunk with `htmlspecialchars()` exactly once.
     *
     * @param string $rawLine Single log line (e.g. `ZOË.Systems.Logs.KernelLog: ... 10-12-2015 07:05:15`).
     * @param int    $lineNum 1-based line number within the current log block.
     * @return string HTML markup for a single `.zoe-log-entry` row.
     */
    protected function highlightZoeLogLine(string $rawLine, int $lineNum = 1): string {
        // Normalize any pre-escaped HTML entities (like `&amp;` or `&quot;`) back to raw UTF-8
        // before lexing so we never double-escape `&amp;` into `&amp;amp;`.
        $line = html_entity_decode($rawLine, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $nsPrefixHtml = '';
        $streamName = 'KernelLog';
        $rest = $line;

        // 1. Peel off the leading `ZOË.Systems.Logs.<StreamName>:` namespace prefix if present
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

        // 2. Peel off the trailing timestamp (`MM-DD-YYYY HH:MM:SS`) so we can render it in a `<time>` badge
        $timestampHtml = '';
        if (preg_match('/^(.*?)\s+(\d{2}-\d{2}-\d{4}\s+\d{2}:\d{2}:\d{2})\.?$/us', $rest, $tsMatch)) {
            $rest = $tsMatch[1];
            $tsEsc = htmlspecialchars($tsMatch[2], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $timestampHtml = '<time class="zkl-timestamp">' . $tsEsc . '</time>';
        }

        // 3. Classify the entry's semantic severity/stream level (`info`, `critical`, `warning`,
        //    `emotional`, `action`, `scheduler`) to color the left-hand border bar of the log row.
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

        // 4. Single-pass token lexer over `$rest` using named regex capture groups (`(?P<name>...)`)
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
        $lastOffset = 0; // Byte offset cursor tracking how far through `$rest` we have already copied
        $length = strlen($rest);

        // `PREG_OFFSET_CAPTURE` returns `[matchedText, byteStartIndex]` for every match
        // so we can copy any unhighlighted plain text between tokens without losing a single character!
        if (preg_match_all($tokenPattern, $rest, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($matches as $match) {
                $fullMatchText = $match[0][0];
                $matchOffset = $match[0][1];

                // Append any plain text sitting between the previous token and this token
                if ($matchOffset > $lastOffset) {
                    $plainChunk = substr($rest, $lastOffset, $matchOffset - $lastOffset);
                    $bodyHtml .= htmlspecialchars($plainChunk, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                }

                // Check which named capture group (`severity`, `exception`, etc.) won this match
                $tokenClass = 'zkl-text';
                foreach (['severity', 'exception', 'thread', 'bracket', 'string', 'label', 'diagnostic', 'function', 'identifier', 'constant', 'number', 'operator'] as $groupName) {
                    if (isset($match[$groupName]) && $match[$groupName][1] !== -1 && $match[$groupName][0] !== '') {
                        $tokenClass = 'zkl-' . $groupName;
                        break;
                    }
                }

                // Wrap the escaped token in `<span class="zkl-<group>">` so `03-typography.css` colors it
                $escapedToken = htmlspecialchars($fullMatchText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $bodyHtml .= '<span class="' . $tokenClass . '">' . $escapedToken . '</span>';

                $lastOffset = $matchOffset + strlen($fullMatchText);
            }
        }

        // Append any trailing plain text after the final matched token
        if ($lastOffset < $length) {
            $bodyHtml .= htmlspecialchars(substr($rest, $lastOffset), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        // Emit the complete row: 2-digit zero-padded line number gutter (`01`, `02`), main highlighted body, and timestamp
        return '<div class="zoe-log-entry zoe-log-level-' . $rowLevel . '" data-line="' . $lineNum . '">'
            . '<span class="zoe-log-gutter" aria-hidden="true">' . str_pad((string)$lineNum, 2, '0', STR_PAD_LEFT) . '</span>'
            . '<div class="zoe-log-main">' . $nsPrefixHtml . $bodyHtml . '</div>'
            . $timestampHtml
            . '</div>';
    }

    /**
     * Parses and renders an in-universe Transit Amber LED Destination Sign (`<led>...</led>`).
     *
     * Supported Authoring Forms:
     * 1. Single-Line Header:
     *      `<led>112 NNTC</led>`
     *      `<led>MAROON EXPRESS</led>`
     * 2. Two-Line Stacked Matrix (No Route Column):
     *      `<led>OUT OF SERVICE | GARAGE</led>`
     *      `<led>RADFORD CONNECTOR RELIEF<br>TO CPI via PRICES FORK</led>`
     * 3. Full-Height Route Number + Stacked Destination Matrix (24x200 Split Header):
     *      `<led>913 | VA BEACH | SHORE & PLEAS HSE</led>`
     *      `<led route="913">VA BEACH | SHORE & PLEAS HSE</led>`
     *      `<led route="913" line1="VA BEACH" line2="SHORE & PLEAS HSE"></led>`
     *      `<led route="112">NNTC</led>`
     *
     * Accessibility & Find-in-Page Fidelity:
     *   Uses inline `<span>` elements (preserving Parsedown inline paragraph context,
     *   browser Find-in-Page, and copy-paste) paired with `.visually-hidden` separators
     *   so screen readers announce `"LED Sign: Route 913 — VA BEACH / SHORE & PLEAS HSE"`.
     */
    protected function renderLedSignSpan(string $attrString, string $rawInner): string {
        // 1. Parse optional HTML-like attributes (`route="913"`, `line1="..."`, `line2="..."`)
        $attrs = [];
        if ($attrString !== '' && preg_match_all('/([a-zA-Z0-9_-]+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/u', $attrString, $attrMatches, PREG_SET_ORDER)) {
            foreach ($attrMatches as $m) {
                $key = strtolower(trim($m[1]));
                $val = $m[2] !== '' ? $m[2] : ($m[3] ?? '');
                $attrs[$key] = trim(html_entity_decode($val, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            }
        }

        $route = $attrs['route'] ?? '';
        $lines = [];

        // Check if explicit `line1` / `line2` attributes were passed on `<led ...>`
        if (isset($attrs['line1']) || isset($attrs['line2'])) {
            if (($attrs['line1'] ?? '') !== '') {
                $lines[] = $attrs['line1'];
            }
            if (($attrs['line2'] ?? '') !== '') {
                $lines[] = $attrs['line2'];
            }
        }

        // Otherwise, split the inner text on `|`, `<br>`, or line breaks
        if (empty($lines)) {
            // Normalize explicit `<br>` tags or literal newlines into pipe `|` segments
            $normalized = preg_replace('/<br\s*\/?>|\R/iu', '|', $rawInner);
            $decoded = html_entity_decode((string)$normalized, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $parts = array_values(array_filter(array_map('trim', explode('|', $decoded)), static function ($seg) {
                return $seg !== '';
            }));

            if ($route === '' && count($parts) >= 3) {
                // 3-part shorthand: `ROUTE | TOP LINE | BOTTOM LINE` (e.g. `913 | VA BEACH | SHORE & PLEAS HSE`)
                // `array_shift` removes and returns the first element (`913`), just like `parts.shift()` in JS.
                $route = array_shift($parts);
                $lines = [
                    $parts[0],
                    implode(' ', array_slice($parts, 1)),
                ];
            } elseif (count($parts) >= 2) {
                // 2-part shorthand: `TOP LINE | BOTTOM LINE`
                $lines = [
                    $parts[0],
                    implode(' ', array_slice($parts, 1)),
                ];
            } elseif (count($parts) === 1) {
                // Single-line header: `112 NNTC`
                $lines = [$parts[0]];
            }
        }

        // Tiny anonymous helper function (`const esc = (val) => ...` in JS) to safely HTML-escape text
        $esc = static function (string $val): string {
            return htmlspecialchars($val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        };

        if ($route === '' && empty($lines)) {
            return '';
        }

        // Case 1: Single-line LED header (no separate full-height route column)
        if ($route === '' && count($lines) === 1) {
            return '<span class="visually-hidden">LED Sign: </span>'
                . '<span class="led-sign"><span class="led-line-single">' . $esc($lines[0]) . '</span></span>';
        }

        // Case 2: Two-line stacked LED header without a left route column
        if ($route === '') {
            return '<span class="visually-hidden">LED Sign: </span>'
                . '<span class="led-sign led-sign--stacked">'
                . '<span class="led-lines">'
                . '<span class="led-line-top">' . $esc($lines[0]) . '</span>'
                . '<span class="visually-hidden"> / </span>'
                . '<span class="led-line-bottom">' . $esc($lines[1]) . '</span>'
                . '</span>'
                . '</span>';
        }

        // Case 3: Full-height route number on the left + 1 or 2 destination lines on the right
        $linesHtml = '';
        if (count($lines) >= 2) {
            $linesHtml = '<span class="led-lines">'
                . '<span class="led-line-top">' . $esc($lines[0]) . '</span>'
                . '<span class="visually-hidden"> / </span>'
                . '<span class="led-line-bottom">' . $esc($lines[1]) . '</span>'
                . '</span>';
        } elseif (count($lines) === 1) {
            $linesHtml = '<span class="led-lines"><span class="led-line-single">' . $esc($lines[0]) . '</span></span>';
        }

        return '<span class="visually-hidden">LED Sign: Route </span>'
            . '<span class="led-sign led-sign--split">'
            . '<span class="led-route">' . $esc($route) . '</span>'
            . ($linesHtml !== '' ? '<span class="visually-hidden"> — </span>' . $linesHtml : '')
            . '</span>';
    }
}
