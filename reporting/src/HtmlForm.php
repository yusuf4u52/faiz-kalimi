<?php

declare(strict_types=1);

namespace JamaatReport;

/**
 * Small helpers for scraping ASP.NET WebForms pages: reading the current
 * value of every form field (needed to faithfully replay a postback) and
 * pulling out individual fields/dropdown options by id.
 */
final class HtmlForm
{
    /**
     * Extracts every input/select field's current value, keyed by its
     * `name` attribute (the ASP.NET-qualified name postbacks expect).
     *
     * @return array<string, string>
     */
    public static function extractFields(string $html): array
    {
        $fields = [];

        preg_match_all('/<input([^>]*)>/i', $html, $tagMatches);
        foreach ($tagMatches[1] as $attrs) {
            if (!preg_match('/name="([^"]+)"/', $attrs, $nm)) {
                continue;
            }
            $type = preg_match('/type="([^"]*)"/', $attrs, $tm) ? strtolower($tm[1]) : 'text';
            // A real browser only ever includes the ONE submit button that was
            // actually clicked, not every button on the page — confirmed by diffing
            // against a real captured request, which omitted all ~70 other buttons
            // this mega-page has. Including them all confuses the server. Callers
            // that need a specific submit button should add it themselves. Likewise,
            // an unchecked checkbox and an empty file input are never submitted at
            // all by a real browser (not even as empty string) — also confirmed by
            // the same diff.
            if (in_array($type, ['submit', 'button', 'reset', 'image', 'file'], true)) {
                continue;
            }
            if (in_array($type, ['checkbox', 'radio'], true) && !preg_match('/checked(="checked")?/i', $attrs)) {
                continue;
            }
            $name = html_entity_decode($nm[1], ENT_QUOTES);
            $value = preg_match('/value="([^"]*)"/', $attrs, $vm) ? html_entity_decode($vm[1], ENT_QUOTES) : '';

            // jQuery UI datepickers on this site default empty receipt-date fields
            // to today's date client-side before any postback (confirmed via diff
            // for MadresaPrePay2/mp1's txtreceiptdate; the pattern recurs across
            // several similar payment-entry sub-panels using the same field name).
            if ($value === '' && str_ends_with($name, '$txtreceiptdate')) {
                $value = date('d-M-Y');
            }

            $fields[$name] = $value;
        }

        preg_match_all('/<textarea[^>]*name="([^"]+)"[^>]*>(.*?)<\/textarea>/is', $html, $textareaMatches, PREG_SET_ORDER);
        foreach ($textareaMatches as $m) {
            $fields[html_entity_decode($m[1], ENT_QUOTES)] = html_entity_decode(trim($m[2]), ENT_QUOTES);
        }

        preg_match_all('/<select[^>]*name="([^"]+)"[^>]*>(.*?)<\/select>/is', $html, $selectMatches, PREG_SET_ORDER);
        foreach ($selectMatches as $m) {
            $name = html_entity_decode($m[1], ENT_QUOTES);
            if (preg_match('/<option[^>]*selected="selected"[^>]*value="([^"]*)"/i', $m[2], $om)) {
                $fields[$name] = html_entity_decode($om[1], ENT_QUOTES);
            } elseif (preg_match('/<option[^>]*value="([^"]*)"/i', $m[2], $om)) {
                // No option explicitly marked selected — browsers default to the first.
                $fields[$name] = html_entity_decode($om[1], ENT_QUOTES);
            }
        }

        return $fields;
    }

    /**
     * Finds the <input> whose id contains $idSubstring (attribute-order-agnostic —
     * confirmed the delta-postback responses render `name`/`type`/`value`/`id` in a
     * different order than full-page HTML does) and returns its value.
     */
    public static function extractValueById(string $html, string $idSubstring): ?string
    {
        if (!preg_match('/<input[^>]*\bid="[^"]*' . preg_quote($idSubstring, '/') . '[^"]*"[^>]*>/', $html, $tag)) {
            return null;
        }

        if (preg_match('/\bvalue="([^"]*)"/', $tag[0], $m)) {
            return html_entity_decode($m[1], ENT_QUOTES);
        }

        return null;
    }

    /**
     * Returns [value => label] for every <option> in the <select> with the given id.
     *
     * @return array<string, string>
     */
    public static function extractOptionsById(string $html, string $id): array
    {
        $options = [];
        if (!preg_match('/id="' . preg_quote($id, '/') . '"[^>]*>(.*?)<\/select>/is', $html, $selectMatch)) {
            return $options;
        }

        preg_match_all('/<option[^>]*value="([^"]*)"[^>]*>([^<]*)</i', $selectMatch[1], $optionMatches, PREG_SET_ORDER);
        foreach ($optionMatches as $opt) {
            $options[html_entity_decode($opt[1], ENT_QUOTES)] = trim(html_entity_decode($opt[2], ENT_QUOTES));
        }

        return $options;
    }
}
