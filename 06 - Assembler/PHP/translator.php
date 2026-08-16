<?php

/**
 * translator.php
 *
 * Converts cleaned Hack assembly instructions (as produced by parser.php)
 * into their 16-bit binary machine code representation, per the Hack
 * platform specification.
 *
 * Two instruction types are handled:
 *  - A-instructions: "@value" or "@symbol"  → 0 followed by a 15-bit address.
 *  - C-instructions: "dest=comp;jump"       → 111 followed by comp/dest/jump bit fields.
 *
 * Label declarations ("(LABEL)") are not instructions in their own right —
 * they're resolved during the assembler's first pass — so both translation
 * functions return null for them and the caller filters those out.
 */

require_once "symbol_table.php";

/** @var array<string,string> Maps a "comp" mnemonic to its 7-bit code. */
$COMP = [
    "0"=> "0101010",
    "1"=> "0111111",
    "-1"=> "0111010",
    "D"=> "0001100",
    "A"=> "0110000",
    "M"=> "1110000",
    "!D"=> "0001101",
    "!A"=> "0110001",
    "!M"=> "1110001",
    "-D"=> "0001111",
    "-A"=> "0110011",
    "-M"=> "1110011",
    "D+1"=> "0011111",
    "A+1"=> "0110111",
    "M+1"=> "1110111",
    "D-1"=> "0001110",
    "A-1"=> "0110010",
    "M-1"=> "1110010",
    "D+A"=> "0000010",
    "D+M"=> "1000010",
    "D-A"=> "0010011",
    "D-M"=> "1010011",
    "A-D"=> "0000111",
    "M-D"=> "1000111",
    "D&A"=> "0000000",
    "D&M"=> "1000000",
    "D|A"=> "0010101",
    "D|M"=> "1010101",
];

/** @var array<string,string> Maps a "dest" mnemonic to its 3-bit code. */
$DEST = [
    ""=> "000",
    "M"=> "001",
    "D"=> "010",
    "DM"=> "011",
    "MD"=> "011",
    "A"=> "100",
    "AM"=> "101",
    "MA"=> "101",
    "AD"=> "110",
    "DA"=> "110",
    "ADM"=> "111",
    "AMD"=> "111",
    "DAM"=> "111",
    "DMA"=> "111",
    "MDA"=> "111",
    "MAD"=> "111",
];

/** @var array<string,string> Maps a "jump" mnemonic to its 3-bit code. */
$JUMP = [
    ""=> "000",
    "JGT"=> "001",
    "JEQ"=> "010",
    "JGE"=> "011",
    "JLT"=> "100",
    "JNE"=> "101",
    "JLE"=> "110",
    "JMP"=> "111",
];

/**
 * Translates a single A-instruction ("@value" or "@symbol") into its
 * 16-bit binary form.
 *
 * Numeric operands are converted directly. Symbolic operands (variables
 * or labels) are resolved via the symbol table — by this point in the
 * assembler's pipeline every symbol is expected to already be registered.
 *
 * @param string $instruction A single cleaned instruction line.
 * @return string|null The 16-bit binary string, or null if this is not an A-instruction.
 */
function atranslate($instruction) {
    if (str_starts_with($instruction, "@")) {
        if (!is_numeric(substr($instruction,1))) {
            // Symbolic operand — resolve to its address via the symbol table.
            $a = str_pad(decbin(intval(lookup(substr($instruction,1)))), 16, "0", STR_PAD_LEFT);
        }
        else {
            // Numeric operand — convert directly.
            $a = str_pad(decbin(intval(substr($instruction,1))), 16, "0", STR_PAD_LEFT);
        }
        return $a;
    }
    else {
        return null;
    }
}

/**
 * Translates a single C-instruction ("dest=comp;jump", with dest and/or
 * jump optional) into its 16-bit binary form.
 *
 * The three fields are extracted based on which of "=" and ";" are present,
 * then each mnemonic is looked up in its respective bit-pattern table.
 * Label declarations ("(LABEL)") are not C-instructions and return null.
 *
 * @param string $instruction A single cleaned instruction line.
 * @return string|null The 16-bit binary string, or null if this is a label declaration.
 */
function ctranslate($instruction) {
    global $COMP, $DEST, $JUMP;

    if (str_starts_with($instruction, "(")) {
        return null;
    }

    if (str_contains($instruction, "=") and str_contains($instruction, ";")) {
        // Full form: dest=comp;jump
        $d = substr($instruction,0, strpos($instruction,"="));
        $start = strpos($instruction,"=");
        $length = (strpos($instruction, ";")) - (1 + $start);
        $c = substr($instruction,$start+1,$length);
        $j = substr($instruction, strpos($instruction, ";")+1);
    }
    elseif (str_contains($instruction, ";")) {
        // Jump only: comp;jump
        $d = "";
        $c = substr($instruction,0, strpos($instruction, ";"));
        $j = substr($instruction, strpos($instruction, ";")+1);
    }
    elseif (str_contains($instruction, "=")) {
        // Destination only: dest=comp
        $d = substr($instruction,0, strpos($instruction,"="));
        $c = substr($instruction, strpos($instruction, "=")+1);
        $j = "";
    }
    else {
        // Comp only, no dest or jump.
        $d = "";
        $c = $instruction;
        $j = "";
    }

    // Resolve each mnemonic to its binary field.
    $d = $DEST[$d];
    $c = $COMP[$c];
    $j = $JUMP[$j];

    // C-instructions always begin with the 3-bit opcode 111.
    return "111" . $c . $d . $j;
}

/**
 * Translates a full list of cleaned instructions into their binary form,
 * dispatching each line to atranslate() or ctranslate() as appropriate.
 *
 * Label declarations are silently dropped, since they have no binary
 * representation of their own — they only affect address resolution
 * during the assembler's first pass.
 *
 * @param string[] $instruction Ordered list of cleaned instruction lines.
 * @return string[] Ordered list of 16-bit binary instruction strings.
 */
function translate($instruction) {
    $translated = [];
    foreach ($instruction as $i){
        if (str_starts_with($i, "@")){
            $a = atranslate($i);
            $translated[] = $a;
        }
        else {
            $c = ctranslate($i);
            if ($c!=null){
                $translated[] = $c;
            }
        }
    }
    return $translated;
}