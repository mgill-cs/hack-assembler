<?php

/**
 * parser.php
 *
 * Reads a Hack assembly (.asm) source file and reduces it to a clean,
 * ordered list of instructions — stripping comments, blank lines, and
 * surrounding whitespace so downstream stages never have to deal with
 * raw source formatting.
 */

/**
 * Reads and parses a Hack assembly file into a flat array of instructions.
 *
 * Each line is stripped of trailing comments (anything after "//") and
 * surrounding whitespace. Lines that are empty after cleaning (blank lines,
 * or lines that were comment-only) are discarded entirely, so the returned
 * array contains only meaningful instruction lines in their original order.
 *
 * @param string $file Path to the .asm source file to read.
 * @return string[] Ordered list of cleaned instruction lines.
 */
function parse_instruction($file) {
    $instruction = [];

    $handle = fopen($file, "r");
    while (($line = fgets($handle)) !== false) {
        // Comments start with "//" — keep only the code before it.
        $i1 = trim(explode("//", $line)[0]);

        // Skip lines that are blank or were comment-only.
        if ($i1 !== "") {
            $instruction[] = $i1;
        }
    }
    fclose($handle);

    return $instruction;
}