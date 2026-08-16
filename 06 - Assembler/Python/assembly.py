# Hack Assembler
import parser
import translator
import symbol_table
import re
import sys

# Load input file
FILE = sys.argv[1]
HACK = "assembly.hack"

instruction = parser.parse_instruction(FILE)

symbol_table.initialise()

line_count = 0

with open(FILE, "r") as f:
    lines = f.readlines()
    for line in lines:
        line = line.strip()  # Remove leading and trailing whitespace
        line = line.split("//")[0].strip()  # Remove comments
        # Skip empty lines after cleaning
        if not line:
            continue
        if line.startswith("("):
            symbol = line[1:-1]  # Extract symbol from (symbol)
            match = re.search(r"\((.*?)\)", line)
            if match:
                label = match.group(1)
                symbol_table.add_row(label, line_count)  # Write the symbol to a file
        else:
            line_count += 1  # count non-bracket, non-empty, non-comment lines

with open(FILE, "r") as f:
    lines = f.readlines()
    for line in lines:
        line = line.strip()  # Remove leading and trailing whitespace
        line = line.split("//")[0].strip()  # Remove comments
        if line.startswith("@"):
            symbol = line[1:]
            if not symbol.isdigit():
                try:
                    symbol_table.lookup(symbol)
                except ValueError:
                    # Only add if it's not a number and if not already in the symbol table
                    symbol_table.add_row(symbol, symbol_table.get_next_value())
                    # Add the symbol with the next available value
                    symbol_table.increment_counter()  # Increment the counter for the next symbol


binstruction = translator.translate(instruction)

with open(HACK, "w") as f:
    f.write("\n".join(binstruction))
