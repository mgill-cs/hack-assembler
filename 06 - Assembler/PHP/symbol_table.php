<?php

/**
 * symbol_table.php
 *
 * Maintains the Hack assembler's symbol table — the mapping of labels and
 * variable names to memory/ROM addresses. The table is persisted to a CSV
 * file (rather than kept purely in memory) so each function here can be
 * called independently across the assembler's two passes without needing
 * to pass the table itself around.
 *
 * Storage:
 *  - symbol.csv   Two-column CSV ("Symbol", "Value") holding every known
 *                 symbol, starting with the predefined Hack symbols.
 *  - counter.txt  A single integer tracking the next free RAM address to
 *                 assign to a new variable (starts at 16, since 0-15 are
 *                 reserved for R0-R15).
 */

$symbol_file = "symbol.csv";
$headers = ["Symbol", "Value"];
$counter_file = "counter.txt";

/**
 * Resets and (re)builds the symbol table with the Hack platform's
 * predefined symbols, and resets the variable counter to 16.
 *
 * Safe to call multiple times — any existing symbol.csv is deleted first,
 * so each assembler run starts from a clean predefined table rather than
 * accumulating symbols from a previous run.
 *
 * @return void
 */
function initialise() {
    global $symbol_file, $headers, $counter_file;

    if (file_exists($symbol_file)) {
        unlink($symbol_file);
    }

    if (!file_exists($symbol_file)) {
        $f = fopen($symbol_file, "w");
        fputcsv($f, $headers);

        // Predefined symbols required by the Hack platform spec:
        // virtual registers R0-R15, memory-mapped I/O, and VM segment pointers.
        $predifined = [
            ["R0", 0],
            ["R1", 1],
            ["R2", 2],
            ["R3", 3],
            ["R4", 4],
            ["R5", 5],
            ["R6", 6],
            ["R7", 7],
            ["R8", 8],
            ["R9", 9],
            ["R10", 10],
            ["R11", 11],
            ["R12", 12],
            ["R13", 13],
            ["R14", 14],
            ["R15", 15],
            ["SCREEN", 16384],
            ["KBD", 24576],
            ["SP", 0],
            ["LCL", 1],
            ["ARG", 2],
            ["THIS", 3],
            ["THAT", 4],
        ];
        foreach ($predifined as $row) {
            fputcsv($f, $row);
        }
        fclose($f);

        // First free RAM address available for user-defined variables.
        $f = fopen($counter_file, "w");
        fwrite($f, "16");
        fclose($f);
    }
}

/**
 * Reads the next available RAM address for a new variable symbol.
 *
 * @return int The next free address (does not advance the counter — see increment_counter()).
 */
function get_next_value() {
    global $counter_file;
    $f = file_get_contents($counter_file);
    return intval(trim($f));
}

/**
 * Advances the variable counter by one, after a new variable has been
 * assigned the current value from get_next_value().
 *
 * @return void
 */
function increment_counter() {
    global $counter_file;
    $current = get_next_value();
    $f = fopen($counter_file, "w");
    fwrite($f, strval($current + 1));
    fclose($f);
}

/**
 * Appends a new symbol/value pair to the symbol table.
 *
 * @param string $name  The symbol name (e.g. a label or variable name).
 * @param int    $value The address to associate with the symbol.
 * @return void
 */
function add_row($name, $value) {
    global $symbol_file;
    $f = fopen($symbol_file, "a");
    fputcsv($f, [$name, $value]);
    fclose($f);
}

/**
 * Looks up the address associated with a symbol name.
 *
 * @param string $name The symbol to look up.
 * @return string The address associated with the symbol, as stored in the CSV.
 * @throws Exception If the symbol does not exist in the table.
 */
function lookup($name) {
    global $symbol_file;

    $reader = fopen($symbol_file, "r");
    $table = [];
    while (($row = fgetcsv($reader)) !== false) {
        if ($row[0] !== "Symbol") { // Skip the header row.
            $table[$row[0]] = $row[1];
        }
    }
    fclose($reader);

    if (array_key_exists($name, $table)) {
        return $table[$name];
    } else {
        throw new Exception("Symbol '$name' not found in symbol_table");
    }
}