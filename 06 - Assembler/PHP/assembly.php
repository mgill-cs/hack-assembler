<?php

/**
 * assembler.php
 *
 * Entry point for the Hack assembler. Converts a Hack assembly (.asm)
 * source file into Hack binary machine code (.hack).
 *
 * Usage:
 *   php assembler.php path/to/Program.asm
 *
 * Pipeline:
 *   1. Parse the source file into a clean list of instructions.
 *   2. First pass  — scan for label declarations "(LABEL)" and register
 *                     their target addresses (the ROM line they point to)
 *                     in the symbol table.
 *   3. Second pass — scan for variable references ("@name", non-numeric)
 *                     and register any not already known, assigning them
 *                     the next free RAM address.
 *   4. Translate every instruction to 16-bit binary and write the result
 *      to a .hack file.
 *
 * The two-pass approach is required because a label can be referenced
 * (e.g. in a jump) before it's declared later in the file, so all labels
 * must be resolved before any address translation happens.
 */

require_once "parser.php";
require_once "translator.php";
require_once "symbol_table.php";

// --- Load input file -------------------------------------------------

$file = $argv[1];
$hack = "assembly.hack";

$instruction = parse_instruction($file);

// Start from a clean symbol table (predefined symbols only) each run.
initialise();

// --- First pass: register label addresses ----------------------------
//
// Labels don't occupy a ROM address themselves — they point to the
// address of the *next* real instruction. $line_count tracks how many
// real instructions have been seen so far, so it's effectively also
// the address the next instruction will be assembled to.

$line_count = 0;

$f = fopen($file, "r");
while (($line = fgets($f)) !== false) {
    $line = trim(explode("//", $line)[0]); // Strip comments and whitespace.

    if ($line === "") {
        continue; // Skip blank/comment-only lines.
    }

    if (str_starts_with($line, "(")) {
        // Label declaration, e.g. "(LOOP)" — extract the name and
        // register it against the current instruction count.
        if (preg_match("/\((.*?)\)/", $line, $matches)) {
            $label = $matches[1];
        }
        add_row($label, $line_count);
    }
    else {
        $line_count = $line_count + 1; // Only real instructions advance the address.
    }
}
fclose($f);

// --- Second pass: register variable symbols ---------------------------
//
// Any "@symbol" where the symbol isn't numeric and isn't already in the
// table (as a predefined symbol or a label from the first pass) is a new
// user variable. It's assigned the next free RAM address and the counter
// is advanced for the next one.

$f = fopen($file, "r");
while (($line = fgets($f)) !== false) {
    $line = trim(explode("//", $line)[0]);

    if (str_starts_with($line, "@")) {
        $symbol = substr($line, 1);
        if (!is_numeric($symbol)) {
            try {
                lookup($symbol); // Already known — nothing to do.
            }
            catch (Exception $e) {
                // Not yet known — register as a new variable.
                add_row($symbol, get_next_value());
                increment_counter();
            }
        }
    }
}
fclose($f);

// --- Translate and write output ---------------------------------------

$binstruction = translate($instruction);

$f = fopen($hack, "w");
fwrite($f, implode("\n", $binstruction));
fclose($f);