<?php
/**
 * Plugin Name: WC Paver Grid Personalization
 * Description: Per-character grid personalization for specific paver SKUs + optional Maltese Cross + optional graphic upload.
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) exit;

final class WC_Paver_Grid_Personalization {

    /**
     * RULES
     * - 4x8: 3 lines, 15 cols normally, 12 cols if cross OR graphic
     * - 8x8: 4 lines, 13 cols
     * - 12x12: 5 lines, 15 cols (assumption)
     * - 24x24: 6 lines, 15 cols (assumption)
     */
    private const RULES_BY_SKU = [
        '4x8-paver' => [
            'lines' => 3,
            'cols'  => 15,
            'allow_cross' => true,
            'reduce_cols_on_cross_or_graphic' => 12,
            'graphic_fee' => 0.00,
        ],
        '8x8-paver' => [
            'lines' => 4,
            'cols'  => 13,
            'allow_cross' => true,
            'graphic_fee' => 0.00,
        ],
        '12x12-paver' => [
            'lines' => 5,
            'cols'  => 15,
            'allow_cross' => true,
            'graphic_fee' => 0.00,
        ],
        '24x24-paver' => [
            'lines' => 6,
            'cols'  => 15,
            'allow_cross' => true,
            'graphic_fee' => 0.00,
        ],
    ];

    public function __construct() {
        add_action('woocommerce_before_add_to_cart_button', [$this, 'render_fields']);

        add_filter('woocommerce_add_to_cart_validation', [$this, 'validate_fields'], 10, 3);
        add_filter('woocommerce_add_cart_item_data', [$this, 'add_cart_item_data'], 10, 2);
        add_filter('woocommerce_get_item_data', [$this, 'display_cart_item_data'], 10, 2);

        add_action('woocommerce_checkout_create_order_line_item', [$this, 'add_order_item_meta'], 10, 4);

        // Optional automatic fee when graphic uploaded
        add_action('woocommerce_cart_calculate_fees', [$this, 'maybe_add_graphic_fees']);
    }

    private function get_rule_for_product(int $product_id): ?array {
        $product = wc_get_product($product_id);
        if (!$product) return null;

        $sku = (string) $product->get_sku();
        if ($sku === '') return null;

        $rule = self::RULES_BY_SKU[$sku] ?? null;
        if (!$rule) return null;

        $rule['sku'] = $sku;
        return $rule;
    }

    public function render_fields() {
        global $product;
        if (!$product) return;

        $rule = $this->get_rule_for_product((int)$product->get_id());
        if (!$rule) return;

        $lines = (int)$rule['lines'];
        $cols  = (int)$rule['cols'];
        $reduce_cols = isset($rule['reduce_cols_on_cross_or_graphic']) ? (int)$rule['reduce_cols_on_cross_or_graphic'] : 0;

        // Persist posted values (for validation errors)
        $posted_cross = !empty($_POST['paver_cross']) ? 1 : 0;

        echo '<div class="paver-personalization-wrap" style="margin:16px 0; padding:14px; border:1px solid #ddd; border-radius:6px;">';
        echo '<h3 style="margin:0 0 8px;">Personalization</h3>';
        echo '<p style="margin:0 0 12px;">Type directly into the grid. All caps. Spaces and punctuation count.</p>';

        if (!empty($rule['allow_cross'])) {
            echo '<p style="margin:0 0 10px;">';
            echo '<label><input type="checkbox" id="paver_cross" name="paver_cross" value="1" '.checked(1, $posted_cross, false).' /> Include Maltese Cross</label>';
            echo '</p>';
        }

        echo '<p style="margin:0 0 10px;">';
        echo '<label style="display:block; font-weight:600; margin-bottom:4px;" for="paver_graphic">Optional graphic upload</label>';
        echo '<input type="file" id="paver_graphic" name="paver_graphic" accept=".png,.jpg,.jpeg,.pdf" />';
        echo '<small style="display:block; color:#555; margin-top:4px;">Allowed: PNG, JPG, PDF.</small>';
        echo '</p>';

        // Grid container + data attributes for JS
        echo '<div
                id="paver_grid"
                class="paver-grid"
                data-lines="'.esc_attr($lines).'"
                data-cols="'.esc_attr($cols).'"
                data-reduce-cols="'.esc_attr($reduce_cols).'"
              ></div>';

        // Hidden fields that WooCommerce will submit/store
        for ($i = 1; $i <= $lines; $i++) {
            $key = "paver_line_{$i}";
            $val = isset($_POST[$key]) ? sanitize_text_field(wp_unslash($_POST[$key])) : '';
            echo '<input type="hidden" name="'.esc_attr($key).'" id="'.esc_attr($key).'" value="'.esc_attr($val).'" />';
        }

        echo '</div>';

        // Inline CSS + JS (fine for a small one-off plugin)
        $this->inline_assets();
    }

    private function inline_assets(): void {
        ?>
        <style>
            .paver-grid {
                display: grid;
                gap: 6px;
                max-width: 100%;
                overflow-x: auto;
                padding: 10px;
                border: 1px solid #e5e5e5;
                border-radius: 6px;
                background: #fafafa;
            }
            .paver-row {
                display: grid;
                gap: 6px;
            }
            .paver-cell {
                width: 2.0em;
                height: 2.2em;
                text-align: center;
                text-transform: uppercase;
                font-weight: 700;
                border: 1px solid #cfcfcf;
                border-radius: 4px;
                background: #fff;
                padding: 0;
            }
            .paver-cell:focus {
                outline: 2px solid #2271b1;
                outline-offset: 1px;
            }
            .paver-help {
                margin-top: 8px;
                font-size: 12px;
                color: #555;
            }

            /* Mobile: slightly bigger tap targets */
            @media (max-width: 600px) {
                .paver-cell { width: 2.2em; height: 2.4em; }
            }
        </style>

        <script>
        (function() {
            const gridEl = document.getElementById('paver_grid');
            if (!gridEl) return;

            const lines = parseInt(gridEl.dataset.lines || '0', 10);
            const baseCols = parseInt(gridEl.dataset.cols || '0', 10);
            const reduceCols = parseInt(gridEl.dataset.reduceCols || '0', 10);

            const crossEl = document.getElementById('paver_cross');
            const fileEl  = document.getElementById('paver_graphic');

            // Allow: A–Z, 0–9, space, and common punctuation.
            // You said “don’t omit special characters” — so we allow most printable ASCII punctuation except angle brackets.
            const isAllowedChar = (ch) => {
                if (ch === ' ') return true;
                const code = ch.charCodeAt(0);
                // A-Z 0-9
                if (code >= 48 && code <= 57) return true;
                if (code >= 65 && code <= 90) return true;

                // Common punctuation (include a wide set; exclude < and >)
                const allowedPunct = ".,-_'\"/\\&():;!?+#@*$%=";
                return allowedPunct.indexOf(ch) !== -1;
            };

            const getEffectiveCols = () => {
                // Only 4x8 has reduceCols configured; other products have 0
                if (!reduceCols) return baseCols;

                const cross = crossEl ? !!crossEl.checked : false;
                const hasFile = fileEl ? (fileEl.files && fileEl.files.length > 0) : false;

                return (cross || hasFile) ? reduceCols : baseCols;
            };

            // Build / rebuild grid
            let cells = []; // [line][col] => input
            const buildGrid = () => {
                const cols = getEffectiveCols();

                // Preserve current values from hidden fields before rebuild
                const prevLines = [];
                for (let i=1; i<=lines; i++) {
                    const hidden = document.getElementById('paver_line_' + i);
                    prevLines.push(hidden ? (hidden.value || '') : '');
                }

                gridEl.innerHTML = '';
                cells = [];

                // Grid rows
                for (let r = 0; r < lines; r++) {
                    const row = document.createElement('div');
                    row.className = 'paver-row';
                    row.style.gridTemplateColumns = `repeat(${cols}, 2.0em)`;

                    cells[r] = [];

                    for (let c = 0; c < cols; c++) {
                        const input = document.createElement('input');
                        input.type = 'text';
                        input.className = 'paver-cell';
                        input.maxLength = 1;
                        input.autocomplete = 'off';
                        input.inputMode = 'text';
                        input.spellcheck = false;
                        input.dataset.r = String(r);
                        input.dataset.c = String(c);

                        // Fill from previous hidden values if present
                        const existing = (prevLines[r] || '');
                        const ch = existing[c] || '';
                        input.value = ch ? ch.toUpperCase() : '';

                        input.addEventListener('beforeinput', (e) => {
                            // Block multi-char insert here; paste handled separately
                            if (e.inputType === 'insertText' && e.data && e.data.length > 1) {
                                e.preventDefault();
                            }
                        });

                        input.addEventListener('input', (e) => {
                            let v = input.value || '';
                            if (!v) {
                                syncHidden();
                                return;
                            }
                            v = v.toUpperCase();

                            // If user types disallowed, clear it
                            if (!isAllowedChar(v)) {
                                input.value = '';
                                syncHidden();
                                return;
                            }

                            input.value = v;
                            focusNext(r, c);
                            syncHidden();
                        });

                        input.addEventListener('keydown', (e) => {
                            const key = e.key;

                            if (key === 'Backspace') {
                                if (input.value) {
                                    input.value = '';
                                    syncHidden();
                                    return;
                                }
                                e.preventDefault();
                                focusPrev(r, c);
                                return;
                            }

                            if (key === 'ArrowLeft') { e.preventDefault(); focusPrev(r, c); return; }
                            if (key === 'ArrowRight') { e.preventDefault(); focusNext(r, c); return; }
                            if (key === 'ArrowUp') { e.preventDefault(); focusUp(r, c); return; }
                            if (key === 'ArrowDown') { e.preventDefault(); focusDown(r, c); return; }

                            if (key === 'Enter') { e.preventDefault(); focusDown(r, c); return; }
                        });

                        input.addEventListener('paste', (e) => {
                            e.preventDefault();
                            const text = (e.clipboardData || window.clipboardData).getData('text') || '';
                            pasteFrom(r, c, text);
                            syncHidden();
                        });

                        cells[r][c] = input;
                        row.appendChild(input);
                    }

                    gridEl.appendChild(row);
                }

                // Help text
                const help = document.createElement('div');
                help.className = 'paver-help';
                help.textContent = `Grid: ${lines} line(s) × ${cols} character box(es) per line.`;
                gridEl.appendChild(help);

                syncHidden();
            };

            const focusCell = (r, c) => {
                if (!cells[r] || !cells[r][c]) return;
                cells[r][c].focus();
                cells[r][c].select();
            };

            const focusNext = (r, c) => {
                if (cells[r] && cells[r][c+1]) return focusCell(r, c+1);
                if (cells[r+1] && cells[r+1][0]) return focusCell(r+1, 0);
            };

            const focusPrev = (r, c) => {
                if (cells[r] && cells[r][c-1]) return focusCell(r, c-1);
                if (cells[r-1]) {
                    const last = cells[r-1].length - 1;
                    return focusCell(r-1, last);
                }
            };

            const focusUp = (r, c) => {
                if (cells[r-1] && cells[r-1][Math.min(c, cells[r-1].length-1)]) {
                    return focusCell(r-1, Math.min(c, cells[r-1].length-1));
                }
            };

            const focusDown = (r, c) => {
                if (cells[r+1] && cells[r+1][Math.min(c, cells[r+1].length-1)]) {
                    return focusCell(r+1, Math.min(c, cells[r+1].length-1));
                }
            };

            const pasteFrom = (r, c, text) => {
                let rr = r, cc = c;
                const cols = cells[0] ? cells[0].length : baseCols;

                for (let i=0; i<text.length; i++) {
                    let ch = text[i];

                    // Normalize newlines/tabs to space or row change
                    if (ch === '\n' || ch === '\r') {
                        rr += 1; cc = 0;
                        if (rr >= lines) break;
                        continue;
                    }
                    if (ch === '\t') ch = ' ';

                    ch = ch.toUpperCase();

                    if (!isAllowedChar(ch)) continue;

                    if (!cells[rr] || !cells[rr][cc]) {
                        // Try to move to next row if current column exceeded
                        rr += 1; cc = 0;
                        if (rr >= lines) break;
                    }
                    if (!cells[rr] || !cells[rr][cc]) break;

                    cells[rr][cc].value = ch;
                    cc += 1;

                    if (cc >= cols) {
                        rr += 1; cc = 0;
                        if (rr >= lines) break;
                    }
                }

                // Focus next available
                focusCell(Math.min(rr, lines-1), Math.min(cc, (cells[Math.min(rr, lines-1)]?.length || 1)-1));
            };

            const syncHidden = () => {
                // Convert each row to a string; trim trailing spaces
                for (let r=0; r<lines; r++) {
                    let s = '';
                    if (cells[r]) {
                        for (let c=0; c<cells[r].length; c++) {
                            s += (cells[r][c].value || ' ');
                        }
                    }
                    s = s.replace(/\s+$/g, ''); // trim trailing spaces
                    const hidden = document.getElementById('paver_line_' + (r+1));
                    if (hidden) hidden.value = s;
                }
            };

            // Rebuild when cross or file changes (affects 4x8 cols)
            if (crossEl) crossEl.addEventListener('change', buildGrid);
            if (fileEl)  fileEl.addEventListener('change', buildGrid);

            // Initial build
            buildGrid();
        })();
        </script>
        <?php
    }

    public function validate_fields($passed, $product_id, $qty) {
        $rule = $this->get_rule_for_product((int)$product_id);
        if (!$rule) return $passed;

        $lines_required = (int)$rule['lines'];
        $base_cols = (int)$rule['cols'];

        $cross_selected = !empty($_POST['paver_cross']);
        $graphic_added  = !empty($_FILES['paver_graphic']['name']);

        $cols = $base_cols;
        if (!empty($rule['reduce_cols_on_cross_or_graphic']) && ($cross_selected || $graphic_added)) {
            $cols = (int)$rule['reduce_cols_on_cross_or_graphic'];
        }

        // Allowed chars server-side (wide punctuation set; reject angle brackets)
        $allowed_re = '/^[A-Z0-9 \.\,\-_\x27\x22\/\\\\&\(\)\:\;\!\?\+\#\@\*\$\%\=]*$/';

        for ($i = 1; $i <= $lines_required; $i++) {
            $key = "paver_line_{$i}";
            $val = isset($_POST[$key]) ? (string) wp_unslash($_POST[$key]) : '';
            $val = mb_strtoupper($val);
            $val = trim($val);

            if ($val === '') {
                wc_add_notice('Please fill out all personalization lines in the grid.', 'error');
                return false;
            }

            // Length check: max columns for that product/config
            if (mb_strlen($val) > $cols) {
                wc_add_notice(sprintf('Line %d is too long. Max %d characters for this paver/configuration.', $i, $cols), 'error');
                return false;
            }

            // Character check
            if (!preg_match($allowed_re, $val)) {
                wc_add_notice('One or more characters are not allowed. Use letters, numbers, spaces, and standard punctuation.', 'error');
                return false;
            }
        }

        // Optional file validation
        if ($graphic_added) {
            $file = $_FILES['paver_graphic'];

            if (!empty($file['size']) && $file['size'] > 5 * 1024 * 1024) {
                wc_add_notice('Graphic upload is too large (max 5MB).', 'error');
                return false;
            }

            $allowed_mimes = ['image/png', 'image/jpeg', 'application/pdf'];
            $type = !empty($file['type']) ? $file['type'] : '';
            if ($type && !in_array($type, $allowed_mimes, true)) {
                wc_add_notice('Graphic must be a PNG, JPG, or PDF.', 'error');
                return false;
            }
        }

        // Force uppercase back into POST so stored data is consistent
        for ($i = 1; $i <= $lines_required; $i++) {
            $key = "paver_line_{$i}";
            if (isset($_POST[$key])) {
                $_POST[$key] = mb_strtoupper((string) $_POST[$key]);
            }
        }

        return $passed;
    }

    public function add_cart_item_data($cart_item_data, $product_id) {
        $rule = $this->get_rule_for_product((int)$product_id);
        if (!$rule) return $cart_item_data;

        $lines_required = (int)$rule['lines'];
        $cross_selected = !empty($_POST['paver_cross']);
        $graphic_added  = !empty($_FILES['paver_graphic']['name']);

        $base_cols = (int)$rule['cols'];
        $cols = $base_cols;
        if (!empty($rule['reduce_cols_on_cross_or_graphic']) && ($cross_selected || $graphic_added)) {
            $cols = (int)$rule['reduce_cols_on_cross_or_graphic'];
        }

        $lines = [];
        for ($i = 1; $i <= $lines_required; $i++) {
            $key = "paver_line_{$i}";
            $val = isset($_POST[$key]) ? sanitize_text_field(wp_unslash($_POST[$key])) : '';
            $val = trim(mb_strtoupper($val));
            $lines[] = $val;
        }

        $cart_item_data['paver_personalization'] = [
            'sku' => $rule['sku'] ?? '',
            'lines' => $lines,
            'cross' => $cross_selected ? 1 : 0,
            'cols_used' => $cols,
        ];

        // Upload
        if ($graphic_added) {
            $attachment_id = $this->handle_upload_to_media($_FILES['paver_graphic']);
            if ($attachment_id) {
                $cart_item_data['paver_personalization']['graphic_id'] = $attachment_id;
                $cart_item_data['paver_personalization']['graphic_url'] = wp_get_attachment_url($attachment_id);
            }
        }

        // Prevent merging of items with different personalization
        $cart_item_data['unique_key'] = md5(wp_json_encode($cart_item_data['paver_personalization']) . microtime(true));

        return $cart_item_data;
    }

    public function display_cart_item_data($item_data, $cart_item) {
        $p = $cart_item['paver_personalization'] ?? null;
        if (!$p) return $item_data;

        if (!empty($p['lines']) && is_array($p['lines'])) {
            $item_data[] = [
                'name'  => 'Personalization',
                'value' => implode('<br>', array_map('esc_html', $p['lines'])),
            ];
        }

        if (!empty($p['cross'])) {
            $item_data[] = ['name' => 'Maltese Cross', 'value' => 'Yes'];
        }

        if (!empty($p['graphic_url'])) {
            $item_data[] = ['name' => 'Graphic', 'value' => esc_html(basename($p['graphic_url']))];
        }

        return $item_data;
    }

    public function add_order_item_meta($item, $cart_item_key, $values, $order) {
        $p = $values['paver_personalization'] ?? null;
        if (!$p) return;

        if (!empty($p['lines'])) {
            $item->add_meta_data('Personalization Lines', implode("\n", $p['lines']), true);
        }
        if (!empty($p['cross'])) {
            $item->add_meta_data('Maltese Cross', 'Yes', true);
        }
        if (!empty($p['graphic_id'])) {
            $item->add_meta_data('Graphic Attachment ID', (int)$p['graphic_id'], true);
        }
        if (!empty($p['graphic_url'])) {
            $item->add_meta_data('Graphic URL', esc_url_raw($p['graphic_url']), true);
        }
        if (!empty($p['cols_used'])) {
            $item->add_meta_data('Char Columns Used', (int)$p['cols_used'], true);
        }
    }

    public function maybe_add_graphic_fees($cart) {
        if (is_admin() && !defined('DOING_AJAX')) return;
        if (!$cart) return;

        $total_fee = 0.0;

        foreach ($cart->get_cart() as $cart_item) {
            $p = $cart_item['paver_personalization'] ?? null;
            if (!$p) continue;

            if (empty($p['graphic_id'])) continue; // only fee if file actually uploaded

            $sku = $p['sku'] ?? '';
            $rule = $sku ? (self::RULES_BY_SKU[$sku] ?? null) : null;
            if (!$rule) continue;

            $fee = (float)($rule['graphic_fee'] ?? 0.0);
            if ($fee > 0) {
                $qty = (int)($cart_item['quantity'] ?? 1);
                $total_fee += ($fee * $qty);
            }
        }

        if ($total_fee > 0) {
            $cart->add_fee('Graphic personalization', $total_fee, false);
        }
    }

    private function handle_upload_to_media(array $file): int {
        if (!function_exists('wp_handle_upload')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        if (!function_exists('wp_insert_attachment')) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
        }

        $uploaded = wp_handle_upload($file, ['test_form' => false]);
        if (isset($uploaded['error'])) {
            wc_add_notice('Graphic upload failed: ' . esc_html($uploaded['error']), 'error');
            return 0;
        }

        $filetype = wp_check_filetype($uploaded['file'], null);

        $attachment = [
            'post_mime_type' => $filetype['type'],
            'post_title'     => sanitize_file_name(basename($uploaded['file'])),
            'post_content'   => '',
            'post_status'    => 'private',
        ];

        $attach_id = wp_insert_attachment($attachment, $uploaded['file']);
        if (!$attach_id) return 0;

        if (strpos((string)$filetype['type'], 'image/') === 0) {
            $attach_data = wp_generate_attachment_metadata($attach_id, $uploaded['file']);
            wp_update_attachment_metadata($attach_id, $attach_data);
        }

        return (int)$attach_id;
    }
}

new WC_Paver_Grid_Personalization();
