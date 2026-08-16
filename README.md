# Hack Assembler

Projects 4 and 6 of [nand2tetris](https://www.nand2tetris.org/): writing programs directly in Hack assembly, then building the assembler that translates that assembly into 16-bit binary machine code.

Implemented independently in **Python** and **PHP**. A **C** version is in progress on the `c-implementation` branch.

## Repository layout

```
06 - Assembler/
    Python/              complete implementation
    PHP/                 complete implementation
    *.asm                test programs supplied with the course
```

The project 4 assembly programs (`mult.asm`, `fill.asm`) will be added shortly.

## The Hack instruction set

Two instruction types:

- **A-instructions** (`@value`) load a constant or address into the A register. The value may be a literal, a predefined symbol, a label, or a variable.
- **C-instructions** (`dest=comp;jump`) compute, optionally store the result, and optionally jump.

The assembler strips comments and whitespace, resolves every symbol to an address, and emits one 16-bit binary line per instruction.

## Design: why two passes

The problem that shapes the whole program is a jump can target a label that isn't defined until later in the file:

```asm
@END        // referenced here...
0;JMP
(END)       // ...but not declared until here
```

A single pass reaches `@END` with no way to resolve it. I used two passes, which keeps symbol resolution in one place instead of spreading it across code generation.

**First pass** records label declarations only. Each `(LABEL)` is stored against the address of the next instruction.

Labels are pseudo-instructions: they occupy a source line but generate no machine code. So the counter advances only on real instructions, after comments and blank lines are stripped.

**Second pass** generates code. Every label is known by then, so any symbol still unrecognised must be a variable, and gets the next free RAM address from 16 upward.

## Symbol table

Pre-loaded with the predefined symbols: `R0`–`R15` → 0–15, `SP`/`LCL`/`ARG`/`THIS`/`THAT` → 0–4, `SCREEN` → 16384, `KBD` → 24576. Labels are added in the first pass, variables in the second.

## Usage

Run from inside the implementation directory:

```bash
# Python
python3 assembly.py ../Add.asm

# PHP
php assembly.php ../Add.asm
```

Output is written to `assembly.hack` in the working directory.

## Known limitations, and what I'd change

**The symbol table is persisted to a CSV file rather than held in memory.** I did this so the symbol table functions could be called independently across both passes without threading the table through as a parameter. It works, but it was the wrong call: every lookup reopens `symbol.csv` and rebuilds the entire dictionary, so assembling a large program like `Pong.asm` performs thousands of full-file reads for what should be a single in-memory dict. It also means the assembler writes `symbol.csv` and `counter.txt` into the working directory as side effects. Rewriting it as a module-level dictionary is the first change I'd make.

**Output filename is hardcoded** to `assembly.hack` rather than derived from the input, so `Prog.asm` should produce `Prog.hack` and doesn't.

**Error handling.** A missing argument, an unreadable file or an unrecognised mnemonic all fail with a raw exception rather than a useful message.

**The C implementation is unfinished** and lives on a separate branch. The parser strips comments and whitespace correctly but does not yet populate the array it returns, and the header is still empty, so it does not currently compile. It is there as work in progress, not as a third finished version.

## Why implement it more than once

Writing the assembler a second time in PHP was a deliberate exercise in separating the algorithm from the language. The two-pass structure and symbol table carry across unchanged; only string handling and file I/O differ. The C version is a third attempt at the same problem, taken on to learn the language, with manual memory management as the point of the exercise.

## Context

Built alongside full-time work, following the nand2tetris hardware track from NAND gates through the ALU, memory and CPU. The earlier HDL projects aren't published here — the course asks that solutions aren't shared.
