import csv
import os

SYMBOL_FILE = "symbol.csv"
HEADERS = ["Symbol", "Value"]
COUNTER_FILE = "counter.txt"


def initialise():
    if os.path.exists(SYMBOL_FILE):
        os.remove(SYMBOL_FILE)

    # Creates predifined table
    if not os.path.exists(SYMBOL_FILE):
        with open(SYMBOL_FILE, "w", newline="") as f:
            writer = csv.writer(f)
            writer.writerow(HEADERS)
            # Predefined values
            writer.writerows(
                [
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
                ]
            )

        # Initialise counter at 16
        with open(COUNTER_FILE, "w") as f:
            f.write("16")


def get_next_value():
    with open(COUNTER_FILE, "r") as f:
        return int(f.read().strip())


def increment_counter():
    current = get_next_value()
    with open(COUNTER_FILE, "w") as f:
        f.write(str(current + 1))


def add_row(name, value):
    with open(SYMBOL_FILE, "a", newline="") as f:
        writer = csv.writer(f)
        writer.writerow([name, value])


def lookup(name):
    with open(SYMBOL_FILE, "r") as f:
        reader = csv.reader(f)
        table = {row[0]: row[1] for row in reader if row[0] != "Symbol"}  # Skip header
        if name in table:
            return table[name]
        else:
            raise ValueError(f"Symbol '{name}' not found in name symbol_table")
